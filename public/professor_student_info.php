<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/config/db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Secretary') {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'FORBIDDEN']);
  exit;
}

if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf'])) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'BAD_CSRF']);
  exit;
}

function cleanInt(?string $s): ?int {
  if ($s === null) return null;
  $d = preg_replace('/\D/', '', $s);
  return $d === '' ? null : (int)$d;
}

function getOrCreateUser(mysqli $conn, string $email, string $fullName, ?int $am, string $userType, string $externalId): int {
  $sel = $conn->prepare("SELECT UserID FROM Users WHERE Email=? LIMIT 1");
  $sel->bind_param("s", $email);
  $sel->execute();
  $sel->bind_result($uid);
  if ($sel->fetch()) { $sel->close(); return (int)$uid; }
  $sel->close();

  $ins = $conn->prepare("
    INSERT INTO Users (Email, Password_hash, FullName, AM, UserType, ExternalID)
    VALUES (?, '123', ?, ?, ?, ?)
  ");
  $ins->bind_param("ssiss", $email, $fullName, $am, $userType, $externalId);
  if (!$ins->execute()) throw new RuntimeException("Users insert failed: ".$ins->error);
  $id = (int)$ins->insert_id;
  $ins->close();
  return $id;
}

function syncProfessor(mysqli $conn, array $p): void {
  $email   = trim((string)($p['email'] ?? ''));
  if ($email === '') return;

  $name    = trim((string)($p['name'] ?? ''));
  $surname = trim((string)($p['surname'] ?? ''));
  $full    = trim(($name.' '.$surname)) ?: 'unknown';
  $topic   = (string)($p['topic'] ?? null);
  $land    = (string)($p['landline'] ?? null);
  $mob     = (string)($p['mobile'] ?? null);
  $dept    = (string)($p['department'] ?? null);
  $uni     = (string)($p['university'] ?? null);
  $extId   = (string)($p['id'] ?? '');

  $uid = getOrCreateUser($conn, $email, $full, null, 'Professor', $extId);

  $sel = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID=? LIMIT 1");
  $sel->bind_param("i", $uid);
  $sel->execute();
  $sel->bind_result($pid);
  if ($sel->fetch()) {
    $sel->close();
    $upd = $conn->prepare("
      UPDATE Professor SET FullName=?, Email=?, Name=?, Surname=?, Topic=?, Landline=?, Mobile=?, Department=?, University=? 
      WHERE ProfessorID=?
    ");
    $upd->bind_param("sssssssssi", $full, $email, $name, $surname, $topic, $land, $mob, $dept, $uni, $pid);
    $upd->execute();
    $upd->close();
  } else {
    $sel->close();
    $ins = $conn->prepare("
      INSERT INTO Professor (UserID, FullName, Email, Name, Surname, Topic, Landline, Mobile, Department, University)
      VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    $ins->bind_param("isssssssss", $uid, $full, $email, $name, $surname, $topic, $land, $mob, $dept, $uni);
    $ins->execute();
    $ins->close();
  }
}

function syncStudent(mysqli $conn, array $s): void {
  $email   = trim((string)($s['email'] ?? ''));
  if ($email === '') return;

  $name    = trim((string)($s['name'] ?? ''));
  $surname = trim((string)($s['surname'] ?? ''));
  $full    = trim(($name.' '.$surname)) ?: 'unknown';
  $am      = cleanInt((string)($s['student_number'] ?? ''));
  $street  = (string)($s['street'] ?? null);
  $number  = (string)($s['number'] ?? null);
  $city    = (string)($s['city'] ?? null);
  $postcode= (string)($s['postcode'] ?? null);
  $father  = (string)($s['father_name'] ?? null);
  $land    = (string)($s['landline_telephone'] ?? null);
  $mob     = (string)($s['mobile_telephone'] ?? null);
  $extId   = (string)($s['id'] ?? '');

  $uid = getOrCreateUser($conn, $email, $full, $am, 'Student', $extId);

  $sel = $conn->prepare("SELECT StudentID FROM Student WHERE UserID=? LIMIT 1");
  $sel->bind_param("i", $uid);
  $sel->execute();
  $sel->bind_result($sid);
  if ($sel->fetch()) {
    $sel->close();
    $upd = $conn->prepare("
      UPDATE Student SET FullName=?, AM=?, Email=?, MobilePhone=?, LandlinePhone=?, Name=?, Surname=?, Street=?, Number=?, City=?, Postcode=?, Father_Name=? 
      WHERE StudentID=?
    ");
    $upd->bind_param("sissssssssssi", $full, $am, $email, $mob, $land, $name, $surname, $street, $number, $city, $postcode, $father, $sid);
    $upd->execute();
    $upd->close();
  } else {
    $sel->close();
    $ins = $conn->prepare("
      INSERT INTO Student (UserID, FullName, AM, Email, MobilePhone, LandlinePhone, Name, Surname, Street, Number, City, Postcode, Father_Name)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $ins->bind_param("isissssssssss", $uid, $full, $am, $email, $mob, $land, $name, $surname, $street, $number, $city, $postcode, $father);
    $ins->execute();
    $ins->close();
  }
}

$url = trim((string)($_POST['url'] ?? ''));
if ($url === '' || !preg_match('~^https?://~i', $url)) {
  echo json_encode(['ok'=>false,'error'=>'BAD_URL']);
  exit;
}

$raw = @file_get_contents($url, false, stream_context_create([
  'http'=>['timeout'=>20],
  'ssl' =>['verify_peer'=>false,'verify_peer_name'=>false]
]));
if ($raw === false) {
  echo json_encode(['ok'=>false,'error'=>'FETCH_FAILED']);
  exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  echo json_encode(['ok'=>false,'error'=>'BAD_JSON']);
  exit;
}

$profs = $data['professors'] ?? [];
$studs = $data['students'] ?? [];

$conn->begin_transaction();
try {
  foreach ($profs as $p) syncProfessor($conn, $p);
  foreach ($studs as $s) syncStudent($conn, $s);
  $conn->commit();
  echo json_encode(['ok'=>true,'msg'=>'Import done','professors'=>count($profs),'students'=>count($studs)]);
} catch (Throwable $e) {
  $conn->rollback();
  echo json_encode(['ok'=>false,'error'=>'DB_ERROR','details'=>$e->getMessage()]);
}



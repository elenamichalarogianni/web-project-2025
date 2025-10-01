<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Student') {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}


$studentId = (int)($_COOKIE['studentid'] ?? 0);
if ($studentId <= 0) {
  $userId = (int)($_COOKIE['userid'] ?? 0);
  if ($userId > 0) {
    $q = $conn->prepare("SELECT StudentID FROM Student WHERE UserID=? LIMIT 1");
    $q->bind_param('i',$userId); $q->execute();
    $q->bind_result($sid); $q->fetch(); $q->close();
    $studentId = (int)$sid;
  }
}
if ($studentId <= 0) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NO_STUDENT']); exit; }

if ($_SERVER['REQUEST_METHOD']==='GET') {
  $q = $conn->prepare("SELECT Email, MobilePhone, LandlinePhone, Address FROM Student WHERE StudentID=?");
  $q->bind_param('i',$studentId); $q->execute();
  $res = $q->get_result()->fetch_assoc(); $q->close();
  echo json_encode(['ok'=>true,'profile'=>[
    'Email'=>$res['Email'] ?? '',
    'MobilePhone'=>$res['MobilePhone'] ?? '',
    'LandlinePhone'=>$res['LandlinePhone'] ?? '',
    'Address'=>$res['Address'] ?? ''
  ]]); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $email  = trim($in['Email'] ?? '');
  $mobile = trim($in['MobilePhone'] ?? '');
  $land   = trim($in['LandlinePhone'] ?? '');
  $addr   = trim($in['Address'] ?? '');

  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422); echo json_encode(['ok'=>false,'error'=>'INVALID_EMAIL']); exit;
  }

  $u = $conn->prepare("UPDATE Student SET Email=?, MobilePhone=?, LandlinePhone=?, Address=? WHERE StudentID=?");
  $u->bind_param('ssssi',$email,$mobile,$land,$addr,$studentId);
  $ok = $u->execute(); $u->close();
  if (!$ok) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'UPDATE_FAILED']); exit; }

  echo json_encode(['ok'=>true]); exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED']);

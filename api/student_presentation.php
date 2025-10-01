<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}


$studentId = (int)($_COOKIE['studentid'] ?? 0);
if ($studentId <= 0) {
  $uid = (int)($_COOKIE['userid'] ?? 0);
  if ($uid > 0) {
    $q = $conn->prepare("SELECT StudentID FROM Student WHERE UserID=? LIMIT 1");
    $q->bind_param('i',$uid); $q->execute(); $q->bind_result($sid); $q->fetch(); $q->close();
    $studentId = (int)$sid;
  }
}
if ($studentId <= 0) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NO_STUDENT']); exit; }

$method   = $_SERVER['REQUEST_METHOD'];
$thesisId = (int)($_GET['thesis_id'] ?? ($_POST['thesis_id'] ?? 0));


function assert_own_under_review(mysqli $conn, int $thesisId, int $studentId, $allowAnyStatus=false){
  $st = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=? AND StudentID=?");
  $st->bind_param('ii',$thesisId,$studentId);
  $st->execute(); $st->bind_result($status);
  if (!$st->fetch()) { $st->close(); http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOUR_THESIS']); exit; }
  $st->close();
  if (!$allowAnyStatus && $status !== 'Under Review') { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'WRONG_STATUS']); exit; }
}

if ($method === 'GET') {
  if ($thesisId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }
  
  assert_own_under_review($conn, $thesisId, $studentId, true);

  $q = $conn->prepare("
    SELECT ExamDate, ExamTime, PresentationType, LocationOrLink, COALESCE(AnnouncementText,'') AS AnnouncementText
    FROM Presentation WHERE ThesisID=?
  ");
  $q->bind_param('i',$thesisId); $q->execute();
  $row = $q->get_result()->fetch_assoc();
  $q->close();
  echo json_encode(['ok'=>true,'presentation'=>$row ?: null]); exit;
}

if ($method === 'POST') {
  
  $inRaw = file_get_contents('php://input');
  $in    = json_decode($inRaw, true) ?: [];
  $thesisId = (int)($in['thesis_id'] ?? 0);
  $date     = trim($in['exam_date'] ?? '');
  $time     = trim($in['exam_time'] ?? '');
  $type     = trim($in['presentation_type'] ?? '');
  $loc      = trim($in['location_or_link'] ?? '');
  $text     = trim($in['announcement_text'] ?? '');

  if ($thesisId <= 0 || $date === '' || $time === '' || $loc === '') {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit;
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'DATE_FORMAT']); exit; }
  if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'TIME_FORMAT']); exit; }
  if (!in_array($type, ['InPerson','Online'], true)) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'BAD_TYPE']); exit; }

  assert_own_under_review($conn, $thesisId, $studentId);

  
  $sql = "INSERT INTO Presentation
            (ThesisID, ExamDate, ExamTime, PresentationType, LocationOrLink, AnnouncementText)
          VALUES (?, ?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE
            ExamDate=VALUES(ExamDate),
            ExamTime=VALUES(ExamTime),
            PresentationType=VALUES(PresentationType),
            LocationOrLink=VALUES(LocationOrLink),
            AnnouncementText=VALUES(AnnouncementText)";
  $st = $conn->prepare($sql);
  if (!$st) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$conn->error]); exit; }
  $st->bind_param('isssss', $thesisId, $date, $time, $type, $loc, $text);
  if (!$st->execute()) { $st->close(); http_response_code(500); echo json_encode(['ok'=>false,'error'=>$st->error]); exit; }
  $st->close();

  echo json_encode(['ok'=>true,'message'=>'Presentation saved']); exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'METHOD_NOT_ALLOWED']);

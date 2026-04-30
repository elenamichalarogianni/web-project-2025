<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$uid       = (int)$_COOKIE['userid'];
$studentId = (int)($_COOKIE['studentid'] ?? 0);
if ($studentId <= 0) {
  $q=$conn->prepare("SELECT StudentID FROM Student WHERE UserID=? LIMIT 1");
  $q->bind_param('i',$uid); $q->execute(); $q->bind_result($sid); $q->fetch(); $q->close();
  $studentId = (int)$sid;
}
if ($studentId <= 0) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NO_STUDENT']); exit; }

$thesisId = (int)($_POST['thesis_id'] ?? 0);
if ($thesisId <= 0 || empty($_FILES['file'])) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit;
}


$st = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=? AND StudentID=? LIMIT 1");
$st->bind_param('ii',$thesisId,$studentId);
$st->execute(); $st->bind_result($status); $ok = $st->fetch(); $st->close();
if (!$ok) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOUR_THESIS']); exit; }
if ($status !== 'Under Review') {
  http_response_code(409); echo json_encode(['ok'=>false,'error'=>'WRONG_STATUS']); exit;
}


$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'UPLOAD_ERROR']); exit; }

$maxBytes = 20 * 1024 * 1024; 
if ($f['size'] > $maxBytes) { http_response_code(413); echo json_encode(['ok'=>false,'error'=>'FILE_TOO_LARGE']); exit; }

$allowed = [
  'pdf'  => 'application/pdf',
  'doc'  => 'application/msword',
  'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if (!isset($allowed[$ext])) { http_response_code(415); echo json_encode(['ok'=>false,'error'=>'BAD_TYPE']); exit; }


$baseDir = dirname(__DIR__) . '/uploads/theses/' . $thesisId;
if (!is_dir($baseDir)) { mkdir($baseDir, 0775, true); }
$fname = 'draft_'.date('Ymd_His').'.'.$ext;
$abs   = $baseDir . '/' . $fname;
$rel   = 'uploads/theses/'.$thesisId.'/'.$fname;


if (!move_uploaded_file($f['tmp_name'], $abs)) {
  http_response_code(500); echo json_encode(['ok'=>false,'error'=>'MOVE_FAILED']); exit;
}


$u = $conn->prepare("UPDATE Thesis SET Link=? WHERE ThesisID=?");
$u->bind_param('si',$rel,$thesisId);
if (!$u->execute()) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$u->error]); exit; }
$u->close();



echo json_encode(['ok'=>true, 'link'=>$rel]);


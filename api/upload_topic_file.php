<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Professor' || empty($_COOKIE['professorid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$topicId  = (int)($_POST['topic_id']  ?? 0);
$thesisId = (int)($_POST['thesis_id'] ?? 0);
if (!$topicId && !$thesisId) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'NO_FILE']); exit;
}

$allowed = [
  'pdf'  => 'application/pdf',
  'doc'  => 'application/msword',
  'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$maxBytes = 20 * 1024 * 1024; 
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!isset($allowed[$ext])) { http_response_code(415); echo json_encode(['ok'=>false,'error'=>'BAD_TYPE']); exit; }
if ($_FILES['file']['size'] > $maxBytes) { http_response_code(413); echo json_encode(['ok'=>false,'error'=>'FILE_TOO_LARGE']); exit; }

$baseRoot = dirname(__DIR__).'/uploads';
if ($topicId)  { $sub = 'topics/'.$topicId;  $table='topic'; }
if ($thesisId) { $sub = 'theses/'.$thesisId; $table='thesis'; }
$destDir = $baseRoot.'/'.$sub;
if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }

$fname = 'file_'.date('Ymd_His').'.'.$ext;
$abs   = $destDir.'/'.$fname;
$rel = 'uploads/topics/'.$topicId.'/'.$fname;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $abs)) {
  http_response_code(500); echo json_encode(['ok'=>false,'error'=>'MOVE_FAILED']); exit;
}

if ($table==='topic') {
  $u = $conn->prepare("UPDATE ThesisTopic SET PDFpath=? WHERE TopicID=?");
  $u->bind_param('si',$rel,$topicId);
} else {
  $u = $conn->prepare("UPDATE Thesis SET Link=? WHERE ThesisID=?");
  $u->bind_param('si',$rel,$thesisId);
}
if (!$u->execute()) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$u->error]); exit; }
$u->close();

echo json_encode(['ok'=>true,'link'=>$rel]);


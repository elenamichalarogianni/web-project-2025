<?php
require_once 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$fileId = (int)($_POST['file_id'] ?? 0);
if ($fileId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }


$q = $conn->prepare("
  SELECT tf.ThesisID, tf.FileType, tf.FilePath, t.StudentID, t.ThesisStatus
  FROM ThesisFile tf JOIN Thesis t ON t.ThesisID=tf.ThesisID
  WHERE tf.FileID=?");
$q->bind_param('i',$fileId);
$q->execute();
$q->bind_result($thesisId,$type,$path,$studentId,$status);
if (!$q->fetch()) { $q->close(); http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }
$q->close();


if ((int)$studentId !== (int)($_COOKIE['studentid'] ?? 0)) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOURS']); exit; }

if ($status !== 'Under Review') { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'WRONG_STATUS']); exit; }

$del = $conn->prepare("DELETE FROM ThesisFile WHERE FileID=?");
$del->bind_param('i',$fileId);
$ok = $del->execute();
$del->close();


echo json_encode(['ok'=>(bool)$ok]);

<?php
require_once 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$thesisId = (int)($_POST['thesis_id'] ?? 0);
$url      = trim($_POST['url'] ?? '');
$title    = trim($_POST['title'] ?? '');

if ($thesisId <= 0 || $url === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }
if (!preg_match('~^https?://~i', $url)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'URL_HTTP_ONLY']); exit; }


$studentId = (int)($_COOKIE['studentid'] ?? 0);
$st = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=? AND StudentID=?");
$st->bind_param('ii',$thesisId,$studentId);
$st->execute(); $st->bind_result($status);
if (!$st->fetch()) { $st->close(); http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOURS']); exit; }
$st->close();
if ($status !== 'Under Review') { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'WRONG_STATUS']); exit; }

$ins = $conn->prepare("INSERT INTO ThesisFile (ThesisID, FileType, FilePath, Description)
                       VALUES (?, 'Link', ?, ?)");
$ins->bind_param('iss',$thesisId,$url,$title);
if (!$ins->execute()) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$ins->error]); exit; }

echo json_encode(['ok'=>true,'id'=>$ins->insert_id]);

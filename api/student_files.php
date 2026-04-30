<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$thesisId = (int)($_GET['thesis_id'] ?? 0);
if ($thesisId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }


$studentId = (int)($_COOKIE['studentid'] ?? 0);
$chk = $conn->prepare("SELECT 1 FROM Thesis WHERE ThesisID=? AND StudentID=?");
$chk->bind_param('ii',$thesisId,$studentId);
$chk->execute(); $chk->store_result();
if ($chk->num_rows === 0) { $chk->close(); http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOURS']); exit; }
$chk->close();

$q = $conn->prepare("SELECT FileID, FileType, FilePath, COALESCE(Description,'') AS Description
                     FROM ThesisFile WHERE ThesisID=? ORDER BY FileID DESC");
$q->bind_param('i',$thesisId);
$q->execute();
$items = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

echo json_encode(['ok'=>true,'items'=>$items]);


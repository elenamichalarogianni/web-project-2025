<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$userId = (int)$_COOKIE['userid'];
$studentId = (int)($_COOKIE['studentid'] ?? 0);
if ($studentId <= 0) {
  $m = $conn->prepare("SELECT StudentID FROM Student WHERE UserID=? LIMIT 1");
  $m->bind_param('i',$userId); $m->execute(); $m->bind_result($sid); $m->fetch(); $m->close();
  $studentId = (int)$sid;
}
if ($studentId <= 0) { echo json_encode(['ok'=>false,'error'=>'NO_STUDENT']); exit; }

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
$thesisId = (int)($in['thesis_id'] ?? 0);
$profIds  = array_map('intval', (array)($in['professor_ids'] ?? []));

if ($thesisId <= 0 || empty($profIds)) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit;
}


$st = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=? AND StudentID=?");
$st->bind_param('ii',$thesisId,$studentId);
$st->execute(); $st->bind_result($status); $ok = $st->fetch(); $st->close();
if (!$ok) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOUR_THESIS']); exit; }
if ($status !== 'Under Assignment') { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'WRONG_STATUS']); exit; }

$created=0; $skipped=[];

foreach ($profIds as $pid) {
  if ($pid <= 0) continue;

  
  $dup = $conn->prepare("SELECT 1 FROM Invitation WHERE ThesisID=? AND ProfessorID=? LIMIT 1");
  $dup->bind_param('ii',$thesisId,$pid);
  $dup->execute(); $dup->store_result();
  if ($dup->num_rows > 0) { $dup->close(); $skipped[]=$pid; continue; }
  $dup->close();

  
  $ins = $conn->prepare("
    INSERT INTO Invitation (ThesisID, StudentID, ProfessorID, InvitationStatus, SentAt)
    VALUES (?, ?, ?, 'Pending', NOW())
  ");
  if ($ins && $ins->bind_param('iii', $thesisId, $studentId, $pid) && $ins->execute()) {
    $created++;
    $ins->close();
  } else {
    if ($ins) $ins->close();
    $skipped[] = $pid;
  }
}

echo json_encode(['ok'=>true,'created'=>$created,'skipped'=>$skipped]);


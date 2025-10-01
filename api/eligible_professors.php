<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';


if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$thesisId = (int)($_GET['thesis_id'] ?? 0);
if ($thesisId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }


$st = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=?");
$st->bind_param('i', $thesisId);
$st->execute(); $st->bind_result($status); $st->fetch(); $st->close();


if ($status !== 'Under Assignment') {
  echo json_encode(['ok'=>true,'items'=>[]]);
  exit;
}


$supervisorId = 0;
$q1 = $conn->prepare("SELECT SupervisorID FROM Thesis WHERE ThesisID=?");
$q1->bind_param('i',$thesisId);
$q1->execute(); $q1->bind_result($supervisorId); $q1->fetch(); $q1->close();

$already = [];

$q2 = $conn->prepare("SELECT DISTINCT ProfessorID FROM Invitation WHERE ThesisID=?");
$q2->bind_param('i',$thesisId);
$q2->execute(); $r2 = $q2->get_result();
while ($row = $r2->fetch_assoc()) { $already[(int)$row['ProfessorID']] = true; }
$q2->close();


$q3 = $conn->prepare("SELECT ProfessorID FROM ThesisCommittee WHERE ThesisID=?");
$q3->bind_param('i',$thesisId);
$q3->execute(); $r3 = $q3->get_result();
while ($row = $r3->fetch_assoc()) { $already[(int)$row['ProfessorID']] = true; }
$q3->close();


$sql = "SELECT ProfessorID, FullName, Email
        FROM Professor
        WHERE ProfessorID <> ?
        ORDER BY FullName ASC";
$s = $conn->prepare($sql);
$s->bind_param('i', $supervisorId);
$s->execute();
$res = $s->get_result();

$out = [];
while ($p = $res->fetch_assoc()) {
  $pid = (int)$p['ProfessorID'];
  if (!isset($already[$pid])) {
    $out[] = ['ProfessorID'=>$pid, 'FullName'=>$p['FullName'], 'Email'=>$p['Email'] ?? ''];
  }
}
echo json_encode(['ok'=>true,'items'=>$out]);

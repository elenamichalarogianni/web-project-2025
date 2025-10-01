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

$thesisId = (int)($_GET['thesis_id'] ?? 0);
if ($thesisId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }


$own = $conn->prepare("SELECT 1 FROM Thesis WHERE ThesisID=? AND StudentID=?");
$own->bind_param('ii',$thesisId,$studentId);
$own->execute(); $own->store_result();
if ($own->num_rows === 0) { $own->close(); http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_YOURS']); exit; }
$own->close();


$sqlCnt = "
  SELECT COUNT(DISTINCT tg.ProfessorID) AS graders
  FROM ThesisGrade tg
  LEFT JOIN Thesis t  ON t.ThesisID = tg.ThesisID
  LEFT JOIN ThesisCommittee tc
         ON tc.ThesisID = tg.ThesisID AND tc.ProfessorID = tg.ProfessorID
  WHERE tg.ThesisID = ?
    AND (tc.ProfessorID IS NOT NULL OR tg.ProfessorID = t.SupervisorID)
    AND tg.FinalScore IS NOT NULL
";
$s = $conn->prepare($sqlCnt);
$s->bind_param('i',$thesisId);
$s->execute();
$graders = (int)($s->get_result()->fetch_assoc()['graders'] ?? 0);
$s->close();
$ready = ($graders >= 3);


$repo = null;
$qr = $conn->prepare("SELECT FileID, FilePath, COALESCE(Description,'') AS Title
                      FROM ThesisFile WHERE ThesisID=? AND FileType='Repository' ORDER BY FileID DESC LIMIT 1");
$qr->bind_param('i',$thesisId); $qr->execute();
$repo = $qr->get_result()->fetch_assoc() ?: null;
$qr->close();

echo json_encode([
  'ok'=>true,
  'ready'=>$ready,
  'repository'=> $repo ? ['id'=>(int)$repo['FileID'], 'url'=>$repo['FilePath'], 'title'=>$repo['Title']] : null,
  'report_url'=> "student_exam_report.php?thesis_id=".$thesisId
]);

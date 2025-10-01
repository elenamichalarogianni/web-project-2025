<?php
require_once 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$thesisId = (int)($_POST['thesis_id'] ?? 0);
$url      = trim($_POST['url'] ?? '');
$title    = trim($_POST['title'] ?? 'Repository');

if ($thesisId <= 0 || $url === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit; }
if (!preg_match('~^https?://~i', $url)) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'URL_HTTP_ONLY']); exit; }


$studentId = (int)($_COOKIE['studentid'] ?? 0);
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
$s->execute(); $graders = (int)($s->get_result()->fetch_assoc()['graders'] ?? 0);
$s->close();
if ($graders < 3) { http_response_code(409); echo json_encode(['ok'=>false,'error'=>'NOT_READY']); exit; }


$conn->query("DELETE FROM ThesisFile WHERE ThesisID=".(int)$thesisId." AND FileType='Repository'");


$ins = $conn->prepare("INSERT INTO ThesisFile (ThesisID, FileType, FilePath, Description) VALUES (?, 'Repository', ?, ?)");
$ins->bind_param('iss',$thesisId,$url,$title);
if (!$ins->execute()) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$ins->error]); exit; }

echo json_encode(['ok'=>true,'id'=>$ins->insert_id,'url'=>$url]);

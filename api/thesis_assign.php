<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}
$userId = (int)($_COOKIE['userid'] ?? 0);


$profId = 0;
$q = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID=? LIMIT 1");
$q->bind_param('i', $userId); $q->execute(); $q->bind_result($pid); $q->fetch(); $q->close();
$profId = (int)$pid;
if ($profId <= 0) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NO_PROFESSOR_ID']); exit; }

$topicId   = (int)($_POST['topic_id'] ?? 0);
$studentId = (int)($_POST['student_id'] ?? 0);
if ($topicId <= 0 || $studentId <= 0) { echo json_encode(['ok'=>false,'error'=>'MISSING_DATA']); exit; }


$t = $conn->prepare("SELECT Title, Summary, COALESCE(PDFpath,'') AS PDFpath
                     FROM ThesisTopic WHERE TopicID=? AND ProfessorID=? LIMIT 1");
$t->bind_param('ii', $topicId, $profId);
$t->execute();
$topic = $t->get_result()->fetch_assoc();
$t->close();
if (!$topic) { echo json_encode(['ok'=>false,'error'=>'TOPIC_NOT_FOUND_OR_NOT_OWNER']); exit; }

$title = trim((string)($topic['Title'] ?? ''));
$desc  = (string)($topic['Summary'] ?? '');
$link  = (string)($topic['PDFpath'] ?? '');


$dupTitle = $conn->prepare("SELECT ThesisID FROM Thesis WHERE TRIM(Title)=TRIM(?) LIMIT 1");
$dupTitle->bind_param("s", $title);
$dupTitle->execute(); $dupTitle->bind_result($existingByTitle); $dupTitle->fetch(); $dupTitle->close();
if (!empty($existingByTitle)) {
  echo json_encode(['ok'=>false,'error'=>'TITLE_EXISTS','thesis_id'=>(int)$existingByTitle]); exit;
}


$dupStudentAny = $conn->prepare("SELECT ThesisID FROM Thesis WHERE StudentID=? LIMIT 1");
$dupStudentAny->bind_param("i", $studentId);
$dupStudentAny->execute(); $dupStudentAny->bind_result($existingForStudentAny); $dupStudentAny->fetch(); $dupStudentAny->close();
if (!empty($existingForStudentAny)) {
  echo json_encode(['ok'=>false,'error'=>'STUDENT_EXISTS','thesis_id'=>(int)$existingForStudentAny]); exit;
}


$dupTopicOngoing = $conn->prepare("SELECT ThesisID FROM Thesis
  WHERE TopicID=? AND ThesisStatus IN ('Under Assignment','Active','Under Review') LIMIT 1");
$dupTopicOngoing->bind_param("i", $topicId);
$dupTopicOngoing->execute(); $dupTopicOngoing->bind_result($existingTopicOngoing); $dupTopicOngoing->fetch(); $dupTopicOngoing->close();
if (!empty($existingTopicOngoing)) {
  echo json_encode(['ok'=>false,'error'=>'TOPIC_ONGOING','thesis_id'=>(int)$existingTopicOngoing]); exit;
}


$ins = $conn->prepare("INSERT INTO Thesis
  (TopicID, Title, ThesisDescription, Link, ThesisStatus, StudentID, SupervisorID)
  VALUES (?, ?, ?, ?, 'Under Assignment', ?, ?)");
$ins->bind_param("isssii", $topicId, $title, $desc, $link, $studentId, $profId);
if (!$ins->execute()) {
  http_response_code(500); echo json_encode(['ok'=>false,'error'=>'INSERT_FAILED']); exit;
}
$newId = (int)$ins->insert_id;
$ins->close();

echo json_encode(['ok'=>true,'thesis_id'=>$newId], JSON_UNESCAPED_UNICODE);

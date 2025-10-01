<?php

require_once 'db_connect.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_COOKIE['fullname']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
    header("Location: index.php"); exit();
}


$professorID = (int)($_COOKIE['professorid'] ?? 0);
if ($professorID <= 0) { http_response_code(403); exit('Invalid professor.'); }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405); exit('Method Not Allowed');
}

$thesisId = (int)($_POST['ThesisID'] ?? 0);
$quality  = isset($_POST['QualityAndGoals'])   ? (float)$_POST['QualityAndGoals']   : -1;
$duration = isset($_POST['DurationScore'])     ? (float)$_POST['DurationScore']     : -1;
$text     = isset($_POST['TextCompleteness'])  ? (float)$_POST['TextCompleteness']  : -1;
$present  = isset($_POST['PresentationScore']) ? (float)$_POST['PresentationScore'] : -1;


if ($thesisId <= 0) { http_response_code(400); exit('Missing ThesisID.'); }

foreach ([
  'Quality & Goals' => $quality,
  'Duration' => $duration,
  'Text completeness' => $text,
  'Presentation' => $present
] as $label => $val) {
  if (!is_numeric($val) || $val < 0 || $val > 10) {
    http_response_code(400);
    exit("$label must be between 0 and 10.");
  }
}


$final = ($quality * 0.60) + ($duration * 0.15) + ($text * 0.15) + ($present * 0.10);


$sql = "
INSERT INTO ThesisGrade
  (ThesisID, ProfessorID, QualityAndGoals, DurationScore, TextCompleteness, PresentationScore, FinalScore)
VALUES
  (?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  QualityAndGoals   = VALUES(QualityAndGoals),
  DurationScore     = VALUES(DurationScore),
  TextCompleteness  = VALUES(TextCompleteness),
  PresentationScore = VALUES(PresentationScore),
  FinalScore        = VALUES(FinalScore)
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  exit('Prepare failed: ' . $conn->error);
}

if (!$stmt->bind_param(
  'iiddddd',
  $thesisId,
  $professorID,
  $quality,
  $duration,
  $text,
  $present,
  $final
)) {
  http_response_code(500);
  exit('Bind failed: ' . $stmt->error);
}

if (!$stmt->execute()) {
  http_response_code(500);
  exit('Execute failed: ' . $stmt->error);
}

$stmt->close();

echo 'Grade saved successfully.';
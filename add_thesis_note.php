<?php
require_once 'db_connect.php';
$wantsJson = (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
          || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');

if (($_COOKIE['role'] ?? '') !== 'Professor' || empty($_COOKIE['professorid'])) {
  if ($wantsJson){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); }
  else { echo "Unauthorized access!"; }
  exit;
}

$professorID = (int)$_COOKIE['professorid'];
$thesisID = (int)($_POST['thesis_id'] ?? 0);
$noteText = trim($_POST['noteText'] ?? '');
if ($thesisID <= 0 || $noteText === '') {
  if ($wantsJson){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); }
  else { echo "Invalid input!"; }
  exit;
}
$noteText = mb_substr($noteText, 0, 300);

$stmt = $conn->prepare("INSERT INTO ThesisNote (ThesisID, ProfessorID, NoteText) VALUES (?,?,?)");
$stmt->bind_param("iis",$thesisID,$professorID,$noteText);
$ok = $stmt->execute(); $stmt->close();

if ($wantsJson){
  if ($ok) echo json_encode(['ok'=>true]);
  else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); }
  exit;
}

if ($ok){ header("Location: thesis_details.php?thesisid=".$thesisID); exit; }
echo "Error.";


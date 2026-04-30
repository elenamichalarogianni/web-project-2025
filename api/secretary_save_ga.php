<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';

if (!isset($_COOKIE['email']) || (($_COOKIE['role'] ?? '') !== 'Secretary')) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$thesisID = (int)($_POST['thesis_id'] ?? 0);
$gaNum    = (int)($_POST['ga_number'] ?? 0);
$gaYear   = (int)($_POST['ga_year']   ?? 0);

if ($thesisID <= 0 || $gaNum <= 0 || $gaYear < 2000 || $gaYear > 2100) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); exit;
}


$st = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=?");
$st->bind_param('i', $thesisID);
$st->execute();
$res = $st->get_result();
$row = $res->fetch_assoc();
$st->close();

if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); exit; }
if (($row['ThesisStatus'] ?? '') !== 'Active') {
  http_response_code(409); echo json_encode(['ok'=>false,'error'=>'NOT_ACTIVE']); exit;
}

$userId = isset($_COOKIE['userid']) ? (int)$_COOKIE['userid'] : null;


$sql = "
  INSERT INTO ThesisAssignmentGA (ThesisID, GA_Number, GA_Year, RecordedAt, RecordedBy)
  VALUES (?, ?, ?, NOW(), ?)
  ON DUPLICATE KEY UPDATE
    GA_Number = VALUES(GA_Number),
    GA_Year   = VALUES(GA_Year),
    RecordedAt= NOW(),
    RecordedBy= VALUES(RecordedBy)
";
$u = $conn->prepare($sql);
$u->bind_param('iiii', $thesisID, $gaNum, $gaYear, $userId);
$ok = $u->execute();
$u->close();

if (!$ok) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); exit; }


$t = $conn->prepare("INSERT INTO ThesisTimeline (ThesisID, ThesisStatus, ActionDate) VALUES (?, 'GA Recorded', NOW())");
$t->bind_param('i', $thesisID);
$t->execute();
$t->close();

echo json_encode(['ok'=>true]);


<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';

if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(401); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}
$userId = (int)($_COOKIE['userid'] ?? 0);


$profId = 0;
$q = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID=? LIMIT 1");
$q->bind_param('i', $userId); $q->execute(); $q->bind_result($pid); $q->fetch(); $q->close();
$profId = (int)$pid;
if ($profId <= 0) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NO_PROFESSOR_ID']); exit; }

$sql = "SELECT tt.TopicID, tt.Title, tt.Summary, tt.PDFpath
        FROM ThesisTopic tt
        WHERE tt.ProfessorID = ?
          AND NOT EXISTS (SELECT 1 FROM Thesis th WHERE th.TopicID = tt.TopicID
                          AND th.ThesisStatus IN ('Under Assignment','Active','Under Review'))
        ORDER BY tt.Title ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $profId);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($r = $res->fetch_assoc()) { $items[] = $r; }
echo json_encode(['ok'=>true, 'items'=>$items], JSON_UNESCAPED_UNICODE);


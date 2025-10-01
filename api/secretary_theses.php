<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if (!isset($_COOKIE['email']) || (($_COOKIE['role'] ?? '') !== 'Secretary')) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$sql = "
  SELECT ThesisID, Title, ThesisStatus, AssignmentDate, StudentID, SupervisorID, TopicID, Link
  FROM Thesis
  WHERE ThesisStatus IN ('Under Assignment','Active','Under Review')
  ORDER BY FIELD(ThesisStatus,'Under Assignment','Active','Under Review'),
           AssignmentDate DESC
";
$res = $conn->query($sql);
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

echo json_encode(['ok'=>true,'items'=>$rows], JSON_UNESCAPED_UNICODE);

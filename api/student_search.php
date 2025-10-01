<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';

if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); exit;
}

$q = trim($_REQUEST['q'] ?? '');
if ($q === '') { echo json_encode(['ok'=>true,'items'=>[]]); exit; }


$like = "%{$q}%";
$sql = "
  SELECT s.StudentID, s.FullName, s.AM, s.Email
  FROM Student s
  WHERE
    (
      s.FullName     LIKE ? OR
      s.Email        LIKE ? OR
      CAST(s.AM AS CHAR) LIKE ? OR
      s.MobilePhone  LIKE ? OR
      s.LandlinePhone LIKE ?
    )
    AND NOT EXISTS (SELECT 1 FROM Thesis t WHERE t.StudentID = s.StudentID)
  ORDER BY s.FullName ASC
  LIMIT 50
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'PREPARE_FAILED']); exit;
}

$stmt->bind_param('sssss', $like, $like, $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($r = $res->fetch_assoc()) { $items[] = $r; }
$stmt->close();

echo json_encode(['ok'=>true, 'items'=>$items], JSON_UNESCAPED_UNICODE);

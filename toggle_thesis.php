<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connect.php';

ini_set('display_errors', '0');


if (($_COOKIE['role'] ?? '') !== 'Professor' || empty($_COOKIE['professorid'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}
$professorID = (int)$_COOKIE['professorid'];


$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$thesisId = isset($data['thesis_id']) ? (int)$data['thesis_id'] : 0;
$enabled  = array_key_exists('enabled', $data) ? (int)!!$data['enabled'] : null;

if ($thesisId <= 0 || $enabled === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Bad input']);
    exit;
}


$chk = $conn->prepare("SELECT SupervisorID FROM Thesis WHERE ThesisID = ?");
$chk->bind_param("i", $thesisId);
$chk->execute();
$chk->bind_result($supervisorId);
if (!$chk->fetch()) {
    $chk->close();
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Thesis not found']);
    exit;
}
$chk->close();

if ((int)$supervisorId !== $professorID) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only supervisor can toggle']);
    exit;
}


$stmt = $conn->prepare("
  INSERT INTO enable_grade (ThesisID, Enabled)
  VALUES (?, ?)
  ON DUPLICATE KEY UPDATE Enabled = VALUES(Enabled)
");
$stmt->bind_param("ii", $thesisId, $enabled);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'thesis_id' => $thesisId, 'enabled' => $enabled]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $stmt->error]);
}
$stmt->close();

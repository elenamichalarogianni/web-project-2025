<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../app/config/db_connect.php';


if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHORIZED']); 
  exit;
}
$userId = (int)($_COOKIE['userid'] ?? 0);


$profId = 0;
$stm = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID = ?");
$stm->bind_param('i', $userId);
$stm->execute();
$stm->bind_result($tmp);
$stm->fetch();
$stm->close();
$profId = $tmp ? (int)$tmp : 0;

if ($profId <= 0) { 
  http_response_code(403); 
  echo json_encode(['ok' => false, 'error' => 'NO_PROFESSOR_RECORD']); 
  exit; 
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  
  $sql = "
    SELECT i.InvitationID,
           s.FullName       AS StudentName,
           t.ThesisDescription
    FROM Invitation i
    JOIN Thesis  t ON t.ThesisID  = i.ThesisID
    JOIN Student s ON s.StudentID = i.StudentID
    WHERE i.ProfessorID = ? AND i.InvitationStatus = 'Pending'
    ORDER BY i.SentAt DESC
  ";
  $st = $conn->prepare($sql);
  $st->bind_param('i', $profId);
  $st->execute();
  $res   = $st->get_result();
  $items = $res->fetch_all(MYSQLI_ASSOC);
  echo json_encode(['ok' => true, 'items' => $items]); 
  exit;
}

if ($method === 'POST') {
  
  $invId  = (int)($_POST['invitation_id'] ?? 0);
  $action = $_POST['action'] ?? ($_POST['new_status'] ?? '');

  if (!$invId || !in_array($action, ['Accepted','Rejected'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'BAD_REQUEST']); 
    exit;
  }

  
  $chk = $conn->prepare("SELECT 1 FROM Invitation WHERE InvitationID = ? AND ProfessorID = ?");
  $chk->bind_param('ii', $invId, $profId);
  $chk->execute();
  $own = $chk->get_result()->fetch_row();
  $chk->close();
  if (!$own) { 
    http_response_code(403); 
    echo json_encode(['ok' => false, 'error' => 'FORBIDDEN']); 
    exit; 
  }

  
  $ins = $conn->prepare("INSERT INTO InvitationAction (InvitationID, NewStatus) VALUES (?, ?)");
  $ins->bind_param('is', $invId, $action);
  $ok = $ins->execute();
  $ins->close();

  echo json_encode(['ok' => (bool)$ok]); 
  exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);


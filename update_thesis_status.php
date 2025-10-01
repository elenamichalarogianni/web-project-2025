<?php
require_once 'db_connect.php';

$wantsJson = (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
          || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');


if (($_COOKIE['role'] ?? '') !== 'Professor' || empty($_COOKIE['professorid'])) {
  if ($wantsJson){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'UNAUTHORIZED']); }
  else { header("Location: index.php"); }
  exit;
}

$professorID = (int)$_COOKIE['professorid'];
$thesisID    = (int)($_POST['thesis_id'] ?? 0);
$newStatus   = $_POST['new_status'] ?? '';

if ($thesisID <= 0 || $newStatus === '') {
  if ($wantsJson){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'BAD_INPUT']); }
  else { echo "Invalid input!"; }
  exit;
}


$q = $conn->prepare("SELECT SupervisorID FROM Thesis WHERE ThesisID = ?");
$q->bind_param("i",$thesisID); $q->execute();
$th = $q->get_result()->fetch_assoc(); $q->close();
if (!$th){
  if ($wantsJson){ http_response_code(404); echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']); }
  else { echo "Thesis not found!"; }
  exit;
}
if ((int)$th['SupervisorID'] !== $professorID){
  if ($wantsJson){ http_response_code(403); echo json_encode(['ok'=>false,'error'=>'NOT_SUPERVISOR']); }
  else { echo "You are not the supervisor of this thesis!"; }
  exit;
}


$upd = $conn->prepare("UPDATE Thesis SET ThesisStatus=? WHERE ThesisID=?");
$upd->bind_param("si",$newStatus,$thesisID);
$ok = $upd->execute();
$upd->close();

if ($wantsJson){
  if ($ok) echo json_encode(['ok'=>true,'thesis_id'=>$thesisID,'new_status'=>$newStatus]);
  else { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'DB_ERROR']); }
  exit;
}


if ($ok){ header("Location: thesis_details.php?thesisid=".$thesisID); }
else { echo "Error updating."; }

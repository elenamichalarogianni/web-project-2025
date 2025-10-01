<?php
require_once 'db_connect.php'; 
header('Content-Type: text/html; charset=utf-8');

if (!isset($_COOKIE['userid']) || !in_array(($_COOKIE['role'] ?? ''), ['Professor','Secretary'], true)) {
  http_response_code(403); die('Unauthorized');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405); die('Invalid method');
}

$thesisID        = (int)($_POST['thesis_id'] ?? 0);
$assemblyNumber  = (int)($_POST['assembly_number'] ?? 0);
$assemblyYear    = (int)($_POST['assembly_year'] ?? 0);
$reason          = trim($_POST['reason'] ?? 'Following students request');

if ($thesisID <= 0 || $assemblyNumber <= 0 || $assemblyYear < 2000 || $assemblyYear > 2100) {
  http_response_code(400); die('Λάθος δεδομένα.');
}
if ($reason === '' || mb_strlen($reason) > 255) {
  http_response_code(400); die('Λάθος reason (0<length<=255).');
}


$own = $conn->prepare("SELECT ThesisStatus FROM Thesis WHERE ThesisID=?");
$own->bind_param("i", $thesisID);
$own->execute();
$res = $own->get_result();
$row = $res->fetch_assoc();
$own->close();
if (!$row) { http_response_code(404); die('thesis not found'); }


$ins = $conn->prepare("
  INSERT INTO ReviewCancellation (ThesisID, Reason, AssemblyNumber, AssemblyYear)
  VALUES (?, ?, ?, ?)
");
$ins->bind_param("isii", $thesisID, $reason, $assemblyNumber, $assemblyYear);
if (!$ins->execute()) { http_response_code(500); die('DB error: '.$ins->error); }
$ins->close();


$conn->begin_transaction();
try {
  $up1 = $conn->prepare("UPDATE Thesis SET ThesisStatus='Cancelled' WHERE ThesisID=? AND ThesisStatus<>'Cancelled'");
  $up1->bind_param("i", $thesisID);
  $up1->execute();
  $changed = ($up1->affected_rows > 0);
  $up1->close();

  
  $ti = $conn->prepare("INSERT INTO ThesisTimeline (ThesisID, ThesisStatus, ActionDate) VALUES (?, 'Cancelled', NOW())");
  $ti->bind_param('i', $thesisID);
  $ti->execute();
  $ti->close();

  $conn->commit();
} catch (Throwable $e) {
  $conn->rollback();
  http_response_code(500); die('DB error: '.$e->getMessage());
}

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'];
$base    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); 
$redirect = $scheme.'://'.$host.$base.'/sec_thesis_details.php'
          . '?thesisid=' . urlencode((string)$thesisID)
          . '&msg=cancel_saved';

header("Location: $redirect", true, 303);
exit;

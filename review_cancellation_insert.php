<?php
require_once 'db_connect.php'; 

header('Content-Type: text/html; charset=utf-8');

if (!isset($_COOKIE['professorid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(403); die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); die('Invalid method');
}

$thesisID        = (int)($_POST['thesis_id'] ?? 0);
$assemblyNumber  = (int)($_POST['assembly_number'] ?? 0);
$assemblyYear    = (int)($_POST['assembly_year'] ?? 0);
$reason          = trim($_POST['reason'] ?? 'By the supervisor');

if ($thesisID <= 0 || $assemblyNumber <= 0 || $assemblyYear < 2000 || $assemblyYear > 2100) {
  http_response_code(400); die('Λάθος δεδομένα.');
}
if ($reason === '' || mb_strlen($reason) > 255) {
  http_response_code(400); die('Λάθος reason (0<length<=255).');
}

$professorID = (int)($_COOKIE['professorid'] ?? 0);
$own = $conn->prepare("SELECT ThesisID FROM Thesis WHERE ThesisID=? AND SupervisorID=?");
$own->bind_param("ii", $thesisID, $professorID);
$own->execute(); $own->store_result();
if ($own->num_rows === 0) { $own->close(); http_response_code(403); die('Not your thesis'); }
$own->close();

$ins = $conn->prepare("
  INSERT INTO ReviewCancellation (ThesisID, Reason, AssemblyNumber, AssemblyYear)
  VALUES (?, ?, ?, ?)
");
$ins->bind_param("isii", $thesisID, $reason, $assemblyNumber, $assemblyYear);

if (!$ins->execute()) {
  http_response_code(500); die('DB error: '.$ins->error);
}
$ins->close();


$up1 = $conn->prepare("UPDATE Thesis SET ThesisStatus='Cancelled' WHERE ThesisID=?");
$up1->bind_param("i", $thesisID);
$up1->execute();
$up1->close();

$up2 = $conn->prepare("INSERT INTO ThesisTimeline (ThesisID, ThesisStatus) VALUES (?, 'Cancelled')");
$up2->bind_param("i", $thesisID);
$up2->execute();
$up2->close();

$conn->close();

header("Location: activethesis.php?msg=cancel_saved");
exit;
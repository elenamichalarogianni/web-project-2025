<?php
require_once 'db_connect.php';


header('Content-Type: text/plain; charset=utf-8');


if (($_COOKIE['role'] ?? '') !== 'Professor') {
  http_response_code(403);
  echo "Unauthorized";
  exit;
}

$professorID = (int)($_COOKIE['professorid'] ?? 0);

if ($professorID <= 0) {
  http_response_code(403);
  echo "Unauthorized";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Invalid method";
  exit;
}

$thesisID = (int)($_POST['thesis_id'] ?? 0);
if ($thesisID <= 0) {
  http_response_code(400);
  echo "Invalid Thesis ID";
  exit;
}


$stmt = $conn->prepare("UPDATE Thesis SET ThesisStatus = 'Cancelled' WHERE ThesisID = ?");
$stmt->bind_param("i", $thesisID);

if ($stmt->execute()) {
  if ($stmt->affected_rows > 0) {
    echo "Η ενέργεια αποθηκεύτηκε επιτυχώς.";
  } else {
    
    echo "Καμία αλλαγή (ίσως έχει ήδη ακυρωθεί).";
  }
} else {
  http_response_code(500);
  echo "Σφάλμα: " . $stmt->error;
}

$stmt->close();
$conn->close();
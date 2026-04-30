<?php
require_once __DIR__ . '/../app/config/db_connect.php'; 

if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(403); die('Unauthorized');
}

$professorID = (int)($_COOKIE['professorid'] ?? 0);
$thesisID    = (int)($_GET['thesis_id'] ?? 0);
if ($thesisID <= 0) { http_response_code(400); die('Invalid thesis id'); }

$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];

$sql = "SELECT ThesisID, Title, ThesisStatus, SupervisorID FROM Thesis WHERE ThesisID = ?";
$st  = $conn->prepare($sql);
$st->bind_param("i", $thesisID);
$st->execute();
$res = $st->get_result();
$thesis = $res->fetch_assoc();
$st->close();

if (!$thesis) { http_response_code(404); die('Thesis not found'); }
if ((int)$thesis['SupervisorID'] !== $professorID) { http_response_code(403); die('Not your thesis'); }
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet"
      href="assets/css/userformat.css?v=<?= is_file(__DIR__.'/assets/css/userformat.css')
           ? filemtime(__DIR__.'/assets/css/userformat.css') : time() ?>">
<link rel="stylesheet"
      href="assets/css/cancel_reason.css?v=<?= is_file(__DIR__.'/assets/css/cancel_reason.css')
           ? filemtime(__DIR__.'/assets/css/cancel_reason.css') : time() ?>">

<title>Thesis Details</title>
</head>
<body>

<div class="blue-bar-left">
  <nav class="side-menu">
    <a href="professor.php">Προβολή και Δημιουργία Θεμάτων προς Ανάθεση</a>
    <a href="assignthesis.php" class="active">Ανάθεση Θέματος</a>
    <a href="activethesis.php">Λίστα Διπλωματικών</a>
    <a href="invitations.php">Προσκλήσεις</a>
    <a href="stats.php">Στατιστικά</a>
  </nav>
</div>

<div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>
<body>
  <div class="form-card">
    <h2>Cancellation of Active Thesis</h2>
    <p><strong>Thesis:</strong> <?= htmlspecialchars($thesis['Title']) ?> (ID: <?= (int)$thesisID ?>)</p>

    <form action="review_cancellation_insert.php" method="post">
      <input type="hidden" name="thesis_id" value="<?= (int)$thesisID ?>">

      <div class="form-row">
        <label for="assembly_number">General Assembly Number</label>
        <input type="number" id="assembly_number" name="assembly_number" required min="1" step="1">
      </div>

      <div class="form-row">
        <label for="assembly_year">General Assembly Year</label>
        <input type="number" id="assembly_year" name="assembly_year" required min="2000" max="2100" step="1">
      </div>

      <div class="form-row">
        <label for="reason">Cancellation Reason</label>
        <input type="text" id="reason" name="reason" maxlength="255" value="By the supervisor">
       
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-primary">Submit</button> 
      </div>
    </form>
  </div>
</body>
</html>


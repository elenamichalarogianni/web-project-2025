<?php
require_once 'db_connect.php'; 

$role = $_COOKIE['role'] ?? '';
if (!isset($_COOKIE['userid']) || !in_array($role, ['Professor','Secretary'], true)) {
  http_response_code(403); die('Unauthorized');
}

$thesisID = (int)($_GET['thesis_id'] ?? 0);
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


$canCancel = ($thesis['ThesisStatus'] === 'Active');
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cancellation</title>
  <link rel="stylesheet" href="assets/css/userformat.css">
  <link rel="stylesheet" href="assets/css/secretary.css">
  <link rel="stylesheet" href="assets/css/sec_thesis_details.css?v=<?=time()?>">
  <link rel="stylesheet" href="assets/css/sec_cancel_reason.css?v=<?=time()?>">
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="secretary.php" class="active">Theses List</a>
      <a href="sec_import_info.php">User Info</a>
    </nav>
  </div>

  <div class="blue-bar-top">
    <span class="username"><?= htmlspecialchars($fullname) ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="form-card" style="margin-top: 100px;">
    <h2>Cancellation of Active Thesis</h2>
    <p><strong>Thesis:</strong> <?= htmlspecialchars($thesis['Title']) ?> (ID: <?= (int)$thesisID ?>)</p>

    <?php if (!$canCancel): ?>
      <p><em>Η διπλωματική δεν είναι σε κατάσταση <b>Active</b>, οπότε δεν μπορεί να ακυρωθεί.</em></p>
      <div class="actions">
        <a href="sec_thesis_details.php?thesisid=<?= (int)$thesisID ?>" class="btn btn-light">Back</a>
      </div>
    <?php else: ?>
      <form action="sec_review_cancellation.php" method="post">
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
          <input type="text" id="reason" name="reason" maxlength="255" value="Following students request">
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-primary">Submit</button>
          <a href="sec_thesis_details.php?thesisid=<?= (int)$thesisID ?>" class="btn btn-light">Back</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>

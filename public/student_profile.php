<?php
require_once __DIR__ . '/../app/config/db_connect.php';
if (!isset($_COOKIE['email']) || (($_COOKIE['role'] ?? null) !== 'Student')) {
  header("Location: index.php"); exit;
}
$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Επεξεργασία Προφίλ</title>
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css'); ?>">
  <link rel="stylesheet" href="assets/css/student_profile.css?v=<?php echo filemtime('assets/css/student_profile.css'); ?>">
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="student.php">Προβολή Θέματος</a>
      <a href="student_profile.php" class="active">Επεξεργασία Προφίλ</a>
    </nav>
  </div>

  <div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="profile-card" aria-live="polite">
    <h2>Στοιχεία Επικοινωνίας</h2>

    <label for="pr-address">Πλήρης Διεύθυνση</label>
    <textarea id="pr-address" rows="3" placeholder="Οδός, αριθμός, ΤΚ, Πόλη"></textarea>

    <label for="pr-email">Email επικοινωνίας</label>
    <input id="pr-email" type="email" placeholder="name@example.com">

    <label for="pr-mobile">Κινητό</label>
    <input id="pr-mobile" type="tel" placeholder="+30 6X XXX XXXX">

    <label for="pr-landline">Σταθερό</label>
    <input id="pr-landline" type="tel" placeholder="+30 2X XXX XXXX">

    <div class="profile-actions">
      <button id="pr-save" class="btn" type="button">Αποθήκευση</button>
    </div>
  </div>

  <script src="assets/js/studentprofile.js?v=<?php echo time(); ?>"></script>
</body>
</html>



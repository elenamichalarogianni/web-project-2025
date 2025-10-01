<?php
require_once 'db_connect.php';
if (!isset($_COOKIE['email']) || (($_COOKIE['role'] ?? '') !== 'Secretary')) {
  header("Location: index.php"); exit;
}
$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Theses</title>
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css'); ?>">
  <link rel="stylesheet" href="assets/css/secretary.css?v=<?php echo filemtime('assets/css/secretary.css'); ?>">
  <link rel="stylesheet" href="assets/css/secretary_thesis.css?v=<?php echo filemtime('assets/css/secretary_thesis.css'); ?>">
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="secretary.php" class="active">Theses List</a>
      <a href="sec_import_info.php">User Info</a>
    </nav>
  </div>

  <div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <h1 class="sec-page-title" style="margin: 0; padding: 45px;">
    Theses (Under Assignment / Active / Under Review)
  </h1>

  
  <div id="sec-theses" style="padding: 0 45px;"></div>

  <script src="assets/js/secretary_theses.js?v=<?= is_file(__DIR__.'/assets/js/secretary_theses.js')
     ? filemtime(__DIR__.'/assets/js/secretary_theses.js') : time() ?>" defer></script>
</body>
</html>

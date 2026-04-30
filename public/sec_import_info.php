<?php
declare(strict_types=1);
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');


if (($_COOKIE['role'] ?? '') !== 'Secretary') {
    http_response_code(403);
    echo 'Forbidden: Secretary access only.';
    exit;
}


$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'] ?? 'Secretary';


if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Info Import</title>
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css'); ?>">
  <link rel="stylesheet" href="assets/css/secretary.css?v=<?php echo filemtime('assets/css/secretary.css'); ?>">
  <link rel="stylesheet" href="assets/css/secretary_thesis.css?v=<?php echo filemtime('assets/css/secretary_thesis.css'); ?>">
  <link rel="stylesheet" href="assets/css/sec_import_info.css?v=<?php echo filemtime('assets/css/sec_import_info.css'); ?>">
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="secretary.php">Theses List</a>
      <a href="sec_import_info.php" class="active">User Info</a>
    </nav>
  </div>

  <div class="blue-bar-top">
    <span class="username"><?= htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="card">
    <h1>Import from Public JSON</h1>
    <p class="hint">Paste a publicly accessible JSON URL and press “Run Import”.</p>

    <form id="importForm" method="post" action="./professor_student_info.php">
      <div class="row">
        <label for="url">JSON URL</label>
        <input id="url" name="url" type="url" required
               placeholder="http://usidas.ceid.upatras.gr/web/2024/export.php"
               value="http://usidas.ceid.upatras.gr/web/2024/export.php"
               pattern="https?://.*">
      </div>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit">Run Import</button>
    </form>

    <div id="output" class="result" hidden></div>
  </div>

<script src="assets/js/sec_import_info.js?v=<?=time()?>" defer></script>

</body>
</html>

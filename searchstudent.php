<?php
if (!isset($_COOKIE['fullname']) && !isset($_COOKIE['email'])) { header("Location: index.php"); exit; }
$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];
$topicId  = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
if ($topicId <= 0) { http_response_code(400); echo "No Topic ID provided!"; exit; }
setcookie("topicid", (string)$topicId, time() + 3600, "/");
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Topic #<?= (int)$topicId ?></title>
  <link rel="stylesheet" href="assets/css/userformat.css">
  <link rel="stylesheet" href="assets/css/assignthesis.css">
  <link rel="stylesheet" href="assets/css/searchstudent.css">
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
    <span class="username"><?= htmlspecialchars($fullname) ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="search-panel">
    <form class="search-form" id="searchForm" onsubmit="return false;" novalidate>
      <input type="text" id="search" name="q" class="search-input" placeholder="name, AM or email">
      <button type="button" id="searchBtn" class="search-btn">Search</button>
    </form>
  </div>

  <div id="resultsBox"></div>

  <div class="thesis-details" id="thesisBox">
    <div class="thesis-content">
      <h2>Topic #<?= (int)$topicId ?></h2>
      <div id="assignMessage" class="notice" style="display:none;"></div>
    </div>
    <div class="thesis-footer">
      <button type="button" class="submit-btn" id="assignBtn" disabled>Submit</button>
    </div>
  </div>

  <script>
    window.TOPIC_ID = <?= (int)$topicId ?>;
  </script>
  <script src="assets/js/searchstudent.js?v=<?=filemtime('assets/js/searchstudent.js')?>" defer></script>
</body>
</html>

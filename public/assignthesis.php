<?php
if (($_COOKIE['role'] ?? '') !== 'Professor') { header('Location: index.php'); exit; }
$fullname = $_COOKIE['fullname'] ?? ($_COOKIE['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Assign Theses</title>
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css')?>">
  <link rel="stylesheet" href="assets/css/assignthesis.css?v=<?php echo filemtime('assets/css/assignthesis.css')?>">
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

  <div class="topics-list">
    <h2>Available Thesis Topics</h2>
    <ul id="assignTopics"></ul>
    <div id="assignEmpty" class="empty-state" style="display:none;">No available topics.</div>
  </div>

  <script src="assets/js/assignthesis.js" defer></script>
</body>
</html>


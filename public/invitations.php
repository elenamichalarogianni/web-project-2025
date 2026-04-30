<?php

if (!isset($_COOKIE['email']) || (($_COOKIE['role'] ?? null) !== 'Professor')) {
  header('Location: index.php'); exit;
}
$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css'); ?>">
  <link rel="stylesheet" href="assets/css/invitationformat.css?v=<?php echo filemtime('assets/css/invitationformat.css'); ?>">
  <title>Invitations</title>
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="professor.php">Προβολή και Δημιουργία Θεμάτων προς Ανάθεση</a>
      <a href="assignthesis.php">Ανάθεση Θέματος</a>
      <a href="activethesis.php">Λίστα Διπλωματικών</a>
      <a href="invitations.php" class="active">Προσκλήσεις</a>
      <a href="stats.php">Στατιστικά</a>
    </nav>
  </div>

  <div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="invitations">
    <h1>Invitations to three-member committee</h1>
    <ul id="invList"></ul>
    <div id="invMsg"></div>
  </div>

  <script src="assets/js/invitations.js?v=<?php echo filemtime('assets/js/invitations.js'); ?>"></script>
</body>
</html>


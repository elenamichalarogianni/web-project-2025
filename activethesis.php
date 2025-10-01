<?php
require_once 'db_connect.php';


if (($_COOKIE['role'] ?? '') !== 'Professor') { header("Location: index.php"); exit; }
$fullname = $_COOKIE['fullname'] ?? ($_COOKIE['email'] ?? '');



$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'] ?? '';
$professorID = (int)($_COOKIE['professorid'] ?? 0);
if ($professorID <= 0) { die('Professor record not found'); }
$base = rtrim(dirname($_SERVER['PHP_SELF']), '/'); 


$sql = "
  SELECT DISTINCT
    t.ThesisID, t.Title, t.ThesisDescription, t.Link,
    t.SupervisorID, t.ThesisStatus,
    s.FullName AS StudentName
  FROM Thesis t
  LEFT JOIN Student s ON t.StudentID = s.StudentID
  LEFT JOIN ThesisCommittee tc ON t.ThesisID = tc.ThesisID AND tc.ProfessorID = ?
  WHERE t.SupervisorID = ? OR tc.ProfessorID IS NOT NULL
  ORDER BY t.ThesisID DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $professorID, $professorID);
$stmt->execute();
$res = $stmt->get_result();
$theses = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css'); ?>">
  <link rel="stylesheet" href="assets/css/activethesis.css?v=<?php echo filemtime('assets/css/activethesis.css'); ?>">
  <title>Διπλωματικές</title>
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="professor.php">Προβολή και Δημιουργία Θεμάτων προς Ανάθεση</a>
      <a href="assignthesis.php">Ανάθεση Θέματος</a>
      <a href="activethesis.php" class="active">Λίστα Διπλωματικών</a>
      <a href="invitations.php">Προσκλήσεις</a>
      <a href="stats.php">Στατιστικά</a>
      
    </nav>
  </div>
  <div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="prof-theses-container">
    <h2>Οι Διπλωματικές μου</h2>
    <ul>
      <?php if ($theses): foreach ($theses as $row): $tid = (int)$row['ThesisID']; ?>
        <li id="thesis-<?php echo $tid; ?>">
          <a href="thesis_details.php?thesisid=<?php echo $tid; ?>">
            <strong class="student">Φοιτητής: <?php echo htmlspecialchars($row['StudentName'] ?? '—'); ?></strong>
            <strong class="title">Τίτλος: <?php echo htmlspecialchars($row['Title'] ?? ''); ?></strong>
          </a>

          <?php if (!empty($row['ThesisDescription'])): ?>
            <p><?php echo nl2br(htmlspecialchars($row['ThesisDescription'])); ?></p>
          <?php endif; ?>

          <?php if (!empty($row['Link'])): ?>
            <a href="<?php echo htmlspecialchars($row['Link']); ?>" target="_blank" class="view-btn">Προβολή</a>
          <?php endif; ?>

          <?php if ((int)$row['SupervisorID'] === $professorID): ?>
            <?php if ($row['ThesisStatus'] === 'Under Assignment'): ?>
              <button type="button" class="cancel-btn" onclick="cancelThesis(<?php echo $tid; ?>, 'Cancelled')">Ακύρωση</button>
            <?php elseif ($row['ThesisStatus'] === 'Active'): ?>
              <button type="button" class="cancel-btn" onclick="location.href='cancel_reason.php?thesis_id=<?php echo $tid; ?>'">Ακύρωση</button>
            <?php endif; ?>
          <?php endif; ?>
        </li>
      <?php endforeach; else: ?>
        <li><em>Δεν βρέθηκαν διπλωματικές.</em></li>
      <?php endif; ?>
    </ul>
  </div>

  <script src="assets/js/cancelthesis.js?v=<?= filemtime(__DIR__.'/assets/js/cancelthesis.js') ?>"></script>
</body>
</html>

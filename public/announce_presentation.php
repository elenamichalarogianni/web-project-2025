<?php
require_once __DIR__ . '/../app/config/db_connect.php';


if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  header('Location: index.php'); exit;
}
$fullname     = $_COOKIE['fullname'] ?? ($_COOKIE['email'] ?? 'User');
$profUserId   = (int)($_COOKIE['userid'] ?? 0);
$professorID  = (int)($_COOKIE['professorid'] ?? 0);


if ($professorID <= 0 && $profUserId > 0) {
  $qp = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID = ? LIMIT 1");
  $qp->bind_param("i", $profUserId);
  $qp->execute(); $qp->bind_result($pid);
  if ($qp->fetch() && $pid) { $professorID = (int)$pid; }
  $qp->close();
}


$thesisID = (int)($_GET['thesisid'] ?? $_GET['thesis_id'] ?? 0);
if ($thesisID <= 0) {
  http_response_code(400);
  echo "Missing or invalid thesis id.";
  exit;
}


$chk = $conn->prepare("SELECT SupervisorID FROM Thesis WHERE ThesisID = ?");
$chk->bind_param("i", $thesisID);
$chk->execute(); $chk->bind_result($supId);
if (!$chk->fetch()) { $chk->close(); http_response_code(404); exit('Thesis not found'); }
$chk->close();

if ((int)$supId !== $professorID) {
  http_response_code(403);
  exit('Forbidden: you are not the supervisor of this thesis.');
}


$thesisTitle = null;
$q = $conn->prepare("
  SELECT COALESCE(th.Title, tt.Title, th.ThesisDescription) AS t
  FROM Thesis th
  LEFT JOIN ThesisTopic tt ON tt.TopicID = th.TopicID
  WHERE th.ThesisID = ?
");
$q->bind_param("i", $thesisID);
if ($q->execute()) {
  $q->bind_result($t);
  if ($q->fetch()) { $thesisTitle = $t; }
}
$q->close();
if (!$thesisTitle || trim($thesisTitle) === '') {
  $thesisTitle = "Thesis #{$thesisID}";
}


$msg = null; $err = null;


$examDate = $_POST['exam_date'] ?? '';
$examTime = $_POST['exam_time'] ?? '';
$type     = $_POST['presentation_type'] ?? '';
$loc      = $_POST['location_or_link'] ?? '';
$text     = $_POST['announcement_text'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $thesisIdPost = (int)($_POST['thesis_id'] ?? 0);

  $allowedTypes = ['InPerson','Online'];
  if ($thesisIdPost <= 0)                          $err = "Invalid ThesisID.";
  elseif (trim($examDate) === '')                  $err = "Exam date is required.";
  elseif (trim($examTime) === '')                  $err = "Exam time is required.";
  elseif (!in_array($type, $allowedTypes, true))   $err = "Invalid presentation type.";
  elseif (trim($loc) === '')                       $err = "Location / Link is required.";

  if (!$err && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDate))       $err = "Exam date must be YYYY-MM-DD.";
  if (!$err && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $examTime))     $err = "Exam time must be HH:MM or HH:MM:SS.";

  if (!$err) {
    $sql = "INSERT INTO Presentation
              (ThesisID, ExamDate, ExamTime, PresentationType, LocationOrLink, AnnouncementText)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              ExamDate         = VALUES(ExamDate),
              ExamTime         = VALUES(ExamTime),
              PresentationType = VALUES(PresentationType),
              LocationOrLink   = VALUES(LocationOrLink),
              AnnouncementText = VALUES(AnnouncementText)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
      $err = "DB error (prepare): " . $conn->error;
    } else {
      $stmt->bind_param("isssss", $thesisID, $examDate, $examTime, $type, $loc, $text);
      if ($stmt->execute()) {
        $msg = "Presentation saved successfully for Thesis #{$thesisID}.";
      } else {
        $err = "DB error (execute): " . $stmt->error;
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/userformat.css?v=<?= filemtime('assets/css/userformat.css') ?>">
<link rel="stylesheet" href="assets/css/thesisdetails.css?v=<?= filemtime('assets/css/thesisdetails.css') ?>">
<link rel="stylesheet" href="assets/css/announce_presentation.css?v=<?= filemtime('assets/css/announce_presentation.css') ?>">
<title>Announce Presentation — <?= htmlspecialchars($thesisTitle) ?></title>
</head>
<body>

  <div class="blue-bar-top">
    <span class="username"><?= htmlspecialchars($fullname) ?></span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>

  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="professor.php">Προβολή και Δημιουργία Θεμάτων προς Ανάθεση</a>
      <a href="assignthesis.php">Ανάθεση Θέματος</a>
      <a href="activethesis.php">Λίστα Διπλωματικών</a>
      <a href="invitations.php">Προσκλήσεις</a>
      <a href="stats.php">Στατιστικά</a>
    </nav>
  </div>

  <div class="thesis-details">
    <h2>Announce Presentation — <?= htmlspecialchars($thesisTitle) ?></h2>

    <p><strong>Thesis:</strong> <?= htmlspecialchars($thesisTitle) ?>
      <span class="small-muted">(ID: <?= (int)$thesisID ?>)</span>
    </p>

    <?php if ($msg): ?><div class="notice ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="notice err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" class="form-grid" autocomplete="on">
      <input type="hidden" name="thesis_id" value="<?= (int)$thesisID ?>">

      <div>
        <label for="exam_date">Exam Date</label>
        <input type="date" id="exam_date" name="exam_date" value="<?= htmlspecialchars($examDate) ?>" required>
      </div>

      <div>
        <label for="exam_time">Exam Time</label>
        <input type="time" id="exam_time" name="exam_time" value="<?= htmlspecialchars($examTime) ?>" required>
      </div>

      <div>
        <label for="presentation_type">Presentation Type</label>
        <select id="presentation_type" name="presentation_type" required>
          <?php
            $sel  = $type;
            $opts = ['InPerson' => 'In Person', 'Online' => 'Online'];
            foreach ($opts as $val => $label) {
              $s = ($sel === $val) ? 'selected' : '';
              echo "<option value=\"{$val}\" {$s}>{$label}</option>";
            }
          ?>
        </select>
      </div>

      <div>
        <label for="location_or_link" id="loc_label">Location / Link</label>
        <input type="text" id="location_or_link" name="location_or_link"
               placeholder="e.g., Room B12 or https://zoom.us/..."
               value="<?= htmlspecialchars($loc) ?>" required>
      </div>

      <div class="full">
        <label for="announcement_text">Announcement Text <span class="small-muted">(optional)</span></label>
        <textarea id="announcement_text" name="announcement_text"
                  placeholder="Any notes for the announcement..."><?= htmlspecialchars($text) ?></textarea>
      </div>

      <div class="actions full">
        <button type="submit" class="supervisor-btn">Save Presentation</button>
        <a class="supervisor-btn" href="thesis_details.php?thesisid=<?= (int)$thesisID ?>">Back to Thesis</a>
      </div>
    </form>
  </div>

  <script src="assets/js/announce_presentation.js?v=<?= filemtime('assets/js/announce_presentation.js') ?>" defer></script>
</body>
</html>



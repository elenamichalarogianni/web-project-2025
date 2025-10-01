<?php
require_once 'db_connect.php';

if (!isset($_COOKIE['fullname']) && !isset($_COOKIE['email'])) {
    header("Location: index.php");
    exit();
}

$fullname    = $_COOKIE['fullname'] ?? $_COOKIE['email'];
$professorID = isset($_COOKIE['professorid']) ? (int)$_COOKIE['professorid'] : 0;

$thesisID = $_GET['thesisid'] ?? null;
if (!$thesisID) { echo "No Thesis ID provided!"; exit(); }
$thesisID = (int)$thesisID;
setcookie("thesisid", $thesisID, time() + 3600, "/");

$query = "SELECT ThesisID, StudentID, Title, ThesisDescription, ThesisStatus, SupervisorID, Link
          FROM Thesis WHERE ThesisID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$result = $stmt->get_result();

$thesis = $result->fetch_assoc();
if (!$thesis) { echo "Thesis not found!"; exit(); }

$studentID        = (int)$thesis['StudentID'];
$thesisTitle      = $thesis['Title'];
$thesisDescription= $thesis['ThesisDescription'];
$thesisStatus     = $thesis['ThesisStatus'];

$query = "SELECT fullname FROM Student WHERE StudentID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentID);
$stmt->execute();
$result = $stmt->get_result();
$studentName = $result->fetch_assoc()['fullname'] ?? "Not found";

$query = "
    SELECT tc.ProfessorID, tc.MemberType, p.FullName
    FROM ThesisCommittee tc
    JOIN Professor p ON tc.ProfessorID = p.ProfessorID
    WHERE tc.ThesisID = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$result = $stmt->get_result();
$committeeMembers = [];
while ($row = $result->fetch_assoc()) { $committeeMembers[] = $row; }

$query = "SELECT thesisstatus, actiondate
          FROM thesistimeline
          WHERE ThesisID = ?
          ORDER BY actiondate ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$result = $stmt->get_result();
$thesisTimeline = [];
while ($row = $result->fetch_assoc()) { $thesisTimeline[] = $row; }

$notes = [];
if ($professorID) {
    $query = "SELECT NoteID, NoteText, CreatedAt
              FROM ThesisNote
              WHERE ThesisID = ? AND ProfessorID = ?
              ORDER BY CreatedAt DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $thesisID, $professorID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $notes[] = $row; }
}

$thesisId = (int)($_GET['thesisid'] ?? 0);
$enabled = false;
$stmt = $conn->prepare("SELECT Enabled FROM enable_grade WHERE ThesisID = ?");
$stmt->bind_param("i", $thesisId);
$stmt->execute();
$stmt->bind_result($enabledTinyint);
if ($stmt->fetch()) { $enabled = (bool)$enabledTinyint; }
$stmt->close();

$isCommittee = false;
if ($professorID) {
  if ((int)$thesis['SupervisorID'] === $professorID) {
    $isCommittee = true;
  } else {
    $stmt = $conn->prepare("SELECT 1 FROM ThesisCommittee WHERE ThesisID=? AND ProfessorID=? LIMIT 1");
    $stmt->bind_param("ii", $thesisID, $professorID);
    $stmt->execute(); $stmt->store_result();
    $isCommittee = $stmt->num_rows > 0;
    $stmt->close();
  }
}

$myGrade = null;
if ($enabled && $isCommittee) {
  $stmt = $conn->prepare("
    SELECT QualityAndGoals, DurationScore, TextCompleteness, PresentationScore, FinalScore
    FROM ThesisGrade
    WHERE ThesisID=? AND ProfessorID=?
  ");
  $stmt->bind_param("ii", $thesisID, $professorID);
  $stmt->execute();
  $res = $stmt->get_result();
  $myGrade = $res->fetch_assoc() ?: null;
  $stmt->close();
}

$isProfessor = (($_COOKIE['role'] ?? '') === 'Professor');


$grades = [];
$stmt = $conn->prepare("
  SELECT 
      tg.ThesisID,
      tg.ProfessorID,
      tg.QualityAndGoals,
      tg.DurationScore,
      tg.TextCompleteness,
      tg.PresentationScore,
      tg.FinalScore,
      p.FullName AS ProfessorName
  FROM ThesisGrade tg
  JOIN Professor p ON p.ProfessorID = tg.ProfessorID
  WHERE tg.ThesisID = ?
  ORDER BY p.FullName
");
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/userformat.css?v=<?=filemtime('assets/css/userformat.css')?>">
<link rel="stylesheet" href="assets/css/thesisdetails.css?v=<?=filemtime('assets/css/thesisdetails.css')?>">
<link rel="stylesheet" href="assets/css/grades.css?v=<?=filemtime('assets/css/grades.css')?>">
<script src="assets/js/grade.js" defer></script>
<script src="assets/js/submit_grade.js?v=<?php echo filemtime('assets/js/submit_grade.js'); ?>" defer></script>
<title>Thesis Details</title>
</head>
<body>

  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="professor.php">Προβολή και Δημιουργία Θεμάτων προς Ανάθεση</a>
      <a href="assignthesis.php">Ανάθεση Θέματος</a> 
      <a href="activethesis.php">Λίστα Διπλωματικών</a>
      <a href="invitations.php">Προσκλήσεις</a>
      <a href="stats.php">Στατιστικά</a>
    </nav>
  </div>
  <div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

<div class="thesis-details">
    <h2>Thesis Details</h2>
    <p><strong>Thesis Title:</strong> <?= htmlspecialchars($thesisTitle) ?></p>
    <p><strong>Thesis Description:</strong> <?= htmlspecialchars($thesisDescription) ?></p>
    <p><strong>Thesis ID:</strong> <?= htmlspecialchars($thesisID) ?></p>
    <p><strong>Student ID:</strong> <?= htmlspecialchars($studentID) ?></p>
    <p><strong>Student Name:</strong> <?= htmlspecialchars($studentName) ?></p>

    <?php if ((int)$thesis['SupervisorID'] === (int)$professorID): ?>
      <h3>Supervisor Actions</h3>
      <?php if ($thesisStatus === 'Active'): ?>
        <form action="update_thesis_status.php" method="POST">
          <input type="hidden" name="thesis_id" value="<?= (int)$thesisID ?>">
          <input type="hidden" name="new_status" value="Under Review">
          <button type="submit" class="supervisor-btn">Set Thesis Under Review</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>

    <hr>

    <div class="committee-section">
      <h3>Committee Members</h3>
      <ul class="committee-list">
        <?php foreach ($committeeMembers as $member): ?>
          <li>
            <span>Professor:</span> <?= htmlspecialchars($member['FullName']) ?>
            - <span>Role:</span> <?= htmlspecialchars($member['MemberType']) ?>
          </li>
        <?php endforeach; ?>
      </ul>

       
      <a class="invitation-link" href="invitationtimeline.php?thesis_id=<?= (int)$thesisID ?>">
        Invitation Timeline
      </a>
    </div>

    <h3>Thesis Timeline</h3>
    <ul class="timeline">
      <?php foreach ($thesisTimeline as $action): ?>
        <li>
          <strong>Status:</strong> <?= htmlspecialchars($action['thesisstatus']) ?><br>
          <strong>Date:</strong> <?= htmlspecialchars($action['actiondate']) ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($thesisStatus === 'Active'): ?>
      <form id="setUnderReviewForm" action="update_thesis_status.php" method="POST">
        <input type="hidden" name="thesis_id" value="<?= (int)$thesisID ?>">
        <input type="hidden" name="new_status" value="Under Review">
        <button type="submit" class="supervisor-btn">Set Thesis Under Review</button>
      </form>
      <script src="assets/js/set_under_review.js?v=1" defer></script>
    <?php endif; ?>


    <?php if ($thesisStatus === 'Under Review'): ?>
      <h3>Thesis Sample</h3>
      <?php if (!empty($thesis['Link'])): ?>
        <a href="<?= htmlspecialchars($thesis['Link']) ?>" target="_blank" class="supervisor-btn">View Thesis</a>
      <?php else: ?>
        <p>No thesis file uploaded yet.</p>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($notes)): ?>
      <h3>Your Notes</h3>
      <ul class="professor-notes">
        <?php foreach ($notes as $note): ?>
          <li>
            <strong>Created At:</strong> <?= htmlspecialchars($note['CreatedAt']) ?><br>
            <p><?= nl2br(htmlspecialchars($note['NoteText'])) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No notes added yet.</p>
    <?php endif; ?>

    <?php if ($thesisStatus === 'Under Review' && (int)$thesis['SupervisorID'] === (int)$professorID): ?>
      <h3>Thesis Presentation</h3>
      <a class="supervisor-btn" href="announce_presentation.php?thesisid=<?= (int)$thesisID ?>">
        Announce Presentation
      </a>
    <?php endif; ?>

    <?php if ($thesisStatus === 'Under Review' && (int)$thesis['SupervisorID'] === (int)$professorID): ?>
      <h3>Thesis Grade</h3>
      <label class="switch">
        <input
          id="grade-toggle-<?= (int)$thesisId ?>"
          class="js-grade-toggle"
          type="checkbox"
          data-thesis-id="<?= (int)$thesisId ?>"
          <?= $enabled ? 'checked' : '' ?>>
        <span class="switch-ui" aria-hidden="true"></span>
      </label>
      <script src="assets/js/toggle.js?v=<?= filemtime('assets/js/toggle.js') ?>" defer></script>
    <?php endif; ?>

    <?php if ($enabled && $isCommittee): ?>
      <div class="grade-card">
        <h4>Insert your evaluation</h4>
        <form action="save_grade.php" method="post" id="gradeForm">
          <input type="hidden" name="ThesisID" value="<?= (int)$thesisId ?>">
          <div class="grid2">
            <label>
              Quality & Goals (0–10) <small>(60%)</small>
              <input type="number" name="QualityAndGoals" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['QualityAndGoals'] ?? '') ?>" required>
            </label>
            <label>
              Duration (0–10) <small>(15%)</small>
              <input type="number" name="DurationScore" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['DurationScore'] ?? '') ?>" required>
            </label>
            <label>
              Text completeness (0–10) <small>(15%)</small>
              <input type="number" name="TextCompleteness" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['TextCompleteness'] ?? '') ?>" required>
            </label>
            <label>
              Presentation (0–10) <small>(10%)</small>
              <input type="number" name="PresentationScore" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['PresentationScore'] ?? '') ?>" required>
            </label>
          </div>
          <div class="final-line">
            <span>Final (auto):</span>
            <output id="finalScore"><?= htmlspecialchars($myGrade['FinalScore'] ?? '0.00') ?></output>
          </div>
          <button type="submit" class="supervisor-btn">Submit grade</button>
          <div id="gradeStatus" role="status" aria-live="polite" style="margin-top:10px;"></div>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($enabled && $isCommittee): ?>
      <h3>Committee Grades</h3>
      <?php if (!empty($grades)): ?>
        <table border="1" cellpadding="6">
          <tr>
            <th>Professor</th>
            <th>Quality &amp; Goals</th>
            <th>Duration</th>
            <th>Text</th>
            <th>Presentation</th>
            <th>Final</th>
          </tr>
          <?php foreach ($grades as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['ProfessorName'] ?? ('#'.$row['ProfessorID'])) ?></td>
              <td><?= htmlspecialchars($row['QualityAndGoals']) ?></td>
              <td><?= htmlspecialchars($row['DurationScore']) ?></td>
              <td><?= htmlspecialchars($row['TextCompleteness']) ?></td>
              <td><?= htmlspecialchars($row['PresentationScore']) ?></td>
              <td><b><?= htmlspecialchars($row['FinalScore']) ?></b></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p>No grades submitted yet.</p>
      <?php endif; ?>
    <?php endif; ?>

    <div class="actions full">
      <a class="supervisor-btn" href="activethesis.php?thesisid=<?= (int)$thesisID ?>">Back</a>
    </div>
</div>
</body>
</html>

<?php
require_once 'db_connect.php';

if (!isset($_COOKIE['fullname']) && !isset($_COOKIE['email'])) {
    header("Location: index.php");
    exit();
}

$fullname    = $_COOKIE['fullname'] ?? $_COOKIE['email'];
$professorID = $_COOKIE['userid'] ?? null;   
$role        = $_COOKIE['role'] ?? '';

$thesisID = (int)($_GET['thesisid'] ?? 0);
if ($thesisID <= 0) { echo "No Thesis ID provided!"; exit(); }
setcookie("thesisid", $thesisID, time() + 3600, "/");


$stmt = $conn->prepare("
  SELECT ThesisID, StudentID, Title, ThesisDescription, ThesisStatus, SupervisorID, Link
  FROM Thesis WHERE ThesisID = ?
");
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$thesis = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$thesis) { echo "Thesis not found!"; exit(); }

$studentID         = (int)$thesis['StudentID'];
$thesisTitle       = $thesis['Title'];
$thesisDescription = $thesis['ThesisDescription'];
$thesisStatus      = $thesis['ThesisStatus'];
$supervisorID      = (int)$thesis['SupervisorID'];


$stmt = $conn->prepare("SELECT FullName FROM Student WHERE StudentID = ?");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$studentName = $stmt->get_result()->fetch_assoc()['FullName'] ?? "Not found";
$stmt->close();


$stmt = $conn->prepare("
  SELECT tc.ProfessorID, tc.MemberType, p.FullName
  FROM ThesisCommittee tc
  JOIN Professor p ON p.ProfessorID = tc.ProfessorID
  WHERE tc.ThesisID = ?
");
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$committeeRes = $stmt->get_result();
$committeeMembers = [];
while ($row = $committeeRes->fetch_assoc()) { $committeeMembers[] = $row; }
$stmt->close();


$notes = [];
if ($professorID) {
  $stmt = $conn->prepare("
    SELECT NoteID, NoteText, CreatedAt
    FROM ThesisNote
    WHERE ThesisID = ? AND ProfessorID = ?
    ORDER BY CreatedAt DESC
  ");
  $stmt->bind_param("ii", $thesisID, $professorID);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) { $notes[] = $r; }
  $stmt->close();
}


$enabled = false;
$stmt = $conn->prepare("SELECT Enabled FROM enable_grade WHERE ThesisID = ?");
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$stmt->bind_result($enabledTiny);
if ($stmt->fetch()) { $enabled = (bool)$enabledTiny; }
$stmt->close();


$isCommittee = false;
if (!empty($professorID)) {
  if ((int)$professorID === $supervisorID) {
    $isCommittee = true;
  } else {
    $stmt = $conn->prepare("SELECT 1 FROM ThesisCommittee WHERE ThesisID=? AND ProfessorID=? LIMIT 1");
    $stmt->bind_param("ii", $thesisID, $professorID);
    $stmt->execute();
    $stmt->store_result();
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
  $myGrade = $stmt->get_result()->fetch_assoc() ?: null;
  $stmt->close();
}


$stmt = $conn->prepare("
  SELECT tg.ThesisID, tg.ProfessorID, tg.QualityAndGoals, tg.DurationScore,
         tg.TextCompleteness, tg.PresentationScore, tg.FinalScore,
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


$since = null; $sinceAgo = ''; $thesisTimeline = [];

if ($role === 'Secretary') {
  $stmt = $conn->prepare("
    SELECT ThesisStatus AS thesisstatus, ActionDate AS actiondate
    FROM ThesisTimeline
    WHERE ThesisID = ? AND ThesisStatus IN ('Active','Under Assignment')
    ORDER BY ActionDate DESC LIMIT 1
  ");
  $stmt->bind_param('i', $thesisID);
  $stmt->execute();
  $since = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($since && !empty($since['actiondate'])) {
    try {
      $dt = new DateTime($since['actiondate']);
      $diff = $dt->diff(new DateTime());
      $parts = [];
      if ($diff->y) $parts[] = $diff->y.'y';
      if ($diff->m) $parts[] = $diff->m.'m';
      if ($diff->d) $parts[] = $diff->d.'d';
      if (!$diff->y && !$diff->m && $diff->h) $parts[] = $diff->h.'h';
      if (!$diff->y && !$diff->m && !$diff->d && $diff->i) $parts[] = $diff->i.'m';
      $sinceAgo = $parts ? ' — '.implode(' ', $parts).' ago' : '';
    } catch(Exception $e) {}
  }
}

$stmt = $conn->prepare("
  SELECT ThesisStatus AS thesisstatus, ActionDate AS actiondate
  FROM ThesisTimeline
  WHERE ThesisID = ?
  ORDER BY ActionDate ASC
");
$stmt->bind_param('i', $thesisID);
$stmt->execute();
$resB = $stmt->get_result();
while ($row = $resB->fetch_assoc()) { $thesisTimeline[] = $row; }
$stmt->close();


   
  
$gradersCount = 0;
$canFinalize  = false;

$countSql = "
  SELECT COUNT(DISTINCT tg.ProfessorID) AS graders
  FROM ThesisGrade tg
  LEFT JOIN Thesis t  ON t.ThesisID = tg.ThesisID
  LEFT JOIN ThesisCommittee tc
         ON tc.ThesisID = tg.ThesisID AND tc.ProfessorID = tg.ProfessorID
  WHERE tg.ThesisID = ?
    AND (tc.ProfessorID IS NOT NULL OR tg.ProfessorID = t.SupervisorID)
    AND tg.FinalScore IS NOT NULL
";

if ($role === 'Secretary') {
  $stmt = $conn->prepare($countSql);
  $stmt->bind_param('i', $thesisID);
  $stmt->execute();
  $gradersCount = (int)($stmt->get_result()->fetch_assoc()['graders'] ?? 0);
  $stmt->close();

  $canFinalize = ($gradersCount >= 3 && $thesisStatus !== 'Completed');
}


$finalizeMsg = '';
if ($role === 'Secretary' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_thesis'])) {
  $postThesisId = (int)($_POST['thesis_id'] ?? 0);

  $stmt = $conn->prepare($countSql);
  $stmt->bind_param('i', $postThesisId);
  $stmt->execute();
  $recount = (int)($stmt->get_result()->fetch_assoc()['graders'] ?? 0);
  $stmt->close();

  if ($recount >= 3) {
    $conn->begin_transaction();
    try {
      $u = $conn->prepare("UPDATE Thesis SET ThesisStatus='Completed' WHERE ThesisID=? AND ThesisStatus<>'Completed'");
      $u->bind_param('i', $postThesisId);
      $u->execute();
      $rowsU = $u->affected_rows;
      $u->close();

      $ti = $conn->prepare("INSERT INTO ThesisTimeline (ThesisID, ThesisStatus, ActionDate) VALUES (?, 'Completed', NOW())");
      $ti->bind_param('i', $postThesisId);
      $ti->execute();
      $ti->close();

      $conn->commit();

      if ($rowsU > 0) {
        $finalizeMsg  = 'Thesis marked as Completed.';
        $thesisStatus = 'Completed';
        $canFinalize  = false;
      } else {
        $finalizeMsg  = 'Already completed or thesis not found.';
      }
    } catch (Throwable $e) {
      $conn->rollback();
      $finalizeMsg = 'Error finalizing thesis: '.$e->getMessage();
    }
  } else {
    $finalizeMsg = 'Cannot finalize: need grades from at least 3 different professors.';
  }
}


$gaNumber = null; $gaYear = null;
$ga = $conn->prepare("SELECT GA_Number, GA_Year FROM ThesisAssignmentGA WHERE ThesisID = ? LIMIT 1");
$ga->bind_param('i', $thesisID);
$ga->execute();
$gaRes = $ga->get_result()->fetch_assoc();
$ga->close();
if ($gaRes) {
  $gaNumber = (int)$gaRes['GA_Number'];
  $gaYear   = (int)$gaRes['GA_Year'];
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Theses</title>
  <link rel="stylesheet" href="assets/css/userformat.css">
  <link rel="stylesheet" href="assets/css/secretary.css">
  <link rel="stylesheet" href="assets/css/sec_thesis_details.css?v=<?=time()?>">

  
  <script>
    window.SEC_GA_CTX = {
      role: <?= json_encode($role, JSON_UNESCAPED_UNICODE) ?>,
      thesisId: <?= (int)$thesisID ?>,
      thesisStatus: <?= json_encode($thesisStatus, JSON_UNESCAPED_UNICODE) ?>
    };
  </script>
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

  <div class="thesis-details">
    <h2>Thesis Details</h2>
    <p><strong>Thesis Title:</strong> <?= htmlspecialchars($thesisTitle) ?></p>
    <p><strong>Thesis Description:</strong> <?= htmlspecialchars($thesisDescription) ?></p>
    <p><strong>Thesis ID:</strong> <?= htmlspecialchars($thesisID) ?></p>
    <p><strong>Student ID:</strong> <?= htmlspecialchars($studentID) ?></p>
    <p><strong>Student Name:</strong> <?= htmlspecialchars($studentName) ?></p>

    <?php if ((int)$supervisorID === (int)$professorID): ?>
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
    </div>

    <h3>Thesis Timeline</h3>
    <ul class="timeline">
      <?php if (empty($thesisTimeline)): ?>
        <li>No timeline entries yet.</li>
      <?php else: ?>
        <?php foreach ($thesisTimeline as $action): ?>
          <li>
            <strong>Status:</strong> <?= htmlspecialchars($action['thesisstatus']) ?><br>
            <strong>Date:</strong>   <?= htmlspecialchars($action['actiondate']) ?>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>

    <h3>Cancel Thesis</h3>
    <?php if ($thesisStatus === 'Active'): ?>
      <div style="margin-top:10px;">
        <a class="supervisor-btn" href="sec_cancel_reason.php?thesis_id=<?=
          (int)$thesisID ?>">Cancel Thesis</a>
      </div>
    <?php endif; ?>

    <?php if ($enabled && $isCommittee): ?>
      <div class="grade-card">
        <h4>Insert your evaluation</h4>
        <form action="save_grade.php" method="post" id="gradeForm">
          <input type="hidden" name="ThesisID" value="<?= (int)$thesisID ?>">
          <input type="hidden" name="StudentID" value="<?= (int)$studentID ?>">

          <div class="grid2">
            <label>Quality & Goals (0–10) <small>(60%)</small>
              <input type="number" name="QualityAndGoals" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['QualityAndGoals'] ?? '') ?>" required>
            </label>
            <label>Duration (0–10) <small>(15%)</small>
              <input type="number" name="DurationScore" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['DurationScore'] ?? '') ?>" required>
            </label>
            <label>Text completeness (0–10) <small>(15%)</small>
              <input type="number" name="TextCompleteness" step="0.01" min="0" max="10"
                     value="<?= htmlspecialchars($myGrade['TextCompleteness'] ?? '') ?>" required>
            </label>
            <label>Presentation (0–10) <small>(10%)</small>
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

    
    <h3>General Assembly (GA) – Assignment Approval</h3>

    <?php if ($role === 'Secretary'): ?>
      <?php if ($thesisStatus === 'Active'): ?>
        <p>Καταχώριση ΑΠ/Έτους Γενικής Συνέλευσης στην οποία εγκρίθηκε η ανάθεση θέματος.</p>

        <form id="gaForm" class="grid2" onsubmit="return false;">
          <label>GA Number
            <input type="number" id="ga_number" min="1" step="1" required
                   value="<?= $gaNumber ? (int)$gaNumber : '' ?>">
          </label>
          <label>GA Year
            <input type="number" id="ga_year" min="2000" max="2100" step="1" required
                   value="<?= $gaYear ? (int)$gaYear : date('Y') ?>">
          </label>
          <div class="actions full">
            <button type="button" class="submit-btn" id="gaSaveBtn">Save GA info</button>
          </div>
          <div id="gaMsg" class="notice" style="display:none;"></div>
        </form>
      <?php else: ?>
        <p>Η διπλωματική δεν είναι σε κατάσταση <b>Active</b>.</p>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($gaNumber && $gaYear): ?>
      <p><strong>GA recorded:</strong> <?= (int)$gaNumber ?>/<?= (int)$gaYear ?></p>
    <?php endif; ?>

    <div class="actions full" style="margin-top:14px;">
      <a class="supervisor-btn" href="secretary.php?thesisid=<?= (int)$thesisID ?>">Back</a>
    </div>
  </div>

  
  <script src="assets/js/sec_ga.js?v=<?=time()?>"></script>
</body>
</html>

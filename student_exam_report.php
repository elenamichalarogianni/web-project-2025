<?php
require_once 'db_connect.php';

if (($_COOKIE['role'] ?? '') !== 'Student' || empty($_COOKIE['userid'])) {
  header("Location: index.php"); exit;
}

$studentId = (int)($_COOKIE['studentid'] ?? 0);
if ($studentId <= 0) {
  $u = (int)($_COOKIE['userid'] ?? 0);
  if ($u>0) { $q=$conn->prepare("SELECT StudentID FROM Student WHERE UserID=? LIMIT 1");
    $q->bind_param('i',$u); $q->execute(); $q->bind_result($sid); $q->fetch(); $q->close(); $studentId=(int)$sid; }
}
$thesisId = (int)($_GET['thesis_id'] ?? 0);
if ($thesisId <= 0) { http_response_code(400); exit('Bad thesis id'); }


$own = $conn->prepare("SELECT Title, ThesisDescription, ThesisStatus, SupervisorID, StudentID FROM Thesis WHERE ThesisID=?");
$own->bind_param('i',$thesisId); $own->execute();
$t = $own->get_result()->fetch_assoc(); $own->close();
if (!$t || (int)$t['StudentID'] !== $studentId) { http_response_code(403); exit('Forbidden'); }


$sqlCnt = "
  SELECT COUNT(DISTINCT tg.ProfessorID) AS graders
  FROM ThesisGrade tg
  LEFT JOIN Thesis tt ON tt.ThesisID = tg.ThesisID
  LEFT JOIN ThesisCommittee tc ON tc.ThesisID = tg.ThesisID AND tc.ProfessorID = tg.ProfessorID
  WHERE tg.ThesisID = ?
    AND (tc.ProfessorID IS NOT NULL OR tg.ProfessorID = tt.SupervisorID)
    AND tg.FinalScore IS NOT NULL
";
$s = $conn->prepare($sqlCnt); $s->bind_param('i',$thesisId); $s->execute();
$graders = (int)($s->get_result()->fetch_assoc()['graders'] ?? 0); $s->close();
if ($graders < 3) { http_response_code(409); exit('Not ready'); }


$st = $conn->prepare("SELECT FullName, AM AS RegNo FROM Student WHERE StudentID=?");
$st->bind_param('i',$t['StudentID']); $st->execute();
$stRes = $st->get_result()->fetch_assoc(); $st->close();


$cm = $conn->prepare("SELECT tc.MemberType, p.FullName FROM ThesisCommittee tc JOIN Professor p ON p.ProfessorID=tc.ProfessorID WHERE tc.ThesisID=? ORDER BY FIELD(tc.MemberType,'Supervisor','Member'), p.FullName");
$cm->bind_param('i',$thesisId); $cm->execute(); $committee = $cm->get_result()->fetch_all(MYSQLI_ASSOC); $cm->close();


$gg = $conn->prepare("
  SELECT p.FullName, tg.QualityAndGoals, tg.DurationScore, tg.TextCompleteness, tg.PresentationScore, tg.FinalScore
  FROM ThesisGrade tg JOIN Professor p ON p.ProfessorID = tg.ProfessorID
  WHERE tg.ThesisID=? ORDER BY p.FullName
");
$gg->bind_param('i',$thesisId); $gg->execute(); $grades = $gg->get_result()->fetch_all(MYSQLI_ASSOC); $gg->close();

$avg = null; if ($grades) {
  $sum = 0; $cnt = 0;
  foreach ($grades as $g) { if ($g['FinalScore'] !== null) { $sum += (float)$g['FinalScore']; $cnt++; } }
  if ($cnt>0) $avg = round($sum/$cnt, 2);
}


$pr = $conn->prepare("SELECT ExamDate, ExamTime, PresentationType, LocationOrLink FROM Presentation WHERE ThesisID=?");
$pr->bind_param('i',$thesisId); $pr->execute(); $present = $pr->get_result()->fetch_assoc(); $pr->close();

$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Πρακτικό Εξέτασης — Thesis #<?= (int)$thesisId ?></title>
<link rel="stylesheet" href="assets/css/userformat.css?v=<?= filemtime('assets/css/userformat.css') ?>">
<link rel="stylesheet" href="assets/css/thesisdetails.css?v=<?= filemtime('assets/css/thesisdetails.css') ?>">
<style>
  .report-card{ width:min(900px,90%); margin:40px auto; background:#fff; border:1px solid #cfe0e8; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.08); padding:24px; }
  .report-card h2{ margin:0 0 14px; color:#185A7B; text-align:center; }
  .report-card table{ width:100%; border-collapse:collapse; margin-top:12px; }
  .report-card th,.report-card td{ border:1px solid #e6eef2; padding:8px 10px; text-align:left; }
  .badge{ display:inline-block; padding:4px 10px; border-radius:999px; background:#eef5f8; color:#185A7B; font-weight:700; }
</style>
</head>
<body>
  <div class="blue-bar-top">
    <span class="username"><?= htmlspecialchars($fullname) ?></span>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="student.php" class="active">Προβολή Θέματος</a>
      <a href="student_profile.php">Επεξεργασία Προφίλ</a>
    </nav>
  </div>

  <div class="report-card">
    <h2>Πρακτικό Εξέτασης</h2>

    <p><strong>Θέμα:</strong> <?= htmlspecialchars($t['Title'] ?: '—') ?></p>
    <p><strong>Φοιτητής/τρια:</strong> <?= htmlspecialchars($stRes['FullName'] ?? '—') ?> (ΑΜ: <?= htmlspecialchars($stRes['RegNo'] ?? '—') ?>)</p>

    <?php if ($present): ?>
      <p>
        <strong>Εξέταση:</strong>
        <?= htmlspecialchars($present['ExamDate'] ?? '') ?> <?= htmlspecialchars($present['ExamTime'] ?? '') ?> —
        <span class="badge"><?= ($present['PresentationType']==='Online' ? 'Διαδικτυακά' : 'Δια ζώσης') ?></span>
        <?php if (!empty($present['LocationOrLink'])): ?>
          • <?= htmlspecialchars($present['LocationOrLink']) ?>
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <h3>Τριμελής Επιτροπή</h3>
    <ul class="committee-list">
      <?php foreach ($committee as $m): ?>
        <li><span><?= htmlspecialchars($m['MemberType']) ?>:</span> <?= htmlspecialchars($m['FullName']) ?></li>
      <?php endforeach; ?>
    </ul>

    <h3>Βαθμολογίες</h3>
    <?php if ($grades): ?>
      <table>
        <tr>
          <th>Καθηγητής</th>
          <th>Quality &amp; Goals</th>
          <th>Duration</th>
          <th>Text</th>
          <th>Presentation</th>
          <th>Τελικός</th>
        </tr>
        <?php foreach ($grades as $g): ?>
          <tr>
            <td><?= htmlspecialchars($g['FullName']) ?></td>
            <td><?= htmlspecialchars($g['QualityAndGoals']) ?></td>
            <td><?= htmlspecialchars($g['DurationScore']) ?></td>
            <td><?= htmlspecialchars($g['TextCompleteness']) ?></td>
            <td><?= htmlspecialchars($g['PresentationScore']) ?></td>
            <td><strong><?= htmlspecialchars($g['FinalScore']) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <?php if ($avg !== null): ?>
        <p><strong>Μέσος όρος τελικών βαθμών:</strong> <?= number_format($avg,2) ?></p>
      <?php endif; ?>
    <?php else: ?>
      <p>Δεν υπάρχουν καταχωρισμένες βαθμολογίες.</p>
    <?php endif; ?>

    <div class="actions full" style="margin-top:14px;">
      <a class="supervisor-btn" href="student.php">Επιστροφή</a>
    </div>
  </div>
</body>
</html>

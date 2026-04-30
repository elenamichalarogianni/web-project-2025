<?php
require_once __DIR__ . '/../app/config/db_connect.php';

if (!isset($_COOKIE['email']) || (($_COOKIE['role'] ?? null) !== 'Student')) {
  header("Location: index.php"); exit;
}
$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];

$studentId = (int)($_COOKIE['studentid'] ?? 0);

$canInvite = false;
if ($studentId > 0) {
  $q = $conn->prepare("
    SELECT ThesisID, ThesisStatus
    FROM Thesis
    WHERE StudentID = ?
    ORDER BY ThesisID DESC
    LIMIT 1
  ");
  $q->bind_param('i',$studentId);
  $q->execute(); $q->bind_result($thesisId, $thesisStatus);
  if ($q->fetch()) {
    $nonSupCnt = 0;
    $q->close();

    $c = $conn->prepare("SELECT COUNT(*) FROM ThesisCommittee WHERE ThesisID=? AND LOWER(MemberType) <> 'supervisor'");
    $c->bind_param('i',$thesisId);
    $c->execute(); $c->bind_result($nonSupCnt); $c->fetch(); $c->close();

    $canInvite = ($thesisStatus === 'Under Assignment' && (int)$nonSupCnt < 2);
  } else {
    $q->close();
  }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/userformat.css?v=<?php echo filemtime('assets/css/userformat.css'); ?>">
  <link rel="stylesheet" href="assets/css/studenttopic.css?v=<?php echo filemtime('assets/css/studenttopic.css'); ?>">
  <link rel="stylesheet" href="assets/css/studentlinks.css?v=<?= filemtime('assets/css/studentlinks.css') ?>">
  <title>Προβολή Θέματος</title>
</head>
<body>
  <div class="blue-bar-left">
    <nav class="side-menu">
      <a href="student.php" class="active">Προβολή Θέματος</a>
      <a href="student_profile.php">Επεξεργασία Προφίλ</a>
    </nav>
  </div>

  <div class="blue-bar-top">
    <span class="username"><?php echo htmlspecialchars($fullname); ?></span>
    <a class="logout-btn" href="logout.php">Logout</a>
  </div>

  <div class="student-topic-card" aria-live="polite">
    <h2>Η Διπλωματική μου</h2>

    <label for="st-title">Τίτλος</label>
    <input id="st-title" type="text" readonly>

    <label for="st-desc">Περιγραφή</label>
    <textarea id="st-desc" readonly></textarea>

    <label for="st-file">Συνημμένο</label>
    <div class="file-row">
      <input id="st-file" type="text" readonly>
      <a id="st-download" class="download-btn" href="#" target="_blank" rel="noopener" hidden>Λήψη αρχείου</a>
    </div>

    <div class="status-badge">Κατάσταση: <span id="st-status">—</span></div>
    <div id="st-elapsed" class="elapsed-badge" hidden>Χρόνος από ανάθεση: </div> 

    <section id="draft-upload" class="student-topic-card" hidden>
      <h2>Πρόχειρο Κείμενο</h2>

      <div class="hint muted">Ανέβασε PDF/DOC/DOCX που θα είναι ορατό στα μέλη της τριμελούς.</div>

      <div class="file-row">
        <input id="draftFileName" type="text" readonly placeholder="No file chosen">
        <label for="draftFile" class="download-btn" style="cursor:pointer;">Choose File</label>
        <input id="draftFile" type="file" accept=".pdf,.doc,.docx" hidden>
        <button id="draftUploadBtn" type="button" class="download-btn">Μεταφόρτωση</button>
      </div>

      <div class="current-draft" style="margin-top:10px;">
        Τρέχον αρχείο:
        <a id="draftCurrentLink" href="#" target="_blank" rel="noopener" hidden>Άνοιγμα</a>
        <span id="draftNone" class="muted">— κανένα —</span>
      </div>
    </section>

    
    <section id="links-box" class="student-topic-card" hidden>
      <h2>Σύνδεσμοι Υλικού</h2>

      <label for="linkTitle">Τίτλος (προαιρετικό)</label>
      <input id="linkTitle" type="text" placeholder="π.χ. Demo video">

      <label for="linkUrl">Σύνδεσμος</label>
      <div class="file-row">
        <input id="linkUrl" type="url" placeholder="https://...">
        <button id="linkAddBtn" type="button" class="download-btn">Προσθήκη</button>
      </div>

      <ul id="linkList" style="list-style:none; padding-left:0; margin-top:12px;"></ul>
    </section>

      
    <section id="st-presentation" class="student-topic-card" hidden>
      <h2>Δήλωση Εξέτασης</h2>
      <p class="hint muted">Καταχώρισε την ημερομηνία/ώρα και τον τρόπο εξέτασης που συμφωνήθηκε με την τριμελή.</p>

      <label for="sp-exam-date">Ημερομηνία</label>
      <input id="sp-exam-date" type="date" required>

      <label for="sp-exam-time">Ώρα</label>
      <input id="sp-exam-time" type="time" required>

      <label for="sp-type">Τρόπος Εξέτασης</label>
      <select id="sp-type">
        <option value="InPerson">Δια ζώσης</option>
        <option value="Online">Διαδικτυακά</option>
      </select>

      <label id="sp-loc-label" for="sp-loc">Αίθουσα</label>
      <input id="sp-loc" type="text" placeholder="π.χ. Αμφιθέατρο Β12" required>

      <label for="sp-note">Σημείωση (προαιρετικά)</label>
      <textarea id="sp-note" rows="3" placeholder="π.χ. προαιρετικά στοιχεία ανακοίνωσης"></textarea>

      <div class="profile-actions">
        <button id="sp-save" class="btn" type="button">Αποθήκευση</button>
      </div>
      <div id="sp-msg" class="hint" aria-live="polite" style="margin-top:8px;"></div>
    </section>

     
    <section id="exam-report-section" class="student-topic-card" hidden>
      <h2>Πρακτικό Εξέτασης & Αποθετήριο</h2>

      <p class="hint muted">Όταν η τριμελής καταχωρήσει βαθμούς, μπορείς να δεις το πρακτικό και να δηλώσεις τον σύνδεσμο στο αποθετήριο.</p>

      <div class="file-row" style="margin:10px 0 14px;">
        <button id="examReportBtn" type="button" class="download-btn">Προβολή Πρακτικού</button>
      </div>

      <h3 style="margin-top:10px;">Σύνδεσμος στο αποθετήριο (Νημερτής)</h3>
      <p>Τρέχουσα καταχώριση: <span id="repoCurrent" class="muted">—</span></p>

      <label for="repoTitle">Τίτλος (προαιρετικό)</label>
      <input id="repoTitle" type="text" placeholder="π.χ. Τελικό κείμενο στη Νημερτή">

      <label for="repoUrl">URL</label>
      <div class="file-row">
        <input id="repoUrl" type="url" placeholder="https://...">
        <button id="repoSave" type="button" class="download-btn">Αποθήκευση</button>
      </div>

      <div id="examMsg" class="hint" aria-live="polite" style="margin-top:8px;"></div>
    </section>
  </div>

  
  <section id="committee" aria-live="polite">
    <h2>Τριμελής Επιτροπή</h2>
    <ul id="committeeList" style="list-style:none; padding-left:0; margin:10px 0;"></ul>

    <?php if ($canInvite): ?>
      <button id="sendInvBtn" type="button">Αποστολή Προσκλήσεων</button>
    <?php endif; ?>
  </section>


  
  <div id="inviteModal" class="invite-modal" hidden>
    <div class="invite-card">
      <div class="invite-header">
        <h3>Αποστολή Προσκλήσεων</h3>
        <button type="button" id="inviteClose" aria-label="Close">×</button>
      </div>
      <div class="invite-body">
        <p class="hint">Επέλεξε καθηγητές (εξαιρείται αυτόματα ο επιβλέπων και όσοι έχουν ήδη προσκληθεί).</p>
        <div id="eligibleList" class="eligible-list"></div>
      </div>
      <div class="invite-actions">
        <button type="button" id="inviteSend" class="download-btn">Αποστολή</button>
      </div>
    </div>
  </div>


  <script src="assets/js/student_thesis.js?v=<?php echo filemtime('assets/js/student_thesis.js'); ?>"></script>
  <script src="assets/js/student_presentation.js?v=<?php echo time(); ?>" defer></script>
  <script src="assets/js/student_exam.js?v=<?php echo time(); ?>" defer></script>
</body>
</html>



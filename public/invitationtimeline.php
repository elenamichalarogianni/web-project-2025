<?php
require_once __DIR__ . '/../app/config/db_connect.php';


if (($_COOKIE['role'] ?? '') !== 'Professor') {
    header("Location: index.php");
    exit();
}

$fullname = $_COOKIE['fullname'] ?? $_COOKIE['email'];
$professorID = (int)($_COOKIE['professorid'] ?? 0);
$thesisID = $_COOKIE['thesisid'] ?? null;

if (!$professorID) {
    echo "User ID not found!";
    exit();
}

if (!$thesisID) {
    echo "thesis ID not found!";
    exit();
}

$query = "
    SELECT ia.NewStatus, ia.ActionAt, p.FullName AS ProfessorName
    FROM InvitationAction ia
    JOIN Invitation i ON ia.InvitationID = i.InvitationID
    JOIN Professor p ON i.ProfessorID = p.ProfessorID
    WHERE i.ThesisID = ?
    ORDER BY ia.ActionAt ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $thesisID);
$stmt->execute();
$result = $stmt->get_result();

$invitationTimeline = [];
while ($row = $result->fetch_assoc()) {
    $invitationTimeline[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel ="stylesheet" type="text/css" href="assets/css/userformat.css">
    <link rel="stylesheet"
      href="assets/css/invitationtimeline.css?v=<?= is_file(__DIR__.'/assets/css/invitationtimeline.css')
           ? filemtime(__DIR__.'/assets/css/invitationtimeline.css') : time() ?>">
</head>
<body>
    <div class="prof-theses-container">
        <div class="thesis-card">
        </div>
    </div>

    <h1>Invitation Timeline</h1>

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
        <span class="username"><?php echo htmlspecialchars($_COOKIE['fullname']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <ul class="invitation-timeline">
        <?php if (!empty($invitationTimeline)): ?>
            <?php foreach ($invitationTimeline as $item): ?>
                <li>
                    <strong>Professor:</strong> <?php echo htmlspecialchars($item['ProfessorName']); ?><br>
                    <strong>Status:</strong> <?php echo htmlspecialchars($item['NewStatus']); ?><br>
                    <strong>Date:</strong> <?php echo htmlspecialchars($item['ActionAt']); ?>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No invitations found for this thesis.</li>
        <?php endif; ?>
    </ul>
</body>
</html>




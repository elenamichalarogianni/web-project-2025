<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/config/db_connect.php'; 

$email = trim($_POST['email'] ?? '');
$pass  = trim($_POST['password'] ?? '');

if ($email === '' || $pass === '') {
  echo json_encode(['ok' => false, 'error' => 'EMPTY_FIELDS']); exit;
}

$stmt = $conn->prepare("SELECT UserID, Email, Password_hash, UserType, FullName
                        FROM Users WHERE Email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row || $pass !== $row['Password_hash']) {
  echo json_encode(['ok' => false, 'error' => 'INVALID_CREDENTIALS']); exit;
}


$cookiePath = '/'; 
setcookie('userid',       (string)$row['UserID'], 0, $cookiePath); 
setcookie('email',    $row['Email'],          0, $cookiePath);
setcookie('role',     $row['UserType'],       0, $cookiePath);
setcookie('fullname', $row['FullName'],       0, $cookiePath);

if ($row['UserType'] === 'Professor') {
  $q = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID = ? LIMIT 1");
  $q->bind_param('i', $row['UserID']);
  $q->execute();
  $q->bind_result($pid);
  if ($q->fetch() && $pid) {
    setcookie('professorid', (string)$pid, 0, $cookiePath);
  }
  $q->close();
} elseif ($row['UserType'] === 'Student') {
  $q = $conn->prepare("SELECT StudentID FROM Student WHERE UserID = ? LIMIT 1");
  $q->bind_param('i', $row['UserID']);
  $q->execute();
  $q->bind_result($sid);
  if ($q->fetch() && $sid) {
    setcookie('studentid', (string)$sid, 0, $cookiePath);
  }
  $q->close();
} elseif ($row['UserType'] === 'Secretary') {
  $q = $conn->prepare("SELECT SecretaryID FROM Secretary WHERE UserID = ? LIMIT 1");
  $q->bind_param('i', $row['UserID']);
  $q->execute();
  $q->bind_result($secid);
  if ($q->fetch() && $secid) {
    setcookie('secretaryid', (string)$secid, 0, $cookiePath);
  }
  $q->close();
}

echo json_encode([
  'ok'       => true,
  'role'     => $row['UserType'],
  'fullname' => $row['FullName'],
]);

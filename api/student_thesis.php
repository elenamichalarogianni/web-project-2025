<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connect.php';


if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Student')) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHORIZED']);
  exit;
}


$studentId = (int)($_COOKIE['studentid'] ?? 0);


$stmt = $conn->prepare("
  SELECT ThesisID, Title, ThesisDescription, Link, ThesisStatus, AssignmentDate
  FROM Thesis
  WHERE StudentID = ?
  ORDER BY ThesisID DESC
  LIMIT 1
");

$stmt->bind_param('i', $studentId);
$stmt->execute();
$thesis = $stmt->get_result()->fetch_assoc();

$assignedAt = $thesis['AssignmentDate'] ?? null;

if (!$assignedAt) {
  $qa = $conn->prepare("
    SELECT MIN(ActionDate)
    FROM ThesisTimeline
    WHERE ThesisID = ? AND ThesisStatus = 'Active'
  ");
  $qa->bind_param('i', $thesis['ThesisID']);
  $qa->execute(); $qa->bind_result($ts); $qa->fetch(); $qa->close();
  if ($ts) { $assignedAt = $ts; }
}

$stmt->close();

if (!$thesis) {
  echo json_encode(['ok' => true, 'thesis' => null]);
  exit;
}


$members = [];
$cm = $conn->prepare("
  SELECT tc.ProfessorID, tc.MemberType, p.FullName
  FROM ThesisCommittee tc
  JOIN Professor p ON p.ProfessorID = tc.ProfessorID
  WHERE tc.ThesisID = ?
  ORDER BY FIELD(tc.MemberType,'Supervisor','Member'), tc.ProfessorID
");
$cm->bind_param('i', $thesis['ThesisID']);
$cm->execute();
$res = $cm->get_result();
while ($row = $res->fetch_assoc()) {
  $members[] = [
    'ProfessorID' => (int)$row['ProfessorID'],
    'MemberType'  => $row['MemberType'],
    'FullName'    => $row['FullName'],
  ];
}
$cm->close();




echo json_encode([
  'ok' => true,
  'thesis' => [
    'ThesisID'         => (int)$thesis['ThesisID'],
    'Title'            => $thesis['Title'],
    'ThesisDescription'=> $thesis['ThesisDescription'],
    'Link'             => $thesis['Link'],
    'ThesisStatus'     => $thesis['ThesisStatus'],
    'AssignedAt'       => $assignedAt,   
    'Committee'        => $members,
    'CanInvite'        => (
        $thesis['ThesisStatus'] === 'Under Assignment' &&
        count(array_filter($members, fn($m) => strtolower($m['MemberType']) !== 'supervisor')) < 2
    )
  ],
]);

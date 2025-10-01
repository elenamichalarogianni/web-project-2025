<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_connect.php';


if (!isset($_COOKIE['userid']) || (($_COOKIE['role'] ?? '') !== 'Professor')) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'UNAUTHORIZED'], JSON_UNESCAPED_UNICODE);
  exit;
}
$userId = (int)($_COOKIE['userid'] ?? 0);


$profId = 0;
$q = $conn->prepare("SELECT ProfessorID FROM Professor WHERE UserID = ? LIMIT 1");
$q->bind_param('i', $userId);
$q->execute();
$q->bind_result($pid);
$q->fetch();
$q->close();

$profId = $pid ? (int)$pid : 0;
if ($profId <= 0) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'NO_PROFESSOR_ID'], JSON_UNESCAPED_UNICODE);
  exit;
}


function save_pdf_upload(int $topicId, array $file): array {
  
  if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'error' => 'NO_FILE'];
  }
  $maxBytes = 20 * 1024 * 1024; 
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if ($ext !== 'pdf') {
    return ['ok' => false, 'error' => 'BAD_TYPE'];
  }
  if ($file['size'] > $maxBytes) {
    return ['ok' => false, 'error' => 'FILE_TOO_LARGE'];
  }

  
  $root = dirname(__DIR__); 
  $destDir = $root . "/uploads/topics/{$topicId}";
  if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

  $fname = 'topic_' . $topicId . '_' . date('Ymd_His') . '.pdf';
  $abs   = $destDir . '/' . $fname;
  $rel   = 'uploads/topics/' . $topicId . '/' . $fname;

  if (!move_uploaded_file($file['tmp_name'], $abs)) {
    return ['ok' => false, 'error' => 'MOVE_FAILED'];
  }
  return ['ok' => true, 'rel' => $rel];
}


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  
  $stmt = $conn->prepare("
    SELECT TopicID, Title, Summary, PDFpath
    FROM ThesisTopic
    WHERE ProfessorID = ?
    ORDER BY TopicID DESC
  ");
  $stmt->bind_param('i', $profId);
  $stmt->execute();
  $res  = $stmt->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);

  echo json_encode(['ok' => true, 'items' => $rows], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  
  if (isset($_POST['mode']) && $_POST['mode'] === 'update') {
    $topicId = (int)($_POST['id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $summary = trim($_POST['description'] ?? '');
    $newUrl  = trim($_POST['pdfpath'] ?? '');

    if ($topicId <= 0 || $title === '') {
      echo json_encode(['ok' => false, 'error' => 'INVALID_INPUT'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    
    $oldPdf = '';
    $chk = $conn->prepare("SELECT PDFpath FROM ThesisTopic WHERE TopicID = ? AND ProfessorID = ?");
    $chk->bind_param('ii', $topicId, $profId);
    $chk->execute();
    $res = $chk->get_result();
    $row = $res->fetch_assoc();
    $chk->close();

    if (!$row) {
      echo json_encode(['ok' => false, 'error' => 'NOT_FOUND_OR_NOT_OWNER'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $oldPdf = (string)($row['PDFpath'] ?? '');
    $finalPdfPath = $oldPdf;

    
    if (isset($_FILES['pdfFile']) && ($_FILES['pdfFile']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $r = save_pdf_upload($topicId, $_FILES['pdfFile']);
      if (!$r['ok']) {
        echo json_encode(['ok' => false, 'error' => $r['error']], JSON_UNESCAPED_UNICODE);
        exit;
      }
      $finalPdfPath = $r['rel'];
    }
    
    elseif ($newUrl !== '') {
      $finalPdfPath = $newUrl;
    }
    

    $upd = $conn->prepare("
      UPDATE ThesisTopic
      SET Title = ?, Summary = ?, PDFpath = ?
      WHERE TopicID = ? AND ProfessorID = ?
    ");
    $upd->bind_param('sssii', $title, $summary, $finalPdfPath, $topicId, $profId);
    $ok = $upd->execute();
    $upd->close();

    echo json_encode(['ok' => (bool)$ok, 'id' => $topicId, 'pdf' => $finalPdfPath], JSON_UNESCAPED_UNICODE);
    exit;
  }

  
  $title   = trim($_POST['title'] ?? '');
  $summary = trim($_POST['description'] ?? '');
  $pdfPath = trim($_POST['pdfpath'] ?? ''); 

  if ($title === '') {
    echo json_encode(['ok' => false, 'error' => 'TITLE_REQUIRED'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  
  $ins = $conn->prepare("
    INSERT INTO ThesisTopic (Title, Summary, PDFpath, ProfessorID)
    VALUES (?,?,?,?)
  ");
  $ins->bind_param('sssi', $title, $summary, $pdfPath, $profId);
  if (!$ins->execute()) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'INSERT_FAILED'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $topicId = (int)$conn->insert_id;
  $ins->close();

  $finalPdfPath = $pdfPath;

  
  if (isset($_FILES['pdfFile']) && ($_FILES['pdfFile']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $r = save_pdf_upload($topicId, $_FILES['pdfFile']);
    if (!$r['ok']) {
      
      echo json_encode(['ok' => false, 'error' => $r['error'], 'id' => $topicId], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $finalPdfPath = $r['rel'];
    $upd = $conn->prepare("UPDATE ThesisTopic SET PDFpath=? WHERE TopicID=? AND ProfessorID=?");
    $upd->bind_param('sii', $finalPdfPath, $topicId, $profId);
    $upd->execute();
    $upd->close();
  }

  echo json_encode(['ok' => true, 'id' => $topicId, 'pdf' => $finalPdfPath], JSON_UNESCAPED_UNICODE);
  exit;
}


http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'METHOD_NOT_ALLOWED'], JSON_UNESCAPED_UNICODE);



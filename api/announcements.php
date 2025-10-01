<?php
require_once __DIR__ . '/../db_connect.php';

function bad($m,$c=400){ http_response_code($c);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error'=>$m], JSON_UNESCAPED_UNICODE); exit; }

function parse_ddmmyyyy($s){
     if(!preg_match('/^\d{8}$/',$s)) return null;
    $d=substr($s,0,2); $m=substr($s,2,2); $y=substr($s,4,4);
    if(!checkdate($m,$d,$y)) return null;
    return sprintf('%04d-%02d-%02d',$y,$m,$d);
}
function to_ddmmyyyy($ymd){ return date('dmY', strtotime($ymd)); }
function hhmm($t){ return $t? substr(str_replace(':','',$t),0,4):''; }

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';
$fmt  = strtolower($_GET['format'] ?? 'json');
if(!in_array($fmt,['json','xml'])) $fmt='json';

$today = date('Y-m-d'); $plus30 = date('Y-m-d', strtotime('+30 days'));
$fromY = $from ? parse_ddmmyyyy($from) : $today;
$toY   = $to   ? parse_ddmmyyyy($to)   : $plus30;
if(!$fromY || !$toY) bad('Invalid date. Use ddmmyyyy.');
if($fromY > $toY)    bad('"from" must be <= "to".');

$sql = "SELECT p.ExamDate, p.ExamTime, p.AnnouncementText,
               t.Title AS ThesisTitle
        FROM Presentation p
        JOIN Thesis t ON t.ThesisID = p.ThesisID
        WHERE p.ExamDate BETWEEN ? AND ?
        ORDER BY p.ExamDate, p.ExamTime";
$stmt=$conn->prepare($sql);
$stmt->bind_param('ss',$fromY,$toY);
$stmt->execute();
$res=$stmt->get_result();

$list=[];
while($r=$res->fetch_assoc()){
  $list[]=[
    'date' => to_ddmmyyyy($r['ExamDate']),
    'time' => hhmm($r['ExamTime']),
    'title'=> 'Δημόσια Παρουσίαση Διπλωματικής: '.$r['ThesisTitle'],
    'announcement_text' => $r['AnnouncementText'] ?? ''
  ];
}
$stmt->close();

$payload=['announcements'=>[
  'from'=>to_ddmmyyyy($fromY),
  'to'  =>to_ddmmyyyy($toY),
  'announcement_list'=>$list
]];

if($fmt==='xml'){
  header('Content-Type: application/xml; charset=utf-8');
  $x="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
  $x.="<announcements from=\"{$payload['announcements']['from']}\" to=\"{$payload['announcements']['to']}\">\n";
  foreach($list as $a){
    $x.="  <announcement>\n";
    $x.="    <date>{$a['date']}</date>\n";
    $x.="    <time>{$a['time']}</time>\n";
    $x.="    <title>".htmlspecialchars($a['title'],ENT_XML1|ENT_COMPAT,'UTF-8')."</title>\n";
    $x.="    <announcement_text>".htmlspecialchars($a['announcement_text'],ENT_XML1|ENT_COMPAT,'UTF-8')."</announcement_text>\n";
    $x.="  </announcement>\n";
  }
  $x.="</announcements>\n";
  echo $x;
} else {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

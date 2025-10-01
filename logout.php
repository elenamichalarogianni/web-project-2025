<?php


$cookiePath = '/'; 
foreach (['userid','email','role','fullname','professorid','secretaryid','studentid'] as $c) {
    setcookie($c, '', time() - 3600, $cookiePath);
}


header('Location: index.php');
exit;
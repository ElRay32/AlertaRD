<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$role = $_GET['role'] ?? 'reporter';
$_SESSION['role'] = $role;
if ($role==='reporter') { $_SESSION['user_id']=101; $_SESSION['name']='Reportero Demo'; }
elseif ($role==='validator') { $_SESSION['user_id']=201; $_SESSION['name']='Validador Demo'; }
else { $_SESSION['user_id']=201; $_SESSION['name']='Admin Demo'; $_SESSION['role']='admin'; }
header('Location: /alertard/index.php');

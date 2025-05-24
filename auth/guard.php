<?php
/* Uso: require_once '../auth/guard.php';          => sólo asegura sesión
         require_once '../auth/guard.php?admin';   => exige rol admin           */
session_start();
// Determinar si se requiere rol admin (páginas de usuarios)
$needAdmin = strpos($_SERVER['SCRIPT_FILENAME'], 'users') !== false; // ejemplo rápido
if (!isset($_SESSION['uid'])) {
  header('Location: ../auth/login.php');exit;
}
if ($needAdmin && $_SESSION['role']!=='admin') {
  http_response_code(403);die('Acceso restringido');
}
require_once '../config/db.php';
?>
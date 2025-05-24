<?php
session_start();
require_once '../config/db.php';
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u=trim($_POST['username']);
  $p=trim($_POST['password']);
  $stmt=$mysqli->prepare('SELECT id,pass_hash,role FROM users WHERE username=?');
  $stmt->bind_param('s',$u);$stmt->execute();
  $stmt->bind_result($uid,$hash,$role);
  if($stmt->fetch() && hash_equals($hash, hash('sha256',$p))) {
    $_SESSION['uid']=$uid;$_SESSION['user']=$u;$_SESSION['role']=$role;
    header('Location: ../public/dashboard.php');exit;
  } else $error='Credenciales incorrectas';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Login</title>
<link rel="stylesheet" href="../public/assets/style.css"></head><body class="full-center">
<form method="post" class="card" style="min-width:280px">
 <img src="../public/assets/logo.png" style="width:20%;">
 <?php if($error) echo "<p class='error'>$error</p>"; ?>
 <input name="username" placeholder="Usuario" required>
 <input type="password" name="password" placeholder="Contraseña" required>
 <button>Entrar</button>
</form></body></html>
<?php require_once '../auth/guard.php';
if($_SESSION['role']!=='admin'){ http_response_code(403);die('Solo admin'); }
if($_SERVER['REQUEST_METHOD']==='POST'){
  $u=trim($_POST['username']);$p=trim($_POST['password']);$r=$_POST['role'];
  $stmt=$mysqli->prepare('INSERT INTO users (username,pass_hash,role) VALUES (?,?,?)');
  $hash=hash('sha256',$p);$stmt->bind_param('sss',$u,$hash,$r);$stmt->execute();
  header('Location: users.php');exit;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Nuevo usuario</title>
<link rel="stylesheet" href="assets/style.css"></head><body>
<?php include 'nav.php'; ?>
<div class="container"><h1>Nuevo usuario</h1>
<form method="post" class="card">
 <input name="username" placeholder="Usuario" required>
 <input type="password" name="password" placeholder="Contraseña" required>
 <label>Rol
  <select name="role">
   <option value="admin">Admin</option>
   <option value="cobrador" selected>Cobrador</option>
  </select>
 </label>
 <button>Crear</button>
</form></div>
<script src="assets/app.js" defer></script></body></html>
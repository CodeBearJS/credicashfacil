<?php require_once '../auth/guard.php'; // bloquea a cobrador
$res=$mysqli->query('SELECT id,username,role FROM users ORDER BY id');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Usuarios</title>
<link rel="stylesheet" href="assets/style.css"></head><body>
<?php include 'nav.php'; ?>
<div class="container"><h1>Usuarios <a href="user_new.php" class="btn">+ Nuevo</a></h1>
<div class="table-wrapper"><table class="tbl"><thead><tr><th>ID</th><th>Usuario</th><th>Rol</th></tr></thead><tbody>
<?php while($u=$res->fetch_assoc()): ?>
<tr><td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['username']) ?></td><td><?= $u['role'] ?></td></tr>
<?php endwhile; ?></tbody></table></div></div>
<script src="assets/app.js" defer></script></body></html>
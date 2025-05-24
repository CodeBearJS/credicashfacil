<?php require_once '../auth/guard.php';

// ─── Parámetros de búsqueda ──────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$status = $_GET['s'] ?? 'activo'; // activo | cancelado | moroso | todos
$where = [];
if ($search) {
  $searchLike = '%'.$mysqli->real_escape_string($search).'%';
  $where[] = "(b.cedula LIKE '$searchLike' OR b.nombres LIKE '$searchLike')";
}
if ($status && $status!=='todos') {
  if ($status==='moroso') {
    // préstamo con al menos una cuota vencida y sin pagar
    $where[] = "EXISTS (SELECT 1 FROM installments i WHERE i.loan_id=l.id AND i.amount_paid=0 AND i.due_date < CURDATE())";
  } else {
    $where[] = "l.status='$status'";
  }
}
$sql  = "SELECT l.id,l.borrower_id,l.principal,l.total_payable,l.weekly_amount,l.start_date,l.status,
               b.nombres,b.cedula
        FROM loans l JOIN borrowers b ON b.id=l.borrower_id";
if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
$sql .= ' ORDER BY l.start_date DESC';
$res  = $mysqli->query($sql);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Préstamos</title>
<link rel="stylesheet" href="assets/style.css"></head><body>
<?php include 'nav.php'; ?>
<div class="container">
  <h1>Préstamos</h1>
  <form class="card grid-2" method="get" style="gap:.5rem;">
    <input name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por cédula o nombre">
    <select name="s">
      <option value="activo"   <?= $status==='activo'?'selected':'' ?>>Activos</option>
      <option value="moroso"   <?= $status==='moroso'?'selected':'' ?>>Morosos</option>
      <option value="cancelado"<?= $status==='cancelado'?'selected':'' ?>>Cancelados</option>
      <option value="todos"    <?= $status==='todos'?'selected':'' ?>>Todos</option>
    </select>
    <button style="grid-column:span 2;">Filtrar</button>
  </form>

  <div class="table-wrapper"><table class="tbl">
    <thead>
        <tr>
      <th>ID</th>
      <td data-label="Cédula">
  <a href="borrower.php?bid=<?= $l['borrower_id'] ?>" target="_blank">
     <?= htmlspecialchars($l['cedula']) ?>
  </a>
</td>
      <th>Nombre</th><th>Monto</th><th>Total</th><th>Semanal</th><th>Inicio</th><th>Estado</th><th></th>
    </tr></thead><tbody>
    <?php while($l=$res->fetch_assoc()): ?>
      <tr>
        <td><?= $l['id'] ?></td>
<td data-label="Cédula">
  <a href="borrower.php?bid=<?= $l['borrower_id'] ?>" target="_blank">
     <?= htmlspecialchars($l['cedula']) ?>
  </a>
</td>
<td data-label="Nombre"><?= htmlspecialchars($l['nombres']) ?></td>
<td data-label="Monto">$<?= number_format($l['principal'],2) ?></td>
        <td>$<?= number_format($l['total_payable'],2) ?></td>
        <td>$<?= number_format($l['weekly_amount'],2) ?></td>
        <td><?= date('d/m/Y',strtotime($l['start_date'])) ?></td>
        <td><?= ucfirst($l['status']) ?></td>
        <td><a href="payments.php?loan_id=<?= $l['id'] ?>" class="btn">Pagos</a></td>
<a href="reports/receipt.php?loan_id=<?= $l['id'] ?>" class="btn" target="_blank">PDF</a>
      </tr>
    <?php endwhile; ?>
    </tbody></table></div>
</div>
<script src="assets/app.js" defer></script>
</body></html>
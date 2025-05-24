<?php
require_once '../auth/guard.php';
require_once __DIR__.'/../config/db.php';

$bid = intval($_GET['bid'] ?? 0);
$bor = $mysqli->query("SELECT * FROM borrowers WHERE id=$bid")->fetch_assoc()
       or die('Prestatario no encontrado');

/* Historial de préstamos */
$sqlLoans = "SELECT id, principal, total_payable, start_date, status
             FROM loans WHERE borrower_id=$bid ORDER BY id DESC";
$loans = $mysqli->query($sqlLoans);

/* Pagos por préstamo (group by loan) */
$sqlPays = "
  SELECT l.id        AS loan_id,
         COUNT(i.id) AS cuotas,
         SUM(i.amount_paid) AS pagado,
         SUM(i.amount_due+i.late_fee) AS total
  FROM loans l
  JOIN installments i ON i.loan_id=l.id
  WHERE l.borrower_id=$bid
  GROUP BY l.id";
$pays = $mysqli->query($sqlPays)->fetch_all(MYSQLI_ASSOC | MYSQLI_NUM);
$payIndex = array_column($pays, null, 0);  // loan_id => fila
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= $bor['nombres'] ?> – Historial</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
<style>
.card{background:#fff;padding:1.25rem;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.08);margin-bottom:1.5rem}
.grid-2{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.8rem}
.tbl td,.tbl th{text-align:center}
.badge-ok{background:#00b23b;color:#fff;padding:2px 6px;border-radius:4px;font-size:.75rem}
.doc-grid{display:flex;gap:1rem;flex-wrap:wrap}
.doc-thumb{width:120px;border:1px solid #aaa;border-radius:6px;cursor:pointer;
           transition:transform .2s}
.doc-thumb:hover{transform:scale(1.05)}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
<h1>Ficha de Prestatario</h1>

<div class="card grid-2">
 <p><strong>Cédula:</strong> <?= $bor['cedula'] ?></p>
 <p><strong>Nombre:</strong> <?= $bor['nombres'] ?></p>
 <p><strong>Tel.:</strong> <?= $bor['telefono'] ?></p>
 <p><strong>Dirección:</strong> <?= $bor['direccion'] ?></p>
 <p><strong>Banco Pago Móvil:</strong> <?= $bor['pago_banco'] ?></p>
 <p><strong>Cédula / Teléfono Pago Móvil:</strong> <?= $bor['pago_cedula'].' / '.$bor['pago_telefono'] ?></p>
  <p><strong>Ingreso Sem. 1 + Ingreso Sem. 2:</strong> <?= $bor['ingreso_semanal'].'$ + '.$bor['ingreso_semanal2'] ?>$</p>
</div>
<?php if($bor['foto_path'] || $bor['cedula_path']): ?>
<h2>Documentos</h2>
<div class="doc-grid">
  <?php if($bor['foto_path'] && file_exists('../'.$bor['foto_path'])): ?>
    <img src="../<?= $bor['foto_path'] ?>" class="doc-thumb"
         data-full="../<?= $bor['foto_path'] ?>" alt="Foto rostro">
  <?php endif; ?>
  <?php if($bor['cedula_path'] && file_exists('../'.$bor['cedula_path'])): ?>
    <img src="../<?= $bor['cedula_path'] ?>" class="doc-thumb"
         data-full="../<?= $bor['cedula_path'] ?>" alt="Cédula">
  <?php endif; ?>
</div>
<?php endif; ?>
<h2>Historial de Préstamos</h2>
<div class="table-wrapper"><table class="tbl">
 <thead><tr>
   <th>ID</th><th>Monto</th><th>Total</th><th>Inicio</th><th>Estado</th>
   <th>Pagado</th><th>Restante</th><th></th>
 </tr></thead><tbody>
 <?php while($L = $loans->fetch_assoc()):
       $p = $payIndex[$L['id']] ?? [0,0,0,0];
       $pagado = $p[2];
       $rest   = $L['total_payable'] - $pagado;
 ?>
  <tr>
    <td><?= $L['id'] ?></td>
    <td>$<?= number_format($L['principal'],2) ?></td>
    <td>$<?= number_format($L['total_payable'],2) ?></td>
    <td><?= date('d/m/Y',strtotime($L['start_date'])) ?></td>
    <td><?= ucfirst($L['status']) ?></td>
    <td>$<?= number_format($pagado,2) ?></td>
    <td>$<?= number_format($rest,2) ?></td>
    <td>
      <a href="payments.php?loan_id=<?= $L['id'] ?>" class="btn" target="_blank">
        Ver pagos
      </a>
    </td>
  </tr>
 <?php endwhile; ?>
 </tbody></table></div>
</div>
<!-- viewer modal -->
<div id="viewer" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
     align-items:center;justify-content:center">
  <img id="viewerImg" style="max-width:90%;max-height:90%;border:4px solid #fff;border-radius:8px">
</div>

<script>
document.querySelectorAll('.doc-thumb').forEach(img=>{
  img.addEventListener('click',()=>openViewer(img.dataset.full));
});
function openViewer(src){
  const v = document.getElementById('viewer');
  document.getElementById('viewerImg').src = src;
  v.style.display='flex';
  v.onclick = ()=> v.style.display='none';
}
</script>
</body>
</html>

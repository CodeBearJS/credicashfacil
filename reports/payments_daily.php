<?php
require_once '../auth/guard.php';
require_once __DIR__.'/../config/db.php';

$day = $_GET['d'] ?? date('Y-m-d');

/* ─── Selecciona abonos hechos en la fecha d ─── */
$sql = "
 SELECT  i.id,                             /* ID de la cuota                */
         b.cedula,
         b.nombres,
         l.id   AS loan_id,
         i.pay_amount_bs   AS monto_bs,    /* lo que guardas en Bs          */
         i.pay_ref
 FROM installments i
 JOIN loans      l ON l.id = i.loan_id
 JOIN borrowers  b ON b.id = l.borrower_id
 WHERE i.pay_date = '$day'                /*   <-- el filtro de la fecha   */
   AND i.amount_paid > 0                  /* abonos mayores que 0          */
 ORDER BY i.pay_date, b.nombres";

$res = $mysqli->query($sql);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Pagos <?= $day ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/style.css">
<style>
.tbl td,.tbl th{text-align:center}
.btn{background:#006633;color:#fff;border:none;padding:.4rem .8rem;border-radius:6px}
</style>
</head>
<body>
<?php include '../nav.php'; ?>
<div class="container">
<h1>Pagos del <?= date('d/m/Y',strtotime($day)) ?></h1>

<!-- filtro fecha -->
<form method="get" class="card" style="max-width:280px;margin-bottom:1rem">
 <input type="date" name="d" value="<?= $day ?>" style="width:100%;margin-bottom:.4rem">
 <button class="btn" style="width:100%">Filtrar</button>
</form>

<!-- tabla -->
<div class="table-wrapper">
<table class="tbl">
 <thead>
     <tr>
     <th>#</th>
     <th>Cédula</th>
     <th>Nombre</th>
     <th>Préstamo</th>
     <th>Monto Bs</th>
     <th>Ref.</th>
     </tr>
     </thead>
 <tbody>
 <?php $tot=0; while($r=$res->fetch_assoc()): $tot+=$r['monto_bs']; ?>
  <tr>
    <td><?= $r['id'] ?></td>
    <td><?= $r['cedula'] ?></td>
    <td><?= htmlspecialchars($r['nombres']) ?></td>
    <td><?= "#".$r['loan_id'] ?></td>
    <td><?= number_format($r['monto_bs'],2,',','.') ?></td>
    <td><?= $r['pay_ref'] ?></td>
  </tr>
 <?php endwhile; ?>
 </tbody>
 <tfoot><tr><th colspan="4">Total</th><th><?= number_format($tot,2,',','.') ?></th><th></th></tr></tfoot>
</table>
</div>

<!-- exportar -->
<form action="export_pdf_daily.php" method="post" style="display:inline">
  <input type="hidden" name="sql" value="<?= htmlspecialchars($sql,ENT_QUOTES) ?>">
  <input type="hidden" name="title" value="Pagos <?= $day ?>">
  <button class="btn">PDF</button>
</form>

<form action="export_csv_daily.php" method="post" style="display:inline">
  <input type="hidden" name="sql" value="<?= htmlspecialchars($sql,ENT_QUOTES) ?>">
  <button class="btn">CSV</button>
</form>

</div>
</body>
</html>

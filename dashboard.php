<?php require_once '../auth/guard.php';
// ── Métricas globales ───────────────────────────────────────────────────
$totLoans  = $mysqli->query('SELECT COUNT(*) c FROM loans')->fetch_assoc()['c'];
$totActive = $mysqli->query("SELECT COUNT(*) c FROM loans WHERE status='activo'")->fetch_assoc()['c'];
$totMoroso = $mysqli->query("SELECT COUNT(DISTINCT loan_id) c FROM installments WHERE amount_paid=0 AND due_date<CURDATE()") ->fetch_assoc()['c'];
$capital   = $mysqli->query('SELECT SUM(principal) s FROM loans')->fetch_assoc()['s'] ?? 0;
$pendiente = $mysqli->query('SELECT SUM(amount_due+late_fee-amount_paid) s FROM installments')->fetch_assoc()['s'] ?? 0;

// ── Datos para gráfico de barras (prestamos por tipo) ──────────────────
$byType = $mysqli->query("SELECT borrower_type, COUNT(*) c FROM loans l JOIN borrowers b ON b.id=l.borrower_id GROUP BY borrower_type");
$types = [1=>0,2=>0,3=>0];
while($t=$byType->fetch_assoc()) $types[$t['borrower_type']] = $t['c'];

// ── Próximos vencimientos (7 días) ─────────────────────────────────────
$due = $mysqli->query("SELECT i.due_date, b.nombres, b.cedula, i.amount_due+i.late_fee - i.amount_paid AS saldo
                       FROM installments i
                       JOIN loans l ON l.id=i.loan_id
                       JOIN borrowers b ON b.id=l.borrower_id
                       WHERE i.amount_paid=0 AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                       ORDER BY i.due_date LIMIT 10");
?>
<!doctype html>
<html lang="es">
  <?php require_once '../auth/guard.php';
/*  ─── queries idénticas a las tuyas ───  */
/* … $totLoans, $totActive, $totMoroso, $capital, $pendiente, $types, $due … */
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Dashboard | CrediCashFácil</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="assets/dashboard.css"><!-- estilos nuevos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<?php include 'nav.php'; ?>

<main class="container">
  <!-- MÉTRICAS -->
  <section class="metrics">
    <article class="metric">
      <h3>Préstamos<br><span><?= $totLoans ?></span></h3>
    </article>
    <article class="metric">
      <h3>Activos<br><span><?= $totActive ?></span></h3>
    </article>
    <article class="metric warning">
      <h3>Morosos<br><span><?= $totMoroso ?></span></h3>
    </article>
    <article class="metric">
      <h3>Capital<br><span>$<?= number_format($capital,2) ?></span></h3>
    </article>
    <article class="metric">
      <h3>Saldo<br><span>$<?= number_format($pendiente,2) ?></span></h3>
    </article>
  </section>

  <!-- GRÁFICO -->
  <section class="panel">
    <header><h2>Distribución de préstamos por tipo</h2></header>
    <canvas id="chartTipo" height="120"></canvas>
  </section>

  <!-- VENCIMIENTOS -->
  <section class="panel">
    <header><h2>Próximos vencimientos (7 días)</h2></header>
    <div class="table-scroll">
      <table class="tbl">
        <thead>
          <tr><th>Fecha</th><th>Cédula</th><th>Nombre</th><th>Saldo cuota</th></tr>
        </thead>
        <tbody>
        <?php while($d=$due->fetch_assoc()): ?>
          <tr>
            <td><?= date('d/m/Y',strtotime($d['due_date'])) ?></td>
            <td><?= $d['cedula'] ?></td>
            <td><?= htmlspecialchars($d['nombres']) ?></td>
            <td>$<?= number_format($d['saldo'],2) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
const ctx=document.getElementById('chartTipo');
new Chart(ctx,{
  type:'bar',
  data:{
    labels:['Dependencia','Independiente','Negocio'],
    datasets:[{
      data:[<?= $types[1] ?>,<?= $types[2] ?>,<?= $types[3] ?>],
      backgroundColor:['#57b773','#ffd900','#2ebd59'],
      borderRadius:6
    }]
  },
  options:{
    plugins:{legend:{display:false}},
    scales:{y:{beginAtZero:true}}
}});
</script>
<script src="assets/app.js" defer></script>
</body>
</html>
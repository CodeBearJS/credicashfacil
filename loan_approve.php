<?php
require_once '../auth/guard.php';
require_once __DIR__.'/../config/db.php';

$bid = intval($_GET['bid'] ?? 0);
if(!$bid){
  header('Location: loans.php'); exit;
}

$bor = $mysqli->query("SELECT * FROM borrowers WHERE id=$bid")->fetch_assoc()
       or die('Prestatario no encontrado');

// ─── AL APROBAR ────────────────────────────────────────────────────
if($_SERVER['REQUEST_METHOD']==='POST'){
  $monto  = floatval($_POST['monto']);
  $rate   = 4.5;              // interés semanal %
  $weeks  = 9;
  $total  = round($monto*(1+$rate/100*$weeks),2);
  $weekly = round($total/$weeks,2);

  $mysqli->query("INSERT INTO loans
      (borrower_id,principal,interest_weekly,weeks,total_payable,
       weekly_amount,start_date,status)
      VALUES ($bid,$monto,$rate,$weeks,$total,$weekly,CURDATE(),'activo')");
  $lid = $mysqli->insert_id;

  // cuotas
  for($i=1;$i<=$weeks;$i++){
    $due = date('Y-m-d', strtotime("+$i week"));
    $mysqli->query("INSERT INTO installments
          (loan_id,due_date,amount_due) VALUES ($lid,'$due',$weekly)");
  }

  // redirige a la planilla
  header("Location: loan_agreement.php?id=$lid");
  exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Aprobar préstamo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
<style>
.card{max-width:500px;margin:auto;padding:1.5rem;border-radius:12px;
      box-shadow:0 2px 6px rgba(0,0,0,.08);background:#fff}
h2{margin-bottom:1rem}
select,button{padding:.6rem .8rem;border:1px solid #ccc;border-radius:6px;
      font-size:1rem}
button{background:#006633;color:#fff;border:none;cursor:pointer;margin-top:1rem}
button:hover{opacity:.9}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
  <form class="card" method="post">
    <h2> Aprobar préstamo de <?= htmlspecialchars($bor['nombres']) ?> </h2>

    <label>Monto a prestar
      <select name="monto" required>
        <option value="25">25 USD</option>
                <option value="50">50 USD</option>
                        <option value="100">100 USD</option>
                                <option value="250">250 USD</option>
                                     <option value="500">500 USD</option>
                                                                             <option value="1000">1000 USD</option>
        <!-- futuro: <option value="50">50 USD</option> -->
      </select>
    </label>

    <button>Aprobar y generar planilla</button>
  </form>
</div>
</body>
</html>
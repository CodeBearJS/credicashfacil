<?php require_once '../auth/guard.php';
$loan_id = intval($_GET['loan_id'] ?? 0);
if(!$loan_id) die('Falta préstamo');

// Datos del préstamo y prestatario
$sql = "SELECT l.*, b.nombres, b.cedula FROM loans l JOIN borrowers b ON b.id=l.borrower_id WHERE l.id=$loan_id";
$loan = $mysqli->query($sql)->fetch_assoc() or die('Préstamo no encontrado');

// Registrar pago
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['pay_ref'])){
  $inst_id = intval($_POST['installment_id']);
  $monto   = floatval($_POST['monto']);
  $ref     = $mysqli->real_escape_string($_POST['pay_ref']);
  $pdate   = $_POST['pay_date'];
  
  // obtener cuota
  
  $q = $mysqli->query("SELECT amount_due,late_fee,amount_paid,due_date FROM installments WHERE id=$inst_id AND loan_id=$loan_id")->fetch_assoc();
  if(!$q) die('Cuota inválida');
  $due = $q['amount_due'] + $q['late_fee'];
  $remaining = $due - $q['amount_paid'];
  if($monto > $remaining) $monto = $remaining; // cap

  // registrar
  $mysqli->query("UPDATE installments SET
      amount_paid = amount_paid + $monto,
      pay_ref = '$ref',
      pay_date = '$pdate',
      pay_amount_bs = $monto,
      paid_date = CURDATE()
      WHERE id=$inst_id");

 

  // marcar loan cancelado si todas las cuotas pagadas
  $chk = $mysqli->query("SELECT COUNT(*) c FROM installments WHERE loan_id=$loan_id AND amount_due+late_fee>amount_paid")->fetch_assoc()['c'];
  if($chk==0) $mysqli->query("UPDATE loans SET status='cancelado' WHERE id=$loan_id");

  header('Location: payments.php?loan_id='.$loan_id.'&ok=1');exit;
}

// Calcular recargos: 5 % sobre amount_due si cuota sin pagar y vencida
$mysqli->query("UPDATE installments SET late_fee = ROUND(amount_due*0.05,2) WHERE loan_id=$loan_id AND amount_paid=0 AND due_date < CURDATE()");

$inst = $mysqli->query("SELECT * FROM installments WHERE loan_id=$loan_id ORDER BY due_date");

/* verificación via ajax */
if(isset($_POST['toggleVerif'])){
  $id = intval($_POST['toggleVerif']);
  $v  = intval($_POST['state']);
  $mysqli->query("UPDATE installments SET pay_verified=$v WHERE id=$id");
  exit;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pagos</title>
<link rel="stylesheet" href="assets/style.css">
<style>
/* botón verde y tabla */
.btn{background:#006633;color:#fff;border:none;padding:.35rem .7rem;border-radius:5px;cursor:pointer}
.btn:disabled{opacity:.5;cursor:default}
.tbl td,.tbl th{text-align:center}
.badge-paid{color:#fff;background:#00b23b;padding:2px 6px;border-radius:4px;font-size:.75rem}

/* MODAL */
#modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.55)}
.modal-card{background:#fff;padding:1.5rem;border-radius:10px;max-width:380px;width:90%;box-shadow:0 4px 12px rgba(0,0,0,.2)}
.modal-card h3{margin-top:0}
.modal-card label{display:flex;flex-direction:column;font-size:.85rem;margin:.5rem 0}
.modal-card input{padding:.45rem;border:1px solid #ccc;border-radius:5px}
.modal-card .actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1rem}
</style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
<h1>Pagos – Préstamo #<?= $loan_id ?></h1>
<p><strong><?= htmlspecialchars($loan['nombres']) ?></strong> – Cédula: <?= $loan['cedula'] ?></p>
<?php if(isset($_GET['ok'])) echo '<p style="color:green">Pago registrado</p>'; ?>
<div class="table-wrapper"><table class="tbl">
 <thead><tr><th>#</th><th>Vence</th><th>Monto</th><th>Mora</th><th>Total</th><th>Pagado</th><th>Restante</th><th></th></tr></thead><tbody>
<?php while($i=$inst->fetch_assoc()):
  $total = $i['amount_due']+$i['late_fee'];
  $rest  = $total - $i['amount_paid'];
?>
<tr>
 <td><?= $i['id'] ?></td>
 <td><?= date('d/m/Y',strtotime($i['due_date'])) ?></td>
 <td>$<?= number_format($i['amount_due'],2) ?></td>
 <td>$<?= number_format($i['late_fee'],2) ?></td>
 <td>$<?= number_format($total,2) ?></td>
 <td>$<?= number_format($i['amount_paid'],2) ?></td>
 <td>$<?= number_format($rest,2) ?></td>
 <td>
<?php if($rest>0): ?>
  <button class="btn" onclick="openModal(<?= $i['id'] ?>,<?= $rest ?>)">Abonar</button>
<?php else: ?>
  <span class="badge-paid">Pagado</span>
  <button class="btn" onclick="showDetails(<?= $i['id'] ?>)">Detalles</button>
<?php endif; ?>
</td>
<td>
  <?php if($rest==0): ?>
    <input type="checkbox" <?= $i['pay_verified']?'checked':'' ?>
       onchange="toggleVerif(<?= $i['id'] ?>,this.checked)">
  <?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody></table></div>
</div>
<script src="assets/app.js" defer></script>

<div id="modal">
  <form class="modal-card" id="payForm" method="post">
    <h3>Registrar abono</h3>
    <input type="hidden" name="installment_id" id="modalInst">
    <label>Fecha
      <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required>
    </label>
    <label>Monto (Bs o $)
      <input type="number" name="monto" step="0.01" required>
    </label>
    <label>Referencia bancaria
      <input name="pay_ref" maxlength="30" required>
    </label>
    <div class="actions">
      <button type="button" class="btn" onclick="closeModal()">Cancelar</button>
      <button class="btn">Guardar</button>
    </div>
  </form>
</div>
<!-- ① Modal de DETALLES  -->
<div id="modalDetail" style="display:none;position:fixed;inset:0;
    align-items:center;justify-content:center;background:rgba(0,0,0,.55)">
  <div class="modal-card" style="max-width:420px">
     <h3>Detalle del abono</h3>
     <div id="detailBody" style="line-height:1.5"></div>
     <div class="actions" style="text-align:right;margin-top:1rem">
        <button class="btn" onclick="closeDetail()">Cerrar</button>
     </div>
  </div>
</div>
<script>
/* modal helpers */
const modal  = document.getElementById('modal');
const form   = document.getElementById('payForm');
function openModal(id,rest){
  modal.style.display='flex';
  document.getElementById('modalInst').value=id;
  form.monto.value = rest.toFixed(2);
}
function closeModal(){modal.style.display='none'}
/* cerrar al hacer click fuera de la tarjeta */
modal.addEventListener('click',e=>{if(e.target===modal)closeModal()});

/* detalles */
function showDetails(id){
  fetch('ajax/installment_detail.php?id='+id)
   .then(r=>r.text()).then(html=>alert(html));
}

/* toggle verificación */
function toggleVerif(id,state){
  fetch('',{method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'toggleVerif='+id+'&state='+(state?1:0)
  });
}
/* ----- DETALLES en modal ----- */
function showDetails(id){
  fetch('ajax/installment_detail.php?id='+id)
   .then(r=>r.text())
   .then(html=>{
      document.getElementById('detailBody').innerHTML = html;
      document.getElementById('modalDetail').style.display = 'flex';
   });
}
function closeDetail(){
  document.getElementById('modalDetail').style.display = 'none';
}
/* cerrar si clic fuera */
document.getElementById('modalDetail').addEventListener('click',e=>{
  if(e.target.id==='modalDetail') closeDetail();
});
</script>
</body>
</html>
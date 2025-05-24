<?php
require_once '../../config/db.php';
$id = intval($_GET['id']??0);
$row = $mysqli->query("SELECT pay_date,pay_ref,pay_amount_bs FROM installments WHERE id=$id")->fetch_assoc();
echo "<strong>Fecha:</strong> {$row['pay_date']}<br>
      <strong>Monto:</strong> {$row['pay_amount_bs']}<br>
      <strong>Referencia:</strong> {$row['pay_ref']}";
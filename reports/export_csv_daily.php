<?php
require_once '../auth/guard.php';
require_once __DIR__.'/../config/db.php';

$sql  = $_POST['sql'];
$res  = $mysqli->query($sql);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="pagos_'.date('Ymd').'.csv"');

$out = fopen('php://output','w');
fputcsv($out,['ID','Cedula','Nombre','Prestamo','Monto Bs','Referencia'],';');

while($r=$res->fetch_assoc()){
  fputcsv($out,[
     $r['id'],$r['cedula'],$r['nombres'],$r['loan_id'],
     number_format($r['pay_amount_bs'],2,'.',''),
     $r['pay_ref']
  ],';');
}
fclose($out);

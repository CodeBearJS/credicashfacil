<?php require_once '../auth/guard.php';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="prestamos.csv"');

$out = fopen('php://output', 'w');
// Cabecera
fputcsv($out, ['ID','Cédula','Nombre','Capital','Total','Saldo pendiente','Inicio','Estado']);

$res = $mysqli->query("SELECT l.id,
                              b.cedula,
                              b.nombres,
                              l.principal,
                              l.total_payable,
                              (SELECT SUM(amount_due+late_fee-amount_paid) FROM installments WHERE loan_id=l.id) AS saldo,
                              l.start_date,
                              l.status
                       FROM loans l
                       JOIN borrowers b ON b.id=l.borrower_id
                       ORDER BY l.id");
while($r = $res->fetch_assoc()){
  fputcsv($out, $r);
}
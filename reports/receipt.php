<?php require_once '../auth/guard.php';
require_once __DIR__.'/../vendor/fpdf/fpdf.php';
$loan_id = intval($_GET['loan_id'] ?? 0);
if(!$loan_id) die('Falta préstamo');
// Datos préstamo + prestatario
$q = $mysqli->query("SELECT l.*, b.nombres, b.cedula FROM loans l JOIN borrowers b ON b.id=l.borrower_id WHERE l.id=$loan_id");
$loan = $q->fetch_assoc() or die('No encontrado');

$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'COMPROBANTE DE PRESTAMO',0,1,'C');
$pdf->Ln(4);
$pdf->SetFont('Arial','',12);
$pdf->Cell(40,8,'ID Prestamo:',0,0); $pdf->Cell(40,8,$loan_id,0,1);
$pdf->Cell(40,8,'Nombre:',0,0);     $pdf->Cell(0,8,$loan['nombres'],0,1);
$pdf->Cell(40,8,'Cedula:',0,0);     $pdf->Cell(0,8,$loan['cedula'],0,1);
$pdf->Cell(40,8,'Capital:',0,0);    $pdf->Cell(0,8,'$'.number_format($loan['principal'],2),0,1);
$pdf->Cell(40,8,'Total a pagar:',0,0);$pdf->Cell(0,8,'$'.number_format($loan['total_payable'],2),0,1);
$pdf->Cell(40,8,'Cuota semanal:',0,0);$pdf->Cell(0,8,'$'.number_format($loan['weekly_amount'],2),0,1);
$pdf->Cell(40,8,'Inicio:',0,0);     $pdf->Cell(0,8,date('d/m/Y',strtotime($loan['start_date'])),0,1);
$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Detalle de cuotas',0,1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(35,7,'#',1);$pdf->Cell(35,7,'Vence',1);$pdf->Cell(35,7,'Monto',1);$pdf->Cell(35,7,'Pagado',1);$pdf->Cell(35,7,'Mora',1);$pdf->Ln();
$res=$mysqli->query("SELECT * FROM installments WHERE loan_id=$loan_id ORDER BY due_date");
while($i=$res->fetch_assoc()){
  $pdf->Cell(35,7,$i['id'],1);
  $pdf->Cell(35,7,date('d/m',strtotime($i['due_date'])),1);
  $pdf->Cell(35,7,number_format($i['amount_due'],2),1);
  $pdf->Cell(35,7,number_format($i['amount_paid'],2),1);
  $pdf->Cell(35,7,number_format($i['late_fee'],2),1);
  $pdf->Ln();
}
$pdf->Output('I','recibo_'.$loan_id.'.pdf');
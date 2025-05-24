<?php
require_once '../auth/guard.php';
require_once __DIR__.'/../../vendor/fpdf/fpdf.php';
require_once __DIR__.'/../config/db.php';

$sql   = $_POST['sql'];
$title = $_POST['title'];
$res   = $mysqli->query($sql);

class PDF extends FPDF{
  function Header(){
    $this->SetDrawColor(0,178,59);
    $this->Rect(8,8,200,263);                       /* marco */
    $this->SetFont('Arial','B',14);
    $this->SetFillColor(0,178,59);
    $this->SetTextColor(255);
    $this->Cell(0,10,utf8_decode($_POST['title']),0,1,'C',true);
    $this->Ln(4);
    $this->SetTextColor(0);
  }
}

$pdf = new PDF('P','mm','Letter');
$pdf->AddPage();
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(0,178,59); $pdf->SetTextColor(255);
$pdf->Cell(12,6,'#',1,0,'C',true);
$pdf->Cell(22,6,'Cédula',1,0,'C',true);
$pdf->Cell(55,6,'Nombre',1,0,'C',true);
$pdf->Cell(20,6,'Prést.',1,0,'C',true);
$pdf->Cell(30,6,'Monto Bs',1,0,'C',true);
$pdf->Cell(35,6,'Referencia',1,1,'C',true);
$pdf->SetFont('Arial','',9); $pdf->SetTextColor(0);

while($r=$res->fetch_assoc()){
  $pdf->Cell(12,6,$r['id'],1,0,'C');
  $pdf->Cell(22,6,$r['cedula'],1);
  $pdf->Cell(55,6,substr(utf8_decode($r['nombres']),0,28),1);
  $pdf->Cell(20,6,$r['loan_id'],1,0,'C');
  $pdf->Cell(30,6,number_format($r['monto_bs'],2,',','.'),1,0,'R');  // <- aquí
  $pdf->Cell(35,6,$r['pay_ref'],1,1);
}
$pdf->Output('I','pagos_'.$title.'.pdf');

<?php
require_once '../auth/guard.php';
require_once __DIR__.'/../vendor/fpdf/fpdf.php';

/* ── CONSULTA: cedula, nombre, teléfono y saldo vencido ─────────────── */
$sql = "
  SELECT l.id,
         b.cedula,
         b.nombres,
         b.telefono,
         SUM(i.amount_due + i.late_fee - i.amount_paid) AS saldo
  FROM loans l
  JOIN borrowers b ON b.id = l.borrower_id
  JOIN installments i ON i.loan_id = l.id
  WHERE i.amount_paid = 0
    AND i.due_date   < CURDATE()
  GROUP BY l.id
  ORDER BY saldo DESC";
$res = $mysqli->query($sql);

/* ── SUBCLASE FPDF CON MARCO Y ENCABEZADO ───────────────────────────── */
class MorososPDF extends FPDF{
  function Header(){
    // Marco
    $this->SetDrawColor(0,178,59);
    $this->Rect(8,8,200,263);          // x,y,w,h   (Carta interior = 200×263)

    // Título
    $this->Ln(10);
    $this->SetFont('Arial','B',16);
    $this->SetFillColor(0,178,59);
    $this->SetTextColor(255);
    $this->Cell(0,10,utf8_decode('Reporte de Préstamos Morosos'),0,1,'C',true);
    $this->Ln(4);
    $this->SetTextColor(0);
  }
  function Footer(){
    $this->SetY(-15);
    $this->SetFont('Arial','I',8);
    $this->Cell(0,5,'Página '.$this->PageNo().'/{nb}',0,0,'C');
  }
}

$pdf = new MorososPDF('P','mm','Letter');
$pdf->AliasNbPages();
$pdf->AddPage();

/* ── CABECERA DE TABLA ──────────────────────────────────────────────── */
$colW = [15,25,70,35,35];                 // ID, Cédula, Nombre, Tel, Saldo
$tableWidth = array_sum($colW);           // 180 mm
$leftX = (210 - $tableWidth) / 2;         // centrado (Carta interior 210 mm)

$pdf->SetXY($leftX, $pdf->GetY());
$pdf->SetFont('Arial','B',10);
$pdf->SetFillColor(0,178,59);
$pdf->SetTextColor(255);
$head = ['ID','Cédula','Nombre','Teléfono','Saldo ($)'];
foreach($head as $i=>$h){
  $pdf->Cell($colW[$i],7,utf8_decode($h),1,0,'C',true);
}
$pdf->Ln();

/* ── FILAS ──────────────────────────────────────────────────────────── */
$pdf->SetFont('Arial','',9);
$pdf->SetTextColor(0);

while($r = $res->fetch_assoc()){
  $pdf->SetX($leftX);
  $pdf->Cell($colW[0],6,$r['id'],1,0,'C');
  $pdf->Cell($colW[1],6,$r['cedula'],1,0,'C');
  $pdf->Cell($colW[2],6,utf8_decode(substr($r['nombres'],0,32)),1);
  $pdf->Cell($colW[3],6,$r['telefono'],1,0,'C');
  $pdf->Cell($colW[4],6,number_format($r['saldo'],2),1,1,'R');
}

$pdf->Output('I','morosos.pdf');
exit;

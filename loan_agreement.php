<?php
ini_set(‘display_errors’, 1);
ini_set(‘display_startup_errors’, 1);
error_reporting(E_ALL);

require_once '../auth/guard.php';
require_once __DIR__ . '/fpdf/fpdf.php';
require_once __DIR__ . '/../config/db.php';       // contiene constantes EMP_*




/* ——— forzar UTF-8 en conexión ——— */
$mysqli->set_charset('utf8');

/* ——— obtengo el préstamo ——— */
$lid = intval($_GET['id'] ?? 0);
$loan = $mysqli->query("
  SELECT l.*, b.*
  FROM loans l
  JOIN borrowers b ON b.id=l.borrower_id
  WHERE l.id=$lid
")->fetch_assoc() or die('Préstamo no encontrado');

$cuotas = $mysqli->query("SELECT due_date, amount_due
                          FROM installments WHERE loan_id=$lid
                          ORDER BY due_date");

/* ——— helper para utf8 -> CP1252 solo si hace falta ——— */
function enc($txt){
  return mb_check_encoding($txt,'UTF-8')
         ? iconv('UTF-8','windows-1252//TRANSLIT',$txt)
         : $txt;
}


$monto_pagar=number_format($loan['total_payable'],0);
if ($monto_pagar==35){
    $monto_a_pagar=" TREINTA Y CINCO DÓLARES ";
}
/* ——— subclase FPDF ——— */
class PDF extends FPDF{
  function Header(){
          $this->SetDrawColor(0,102,51);
    $this->Rect(8,8,200,263);  // x,y,ancho,alto
    if(file_exists(EMP_LOGO)) $this->Image(EMP_LOGO,10,8,28);
/*    $this->SetFont('Arial','B',15);
    $this->SetTextColor(0,102,51);
    $this->Cell(0,8, utf8_decode(CrediCashFácil),0,1,'C'); */
    $this->Image('assets/logo.png', 90, 8, 25, 0);
    $this->Ln(6);
  }
  function Footer(){
    $this->SetY(-15);
    $this->SetFont('Arial','I',8);

  }
 function titulo($txt){
  $this->SetFillColor(0,102,51);        // verde
  $this->SetTextColor(255);             // blanco
  $this->SetFont('Arial','B',12);
  $this->Cell(0,7,utf8_decode($txt),0,1,'L',true);
  $this->Ln(1);
  $this->SetTextColor(0);               // regresa a negro
}
}

$pdf = new PDF('P','mm','Letter');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true,20);

$pdf->SetFont('Arial','',9);
$pdf->SetTextColor(255,0,0);

$pdf->Cell(0,0,'CN-00'.$lid,0,1,'R'); 
$pdf->Ln(19);



/* ——— sección prestatario ——— */
$pdf->SetFont('Arial','B',12);
$pdf->titulo('Datos del Prestatario');
$pdf->Ln(1);

/* Foto */
$topY = $pdf->GetY();
if($loan['foto_path'] && file_exists('../'.$loan['foto_path'])){
  $pdf->Image('../'.$loan['foto_path'],160,$topY,35,45);
}

$pdf->SetFont('Arial','',10);
$texto = utf8_decode("Nombre: {$loan['nombres']} {$loan['apellidos']}\n").
         utf8_decode("Cédula: {$loan['cedula']}   Tel: {$loan['telefono']}\n").
         utf8_decode("Dirección: ").utf8_decode(($loan['direccion']));
$pdf->MultiCell(140,6,$texto);
$pdf->Ln(2);

/* ——— Empresa / trabajo / negocio / actividad ——— */
$pdf->SetFont('Arial','B',12);
  $pdf->SetFillColor(0,102,51);        // verde
  $pdf->SetTextColor(255);             // blanco
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(142,7,utf8_decode("Información Laboral / Negocio"),0,1,'L',true);
  $pdf->Ln(1);
  $pdf->SetTextColor(0);
$pdf->SetFont('Arial','',10);
$infoLab = [];

if($loan['empresa'])          $infoLab[] = utf8_decode("Empresa: ".($loan['empresa']));
if($loan['empresa_rif'])      $infoLab[] = utf8_decode("RIF: {$loan['empresa_rif']}");
if($loan['cargo'])            $infoLab[] = utf8_decode("Cargo / Profesión: ".($loan['cargo']));
if($loan['empresa_tel'])      $infoLab[] = utf8_decode("Tel. Empresa: {$loan['empresa_tel']}");
if($loan['empresa_dir'])      $infoLab[] = utf8_decode("Dir. Empresa: ".($loan['empresa_dir']));
if($loan['actividad'])        $infoLab[] = utf8_decode("Actividad Independiente: ".($loan['actividad']));

if($infoLab){
  foreach($infoLab as $l) $pdf->MultiCell(0,5,$l);
}else{
  $pdf->Cell(0,5,'(Sin información laboral proporcionada)',0,1);
}
$pdf->Ln(2);

/* ——— Pago móvil ——— */
$pdf->SetFont('Arial','B',12);
  $pdf->SetFillColor(0,102,51);        // verde
  $pdf->SetTextColor(255);             // blanco
  $pdf->SetFont('Arial','B',12);
  $pdf->Cell(142,7,utf8_decode("Datos para el Pago Móvil (Envío Préstamo)"),0,1,'L',true);
  $pdf->Ln(1);
  $pdf->SetTextColor(0);
$pdf->SetFont('Arial','',10);
$pm = "Banco: {$loan['pago_banco']}    ".
      "C.I./RIF: {$loan['pago_cedula']}    ".
      "Tel: {$loan['pago_telefono']}";
$pdf->MultiCell(0,6,$pm);
$pdf->Ln(2);

/* ——— Resumen préstamo ——— */
$pdf->SetFont('Arial','B',12);
$pdf->titulo('Resumen del Préstamo');
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,
  "Aprobado: $".number_format($loan['principal'],0).
  utf8_decode("   Interés semanal: ". number_format($loan['interest_weekly'],1)."%")."   Monto a cancelar: $".number_format($loan['total_payable'],0).
  utf8_decode("    Nº de cuotas: {$loan['weeks']}").utf8_decode(" - Fecha: ").date('d/m/Y',strtotime($loan['start_date']))."."
);

$pdf->Ln(4);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('CONTRATO DE PRESTACIÓN DE SERVICIOS CON FINANCIAMIENTO Y FIADOR SOLIDARIO'),0,1);
$pdf->Ln(2);
$pdf->SetFont('Arial','',10);
$decl = utf8_decode("Entre la ciudadana DEIRY NATHALY VILLA GONZÁLEZ, venezolana, mayor de edad, titular de la cédula de identidad número V-17393659, domiciliada en la ciudad de Valera, estado Trujillo, quien en lo sucesivo y a los solos efectos del presente contrato se denominará EL PRESTADOR DEL SERVICIO; y el ciudadano {$loan['nombres']} {$loan['apellidos']}, venezolano, titular de la cédula de identidad número V- {$loan['cedula']}, domiciliado en {$loan['direccion']}, estado Trujillo, quien en lo sucesivo y a los solos efectos del presente contrato se denominará EL PRESTATARIO se ha convenido el presente CONTRATO DE PRESTACIÓN DE SERVICIOS CON FINANCIAMIENTO Y FIADOR SOLIDARIO, el cual se regirá por las siguientes cláusulas: PRIMERO: OBJETO DEL CONTRATO; EL PRESTADOR DEL SERVICIO con la firma del presente contrato, da en calidad de servicio de financiamiento a favor de EL PRESTATARIO la cantidad de".$monto_a_pagar. "(USD ". number_format($loan['total_payable'],0)."),")
       .utf8_decode("comprometo a pagar puntualmente cada cuota según el cronograma ")
       .utf8_decode("previsto. Acepto las condiciones de mora y penalización establecidas ")
       .utf8_decode("por CrediCashFácil.");
$pdf->MultiCell(0,6,enc($decl));
$pdf->Ln(12);
// Calculo ancho tabla: 12 + 35 + 35 = 82 mm
$tabX = (210 - 82) / 2;   // 210 es ancho interior Letter

$pdf->SetX($tabX);
$pdf->SetFillColor(0,102,51);  // cabecera verde
$pdf->SetTextColor(255);
$pdf->Cell(12,6,'#',1,0,'C',true);
$pdf->Cell(35,6,'Fecha',1,0,'C',true);
$pdf->Cell(35,6,'Monto $',1,1,'C',true);
$pdf->SetTextColor(0);

$i=1;
while($c=$cuotas->fetch_assoc()){
  $pdf->SetX($tabX);
  $pdf->Cell(12,6,$i++,1,0,'C');
  $pdf->Cell(35,6,date('d/m/Y',strtotime($c['due_date'])),1,0,'C');
  $pdf->Cell(35,6,number_format($c['amount_due'],2),1,1,'R');
}
$pdf->Ln(2);

/* ——— Compromiso de pago ——— */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('Declaración y Compromiso'),0,1);
$pdf->SetFont('Arial','',10);
$decl = utf8_decode("Yo, {$loan['nombres']} {$loan['apellidos']}titular de la cédula de identidad {$loan['cedula']}, ")
       .utf8_decode("afirmo haber recibido la suma indicada como Monto aprobado y me ")
       .utf8_decode("comprometo a pagar puntualmente cada cuota según el cronograma ")
       .utf8_decode("previsto. Acepto las condiciones de mora y penalización establecidas ")
       .utf8_decode("por CrediCashFácil.");
$pdf->MultiCell(0,6,enc($decl));
$pdf->Ln(12);

/* ——— firmas ——— */
$pdf->Cell(90,6,'____________________________',0,0,'C');
$pdf->Cell(20,6,'',0,0);
$pdf->Cell(90,6,'____________________________',0,1,'C');
$pdf->Cell(90,6,'Firma del Prestatario',0,0,'C');
$pdf->Cell(20,6,'',0,0);
$pdf->Cell(90,6,utf8_decode('Representante CrediCashFácil'),0,1,'C');

$pdf->Output('I','planilla_prestamo_'.$lid.'.pdf');
exit;

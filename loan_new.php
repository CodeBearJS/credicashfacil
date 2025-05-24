<?php require_once '../auth/guard.php';
require_once __DIR__.'/../config/db.php';

 mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
 error_reporting(E_ALL); ini_set('display_errors', 1);


$editing = false;   // se volverá true si encontramos cédula existente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  /* 1. DATOS BÁSICOS Y OPCIONALES ------------------------------------ */
  $type = (int)$_POST['borrower_type'];
  $ced  = trim($_POST['cedula']);
  $nom  = trim($_POST['nombres']);
  $tel  = trim($_POST['telefono']);
  $dir  = trim($_POST['direccion']);

  // Empresa / actividad opcional
  $empresa = $_POST['empresa']      ?? null;
  $rif     = $_POST['empresa_rif']  ?? null;
  $edir    = $_POST['empresa_dir']  ?? null;
  $etel    = $_POST['empresa_tel']  ?? null;
  $cargo   = $_POST['cargo']        ?? null;
  $actividad = $_POST['actividad']  ?? null;

  // Pago Móvil
  $pagoBanco = $_POST['pago_banco'];
  $pagoTelef = $_POST['pago_prefijo'] . $_POST['pago_telefono'];   // 0412xxxxxxx
  $pagoCedPM = $_POST['pago_doc_tipo'] . $_POST['pago_doc_nro'];   // V12345678
  
  
  // Campos nuevos
  $ingreso_semanal2 = $_POST['ingreso_semanal2']      ?? null;
  $autoriza_seguimiento    = $_POST['autoriza_seguimiento'];
  $ingreso_semanal    = $_POST['ingreso_semanal'];  
  

  /* 2. SUBIR IMÁGENES ------------------------------------------------- */
  function saveImg($field){
      if (!isset($_FILES[$field]) || $_FILES[$field]['error']) return null;
      $dest = '../uploads/' . time() . '_' . basename($_FILES[$field]['name']);
      move_uploaded_file($_FILES[$field]['tmp_name'], $dest);
      return 'uploads/' . basename($dest);
  }
  $foto = saveImg('foto');
  $doc  = saveImg('ced_doc');

  /* 3. UPDATE o INSERT ------------------------------------------------ */
  $ex = $mysqli->query("SELECT id FROM borrowers WHERE cedula='$ced'")->fetch_row();
  if ($ex) {
      // -------- UPDATE ----------
      $bid = $ex[0];
      $sql = "UPDATE borrowers SET
                borrower_type=?, nombres=?, telefono=?, direccion=?,
                empresa=?, empresa_rif=?, empresa_dir=?, empresa_tel=?,
                cargo=?, actividad=?,
                pago_banco=?, pago_telefono=?, pago_cedula=?,
                foto_path=IFNULL(?, foto_path),
                cedula_path=IFNULL(?, cedula_path)
                ingreso_semanal=?,autoriza_seguimiento=?,ingreso_semanal2=?,
              WHERE id=?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param(
        'issssssssssssssdsdi',
        $type, $nom, $tel, $dir,
        $empresa, $rif, $edir, $etel,
        $cargo, $actividad,
        $pagoBanco, $pagoTelef, $pagoCedPM,
        $foto, $doc,$ingreso_semanal,$autoriza_seguimiento,$ingreso_semanal2,
        $bid
      );
      $stmt->execute();

  } else {
      // -------- INSERT ----------
      $sql = "INSERT INTO borrowers
              (borrower_type, cedula, nombres, telefono, direccion,
               empresa, empresa_rif, empresa_dir, empresa_tel, cargo, actividad,
               pago_banco, pago_telefono, pago_cedula,
               foto_path, cedula_path,ingreso_semanal, autoriza_seguimiento, ingreso_semanal2)
              VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param(
        'isssssssssssssssdsd',
        $type, $ced, $nom, $tel, $dir,
        $empresa, $rif, $edir, $etel, $cargo, $actividad,
        $pagoBanco, $pagoTelef, $pagoCedPM,
        $foto, $doc,$ingreso_semanal,$autoriza_seguimiento,$ingreso_semanal2
      );
      $stmt->execute();
      $bid = $stmt->insert_id;
  }

  /* 4. REDIRIGIR AL PASO “APROBAR” ----------------------------------- */
  header("Location: loan_approve.php?bid=$bid");
  exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nuevo Préstamo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="assets/style.css">
<style>
/* Mini-tweak responsive */
.form-card{background:#fff;padding:1.5rem;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.grid-2{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem}
label{display:flex;flex-direction:column;font-size:.85rem;font-weight:600;color:#444}
select,input,textarea{padding:.55rem .65rem;border:1px solid #ccc;border-radius:6px;font-size:.9rem}
.preview{max-width:110px;border:1px solid #aaa;border-radius:6px;margin-top:.5rem}
@media(max-width:600px){.grid-2{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
<h1>Registro de Préstamo</h1>

<form class="form-card" method="post" enctype="multipart/form-data" id="loanForm">
  <!-- Datos básicos -->
  <label>Tipo de prestatario
    <select name="borrower_type" id="borrower_type" required>
      <option value="1">Empleado</option>
      <option value="2">Independiente</option>
      <option value="3">Dueño de Negocio</option>
    </select>
  </label>

  <div class="grid-2">
   <label>Cédula
     <input name="cedula" id="cedula" placeholder="" required>
   </label>
   <label>Nombre completo
     <input name="nombres" id="nombres" required>
   </label>
  </div>

  <div class="grid-2">
   <label>Teléfono
     <input name="telefono" id="telefono" required>
   </label>
   <label>Dirección
     <input name="direccion" id="direccion" required>
   </label>
  </div>
<!-- SE MUESTRA SOLO PARA “Empleado” (1) y “Dueño de Negocio” (3) -->
<div id="boxEmpresa" style="display:none;margin-top:.6rem">
  <h4>Datos del trabajo / empresa <small></small></h4>
  <div class="grid-2">
    <label>Nombre empresa / negocio
      <input name="empresa" id="empresa">
    </label>
    <label>RIF
      <input name="empresa_rif" id="empresa_rif">
    </label>
  </div>
  <div class="grid-2">
    <label>Teléfono empresa
      <input name="empresa_tel" id="empresa_tel">
    </label>
    <label>Dirección empresa
      <input name="empresa_dir" id="empresa_dir">
    </label>
  </div>
  <label>Cargo / Profesión
    <input name="cargo" id="cargo">
  </label>
</div>

<!-- SE MUESTRA SOLO PARA “Independiente” (2) -->
<div id="boxIndep" style="display:none;margin-top:.6rem">
  <h4>Actividad / Profesión <small></small></h4>
  <textarea name="actividad" id="actividad" rows="2"
            placeholder="Describa su actividad o profesión"></textarea>
</div>
<h4>Indique el monto de sus ingresos semanales<small></small></h4>

<div class="grid-2">
    <label>Ingreso principal (en dólares)
        <input type="number" name="ingreso_semanal" min="0" step="0.01" placeholder="" required>
    </label>
    <label>Ingreso secundario (opcional)
        <input type="number" name="ingreso_semanal2" min="0" step="0.01" placeholder="" required>
    </label>

</div>

  <!-- ——— NUEVA SECCIÓN PAGO MÓVIL ——— -->
  <h4>Datos de Pago Móvil</h4>
  <div class="grid-2">
    <label>Banco
      <select name="pago_banco" required>
        <option value="">Seleccione…</option>
        <?php
        $bancos = ['Banco de Venezuela (0102)','Banco Mercantil (0105)','Banco Provincial (0108)','Banco del Caribe (0114)','Banco Exterior (0115)',
                   'Banco Bicentenario (0175)','Banesco (0134)','Banco Plaza (0138)','DelSur Banco Universal (0157)','Banco Activo (0171)',
                   'Banco Fondo Común (0151)','Banco del Tesoro (0163)','Banco Agrícola de Venezuela (0166)','Bancrecer (0168)','R4 Banco Microfinanciero (0169)','Banplus (0174)','Banco Nacional de Crédito (0191)','Banco Caroní (0128)','Banco Sofitasa (0137)','Banco de la Gente Emprendedora (0146)','Mi Banco (0169)','Bancamiga (0172)','Banco Internacional de Desarrollo (0173)','Banco de la Fuerza Armada Nacional Bolivariana (Banfanb) (0177)'];
        foreach($bancos as $bk) echo "<option>$bk</option>";
        ?>
      </select>
    </label>

    <label>Teléfono (Pago Móvil)
      <div class="grid-2" style="gap:.4rem">
        <select name="pago_prefijo">
          <?php foreach(['0412','0422','0414','0424','0416','0426'] as $p)
              echo "<option>$p</option>"; ?>
        </select>
        <input name="pago_telefono" maxlength="7" pattern="\d{7}" placeholder="" required>
      </div>
    </label>
  </div>

  <label>Cédula / Rif Pago Móvil
    <div class="grid-2" style="gap:.4rem">
      <select name="pago_doc_tipo">
        <?php foreach(['V','E','J','P'] as $t) echo "<option>$t</option>"; ?>
      </select>
      <input name="pago_doc_nro" pattern="\d{5,10}" placeholder="" required>
    </div>
  </label>


<div class="grid-2" style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem;">
    <label>¿Autoriza seguimiento por teléfono o visita en caso de atraso?
        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem;margin-bottom:15px;">
            <label style="flex-direction: row; font-weight: normal;">
                <input type="radio" name="autoriza_seguimiento" value="1" required> Sí
            </label>
            <label style="flex-direction: row; font-weight: normal;">
                <input type="radio" name="autoriza_seguimiento" value="0"> No
            </label>
        </div>
    </label>
</div>
  <!-- Documentos -->
  <h4>Documentos</h4>
  <label>Foto del solicitante
    <input type="file" name="foto" accept="image/*" onchange="preview('p1',this)">
  </label>
  <img id="p1" class="preview">

  <label>Foto / escaneo de cédula
    <input type="file" name="ced_doc" accept="image/*" onchange="preview('p2',this)">
  </label>
  <img id="p2" class="preview">

  <button>Guardar / Actualizar prestatario y registrar préstamo</button>
</form>
</div>

<script>
// ── Previsualizar imágenes ───────────────────
function preview(id,input){
  if(input.files[0]){
    const url = URL.createObjectURL(input.files[0]);
    document.getElementById(id).src = url;
  }
}

function toggleSections(t){
  const v = (typeof t==='string') ? t : t.target.value;
  document.getElementById('boxEmpresa').style.display =
        (v==='1'||v==='3') ? 'block' : 'none';
  document.getElementById('boxIndep').style.display =
        (v==='2') ? 'block' : 'none';
}
document.getElementById('borrower_type').addEventListener('change',toggleSections);
// Ejecuta una vez al cargar
toggleSections(document.getElementById('borrower_type').value);

document.getElementById('cedula').addEventListener('blur', async e=>{
  const ced = e.target.value.trim();
  if(!ced) return;
  const res  = await fetch('ajax/get_borrower.php?ced='+ced);
  const data = await res.json();
  if(!data.id) return;

  alert('Datos encontrados: el formulario se cargará para actualizar.');

  // Tipo de prestatario y secciones
  document.getElementById('borrower_type').value = String(data.borrower_type);
  toggleSections(String(data.borrower_type));

  // Datos básicos
  ['nombres','telefono','direccion'].forEach(id=>{
      if(data[id]) document.getElementById(id).value = data[id];
  });

  // Empresa / negocio
  ['empresa','empresa_rif','empresa_tel','empresa_dir','cargo'].forEach(id=>{
      if(document.getElementById(id) && data[id]) document.getElementById(id).value = data[id];
  });
  if(data.actividad) document.getElementById('actividad').value = data.actividad;

  // Pago móvil
  if(data.pago_banco){
    document.querySelector('[name="pago_banco"]').value = data.pago_banco;
    document.querySelector('[name="pago_prefijo"]').value = data.pago_telefono.substr(0,4);
    document.querySelector('[name="pago_telefono"]').value = data.pago_telefono.substr(4);
    document.querySelector('[name="pago_doc_tipo"]').value = data.pago_cedula.substr(0,1);
    document.querySelector('[name="pago_doc_nro"]').value  = data.pago_cedula.substr(1);
  }

  // Fotos previas
  if(data.foto_path){
    document.getElementById('p1').src = '../'+data.foto_path;
  }
  if(data.cedula_path){
    document.getElementById('p2').src = '../'+data.cedula_path;
  }
})
</script>
<script src="assets/app.js" defer></script>
</body>
</html>

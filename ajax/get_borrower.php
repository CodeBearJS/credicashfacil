<?php
require_once '../../config/db.php';      // ajusta ruta
$ced = trim($_GET['ced'] ?? '');
if(!$ced) exit('{}');

$row = $mysqli->query("SELECT * FROM borrowers WHERE cedula='$ced' LIMIT 1")
              ->fetch_assoc();
echo json_encode($row ?: []);

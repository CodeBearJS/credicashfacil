<?php
$mysqli = new mysqli('localhost','ccfaacad_admin','8KgvsUxrCZ2WYM6','ccfaacad_credi');
if ($mysqli->connect_errno) {
    die('Error de conexión: '.$mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>
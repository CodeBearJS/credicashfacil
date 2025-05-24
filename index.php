<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if(isset($_SESSION['uid'])){
  header('Location: public/dashboard.php');
} else {
  header('Location: auth/login.php');
}
exit;
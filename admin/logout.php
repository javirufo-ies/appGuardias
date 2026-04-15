<?php
session_start();
session_destroy();
unset($_SESSION);
var_dump($_SESSION);
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
?>

<?php
require_once __DIR__.'/../admin/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}



function depurar($mensaje) {
    //echo "<script>console.log(\"" . $mensaje . "\");</script>";
    error_log($mensaje);
}
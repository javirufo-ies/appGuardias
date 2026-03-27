<?php
require_once __DIR__ . '/includes/db.php';

if(!isset($_GET['id'])) {
    header("Location: mensajes.php");
    exit;
}

$id = intval($_GET['id']);

// --- 1️⃣ Borrar imagen del servidor si existe
$stmt = $pdo->prepare("SELECT imagen FROM mensajes WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if($row && $row['imagen']) {
    $ruta = __DIR__ . "/../uploads/mensajes/" . $row['imagen'];
    if(file_exists($ruta)) unlink($ruta);
}

// --- 2️⃣ Borrar mensaje de la base de datos
$pdo->prepare("DELETE FROM mensajes WHERE id = ?")->execute([$id]);

// --- 3️⃣ Redirigir con confirmación
header("Location: mensajes.php");
exit;

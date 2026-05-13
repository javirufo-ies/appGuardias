<?php
session_start();
require_once '../includes/db.php';

$user_id = $_SESSION['usuario'];

$csrf = $_POST['csrf_token'] ?? '';
$actual = $_POST['actual'] ?? '';
$nueva = $_POST['nueva'] ?? '';
$nueva2 = $_POST['nueva2'] ?? '';

// 1. CSRF check
if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
    die("Token inválido");
}

// 2. validar coincidencia
if ($nueva !== $nueva2) {
    die("Las contraseñas no coinciden");
}

// 3. obtener password actual
$stmt = $pdo->prepare("SELECT password FROM usuarios WHERE usuario = ?");
$stmt->execute([$user_id]);
$hash = $stmt->fetchColumn();

// 4. verificar actual
if (!password_verify($actual, $hash)) {
    die("Contraseña actual incorrecta");
}

// 5. actualizar
$new_hash = password_hash($nueva, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE usuario = ?");
$stmt->execute([$new_hash, $user_id]);
header("Location: dashboard.php?seccion=misdatos&msg=pass_ok");
exit;
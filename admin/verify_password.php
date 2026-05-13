<?php
session_start();
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$actual = $_POST['actual'] ?? '';

$stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$hash = $stmt->fetchColumn();

echo json_encode([
    "ok" => password_verify($actual, $hash)
]);
<?php
session_start();
require_once '../includes/db.php';
$user_id = $_SESSION['usuario'];

// Usuario
$stmt = $pdo->prepare("SELECT usuario, nombre FROM usuarios WHERE usuario = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

if (isset($_GET['msg']) && $_GET['msg'] === 'pass_ok') {
    echo "<div style='color:green;font-weight:bold;'>✅Contraseña actualizada</div>";
}
?>
<h2>Mis datos</h2>

<p><strong>Usuario:</strong> <?= htmlspecialchars($user['usuario']) ?></p>
<p><strong>Nombre:</strong> <?= htmlspecialchars($user['nombre']) ?></p>

<hr>

<h3>Cambiar contraseña</h3>

<form method="POST" action="cambiar_password.php" id="formPassword">

    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <label>Contraseña actual</label>
    <div class="input-group">
        <input type="password" name="actual" id="actual">
        <button type="button" onclick="togglePass('actual')">👁</button>
    </div>
    <small id="actual-status"></small>

    <label>Nueva contraseña</label>
    <div class="input-group">
        <input type="password" name="nueva" id="nueva">
        <button type="button" onclick="togglePass('nueva')">👁</button>
    </div>
    <small id="strength"></small>

    <label>Repetir nueva contraseña</label>
    <div class="input-group">
        <input type="password" name="nueva2" id="nueva2">
        <button type="button" onclick="togglePass('nueva2')">👁</button>
    </div>
    <small id="match"></small>

    <button type="submit">Actualizar contraseña</button>
</form>

<script src="password.js"></script>
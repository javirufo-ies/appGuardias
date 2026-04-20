<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $clave = $_POST['clave'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($clave, $user['password'])) {
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['usuario_id'] = $user['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Guardias</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=<?= time() ?>">
</head>
<body class="login-body">

<div class="theme-toggle">
    <button type="button" onclick="toggleTheme()">🌓</button>
</div>

<div class="login-container animate-in">

    <div class="login-logo">
        <img src="../images/logo.png" alt="Logo del centro">
    </div>

    <h1>Acceso Administración</h1>

    <form method="POST" class="login-form <?= !empty($error) ? 'shake' : '' ?>">

        <div class="input-group">
            <span class="icon">👤</span>
            <input type="text" name="usuario" placeholder="Usuario" required>
        </div>

        <div class="input-group">
            <span class="icon">🔒</span>
            <input type="password" name="clave" placeholder="Contraseña" required>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

</div>

<script>
function toggleTheme() {
    document.body.classList.toggle('dark');
}
</script>

</body>
</html>

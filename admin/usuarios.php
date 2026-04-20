<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';
session_start();

// --- Verificar que el usuario es admin

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensaje = "";

// --- Añadir usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_usuario'])) {
    $nombre = $_POST['nuevo_usuario'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'] ?? 'profesor';
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, nombre, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $nombre, $password, $rol]);
    $mensaje = "Usuario añadido correctamente.";
}

// --- Editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id'])) {
    $id = $_POST['editar_id'];
    $nombre = $_POST['editar_nombre'];
    $rol = $_POST['editar_rol'];
    $sql = "UPDATE usuarios SET nombre = ?, rol = ?";
    $params = [$nombre, $rol];
    if (!empty($_POST['editar_password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($_POST['editar_password'], PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mensaje = "Usuario actualizado correctamente.";
}

// --- Borrar usuario
if (isset($_GET['borrar_id'])) {
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$_GET['borrar_id']]);
    $mensaje = "Usuario eliminado.";
}
// --- Roles de usuario
$stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
preg_match("/^enum\((.*)\)$/", $row['Type'], $matches);
$roles = str_getcsv($matches[1], ',', "'");
// --- Listar usuarios
$usuarios = $pdo->query("SELECT id, nombre, rol FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Usuarios</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<style>
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: center; vertical-align: middle; }
th { background-color: #007bff; color: white; }
input, select { padding: 4px; margin: 2px; }
button { padding: 6px 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background-color: #0056b3; }
.mensaje { color: green; font-weight: bold; margin-bottom: 10px; }
</style>
</head>
<body>
<h2>Gestión de Usuarios</h2>
<?php if($mensaje): ?><p class="mensaje"><?= $mensaje ?></p><?php endif; ?>

<h3>Añadir Usuario</h3>
<form method="post">
    <input type="text" name="nuevo_usuario" placeholder="Nombre de usuario" required>
    <input type="password" name="password" placeholder="Contraseña" required>
<select name="rol">
    <?php foreach ($roles as $rol): ?>
        <option value="<?= $rol ?>"><?= ucfirst($rol) ?></option>
    <?php endforeach; ?>
</select>
    <button type="submit">Añadir</button>
</form>

<h3>Usuarios existentes</h3>
<table>
    <thead>
        <tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Acciones</th></tr>
    </thead>
    <tbody>
        <?php foreach($usuarios as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= ucfirst($u['rol']) ?></td>
            <td>
                <form method="post" style="display:inline-block">
                    <input type="hidden" name="editar_id" value="<?= $u['id'] ?>">
                    <input type="text" name="editar_nombre" value="<?= htmlspecialchars($u['nombre']) ?>" required>
                    <input type="password" name="editar_password" placeholder="Nueva contraseña">
<select name="editar_rol">
    <?php foreach ($roles as $rol): ?>
        <option value="<?= $rol ?>" <?= $u['rol'] === $rol ? 'selected' : '' ?>>
            <?= ucfirst($rol) ?>
        </option>
    <?php endforeach; ?>
</select>
                    <button type="submit">Actualizar</button>
                </form>
                <a href="?borrar_id=<?= $u['id'] ?>" onclick="return confirm('¿Seguro que quieres borrar este usuario?')">Borrar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


</body>
</html>

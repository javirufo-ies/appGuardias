<?php
require_once 'includes/db.php';
require_once 'includes/funciones.php';
session_start();
if (!isset($_SESSION['usuario'])) header('Location: login.php');

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $asignatura = $_POST['asignatura'] ?? '';

    if (agregarProfesor($pdo, $nombre, $email, $asignatura)) {
        $mensaje = "Profesor agregado correctamente";
    } else {
        $mensaje = "Error al agregar profesor";
    }
}
?>

<h1>Alta de Profesor</h1>
<form method="POST">
    Nombre: <input type="text" name="nombre" required><br>
    Email: <input type="email" name="email"><br>
    Asignatura: <input type="text" name="asignatura"><br>
    <button type="submit">Agregar</button>
</form>
<p><?php echo $mensaje; ?></p>

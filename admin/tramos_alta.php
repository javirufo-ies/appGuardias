<?php
require_once 'includes/db.php';
require_once 'includes/funciones.php';
session_start();
if (!isset($_SESSION['usuario'])) header('Location: login.php');

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dia = $_POST['dia_semana'] ?? '';
    $inicio = $_POST['hora_inicio'] ?? '';
    $fin = $_POST['hora_fin'] ?? '';
    $desc = $_POST['descripcion'] ?? '';

    if (agregarTramo($pdo, $dia, $inicio, $fin, $desc)) {
        $mensaje = "Tramo agregado correctamente";
    } else {
        $mensaje = "Error al agregar tramo";
    }
}
?>

<h1>Alta de Tramo de Clase</h1>
<form method="POST">
    Día de la semana:
    <select name="dia_semana">
        <option>Lunes</option>
        <option>Martes</option>
        <option>Miércoles</option>
        <option>Jueves</option>
        <option>Viernes</option>
    </select><br>
    Hora inicio: <input type="time" name="hora_inicio" required><br>
    Hora fin: <input type="time" name="hora_fin" required><br>
    Descripción: <input type="text" name="descripcion"><br>
    <button type="submit">Agregar</button>
</form>
<p><?php echo $mensaje; ?></p>

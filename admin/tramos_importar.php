<?php
require_once 'includes/db.php';
require_once 'includes/funciones.php';
session_start();
if (!isset($_SESSION['usuario'])) header('Location: login.php');

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $archivo = $_FILES['csv_file']['tmp_name'];
    if (importarCSV($pdo, $archivo, 'tramo')) {
        $mensaje = "CSV importado correctamente";
    } else {
        $mensaje = "Error al importar CSV";
    }
}
?>

<h1>Importar Profesores desde CSV</h1>
<form method="POST" enctype="multipart/form-data">
    CSV: <input type="file" name="csv_file" accept=".csv" required><br>
    <button type="submit">Importar</button>
</form>
<p><?php echo $mensaje; ?></p>

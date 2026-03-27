<?php
require_once 'includes/db.php';
require_once 'includes/funciones.php';

$mensaje = '';

// Alta individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'alta') {
    $dia = $_POST['dia_semana'] ?? '';
    $inicio = $_POST['hora_inicio'] ?? '';
    $fin = $_POST['hora_fin'] ?? '';
    $desc = $_POST['descripcion'] ?? '';
    if (agregarTramo($pdo, $dia, $inicio, $fin, $desc)) {
        $mensaje = "Tramo agregado correctamente.";
    } else {
        $mensaje = "Error al agregar tramo.";
    }
}

// Importación CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'csv' && isset($_FILES['csv_file'])) {
    $archivo = $_FILES['csv_file']['tmp_name'];
    if (importarCSV($pdo, $archivo, 'tramo')) {
        $mensaje = "CSV importado correctamente.";
    } else {
        $mensaje = "Error al importar CSV.";
    }
}

// Listado de tramos
$stmt = $pdo->query("SELECT * FROM tramos ORDER BY FIELD(dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes'), hora_inicio ASC");
$tramos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Tramos de clases</h1>
<p><?php echo $mensaje; ?></p>

<h2>Alta individual</h2>
<form method="POST">
    <input type="hidden" name="tipo" value="alta">
    Día: 
    <select name="dia_semana">
        <option>Lunes</option>
        <option>Martes</option>
        <option>Miércoles</option>
        <option>Jueves</option>
        <option>Viernes</option>
    </select>
    Hora inicio: <input type="time" name="hora_inicio" required>
    Hora fin: <input type="time" name="hora_fin" required>
    Descripción: <input type="text" name="descripcion">
    <button type="submit">Agregar</button>
</form>

<h2>Importar desde CSV</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="tipo" value="csv">
    CSV: <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Importar</button>
</form>

<h2>Listado de Tramos</h2>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Día</th>
        <th>Hora inicio</th>
        <th>Hora fin</th>
        <th>Descripción</th>
    </tr>
    <?php foreach ($tramos as $t): ?>
    <tr>
        <td><?php echo htmlspecialchars($t['dia_semana']); ?></td>
        <td><?php echo htmlspecialchars($t['hora_inicio']); ?></td>
        <td><?php echo htmlspecialchars($t['hora_fin']); ?></td>
        <td><?php echo htmlspecialchars($t['descripcion']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

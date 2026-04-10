<?php
require_once 'includes/db.php';
require_once 'includes/funciones.php';

$mensaje = '';

// Alta individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'alta') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $asignatura = $_POST['asignatura'] ?? '';
    if (agregarProfesor($pdo, $nombre, $email, $asignatura)) {
        $mensaje = "Profesor agregado correctamente.";
    } else {
        $mensaje = "Error al agregar profesor.";
    }
}

// Importación CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo']) && $_POST['tipo'] === 'csv' && isset($_FILES['csv_file'])) {
    $archivo = $_FILES['csv_file']['tmp_name'];
    $resultado = importarCSV($pdo, $archivo, 'profesor'); // 'profesor' o 'tramo'
    $mensaje = $resultado['mensaje'];
}

// Listado de profesores
$stmt = $pdo->query("SELECT * FROM profesores ORDER BY nombre ASC");
$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Profesores</h1>
<?php if ($mensaje): ?>
    <p style="color:green;"><?php echo htmlspecialchars($mensaje); ?></p>
<?php endif; ?>

<h2>Alta individual</h2>
<form method="POST">
    <input type="hidden" name="tipo" value="alta">
    Nombre: <input type="text" name="nombre" required>
    Email: <input type="email" name="email">
    Asignatura: <input type="text" name="asignatura">
    <button type="submit">Agregar</button>
</form>
<!--
<h2>Importar desde CSV</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="tipo" value="csv">
    CSV: <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit">Importar</button>
</form>

-->


<?php
// --- ACTUALIZAR PROFESOR
if (isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);

    if ($nombre !== '') {
        $stmt = $pdo->prepare("UPDATE profesores SET nombre=? WHERE id=?");
        $stmt->execute([$nombre, $id]);
    }
}

// --- ELIMINAR PROFESOR
if (isset($_POST['eliminar'])) {
    $id = intval($_POST['id']);

    // Opcional: eliminar también sus horarios
    $pdo->prepare("DELETE FROM horarios WHERE profesor_id=?")->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM profesores WHERE id=?");
    $stmt->execute([$id]);
}
?>

<h2>Listado de Profesores</h2>

<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <th>Nombre</th>
        <th>Acciones</th>
    </tr>

    <?php foreach ($profesores as $p): ?>
    <tr>
        <!-- FORMULARIO INLINE PARA EDITAR -->
        <form method="post" action="">
            <td>
                <input type="text" name="nombre" value="<?= htmlspecialchars($p['nombre']) ?>">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
            </td>
            <td>
                <button type="submit" name="editar">Guardar</button>
                <button type="submit" name="eliminar" onclick="return confirm('¿Seguro que quieres eliminar este profesor?')">Eliminar</button>
                
                <!-- VER HORARIO -->
                <!-- <a href="horario_profesor.php?profesor_id=<?= $p['id'] ?>">Ver horario</a> -->
                <a href="horario_profesor_adv.php?profesor_id=<?= $p['id'] ?>">Ver horario avanzado</a>
            </td>
        </form>
    </tr>
    <?php endforeach; ?>
</table>
<a href="dashboard.php">⬅ Volver</a>
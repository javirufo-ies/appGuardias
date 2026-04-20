<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// --- 1️⃣ Obtener profesores
$profesores = $pdo->query("SELECT id, nombre FROM profesores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$profesor_id = $_GET['profesor_id'] ?? null;
$mensaje = "";

// --- 2️⃣ Guardar cambios de guardias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profesor_id'])) {
    $profesor_id = $_POST['profesor_id'];
    $seleccionados = $_POST['tramos'] ?? [];

    // Borrar las guardias previas
    $pdo->prepare("DELETE FROM guardias WHERE profesor_id = ?")->execute([$profesor_id]);

    // Insertar las nuevas
    $stmt = $pdo->prepare("INSERT INTO guardias (profesor_id, tramo_id) VALUES (?, ?)");
    foreach ($seleccionados as $t) {
        $stmt->execute([$profesor_id, $t]);
    }

    $mensaje = "✅ Guardias actualizadas correctamente.";
}

// --- 3️⃣ Obtener todos los tramos
$tramos = $pdo->query("
    SELECT id, descripcion, dia_semana, hora_inicio, hora_fin
    FROM tramos
    ORDER BY FIELD(dia_semana, 'lunes','martes','miércoles','jueves','viernes'), hora_inicio
")->fetchAll(PDO::FETCH_ASSOC);

// --- 4️⃣ Guardias actuales del profesor
$guardias_actuales = [];
if ($profesor_id) {
    $stmt = $pdo->prepare("SELECT tramo_id FROM guardias WHERE profesor_id = ?");
    $stmt->execute([$profesor_id]);
    $guardias_actuales = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'tramo_id');
}

// --- 5️⃣ Construir cuadrante
$dias = ['lunes','martes','miércoles','jueves','viernes'];
$cuadrante = ['diurno' => [], 'vespertino' => []];

foreach ($tramos as $t) {
    $turno = stripos($t['descripcion'], 'vespertino') !== false ? 'vespertino' : 'diurno';
    $hora = "{$t['hora_inicio']} - {$t['hora_fin']}";
    $dia_normalizado = strtolower(trim($t['dia_semana']));

    $cuadrante[$turno][$hora][$dia_normalizado] = $t;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Guardias</title>
    <link rel="stylesheet" href="../assets/css/estilos.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h2 { margin-bottom: 10px; }
        .mensaje { color: green; font-weight: bold; }

        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        th { background-color: #007bff; color: white; }
        td:first-child { background: #f2f2f2; font-weight: bold; }
        .asignado { background-color: #cdeeff; }
        input[type=checkbox] { transform: scale(1.2); cursor: pointer; }
        button {
            padding: 8px 16px;
            background-color: #007bff; border: none;
            color: white; border-radius: 6px; cursor: pointer;
        }
        button:hover { background-color: #0056b3; }
        .turno { background: #004b9b; color: white; text-align: center; font-size: 1.1em; padding: 4px; }
    </style>
</head>
<body>

<h2>Asignar / Modificar Guardias</h2>

<?php if($mensaje): ?><p class="mensaje"><?= $mensaje ?></p><?php endif; ?>

<form method="get" action="guardias.php">
    <label>Selecciona Profesor:</label>
    <select name="profesor_id" onchange="this.form.submit()">
        <option value="">-- Selecciona --</option>
        <?php foreach($profesores as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $profesor_id == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<?php if($profesor_id): ?>
<form method="post" action="guardias.php">
    <input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">

    <?php foreach ($cuadrante as $turno => $horas): ?>
        <div class="turno"><?= strtoupper($turno) ?></div>
        <table>
            <thead>
                <tr>
                    <th>Tramo</th>
                    <?php foreach ($dias as $dia): ?>
                        <th><?= ucfirst($dia) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($horas as $hora => $columnas): ?>
                    <tr>
                        <td><?= $hora ?></td>
                        <?php foreach ($dias as $dia): ?>
                            <?php
                            $t = $columnas[$dia] ?? null;
                            if ($t):
                                $checked = in_array($t['id'], $guardias_actuales);
                            ?>
                                <td class="<?= $checked ? 'asignado' : '' ?>">
                                    <input type="checkbox" name="tramos[]" value="<?= $t['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                </td>
                            <?php else: ?>
                                <td>-</td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <button type="submit">Guardar Cambios</button>
</form>
<?php endif; ?>
<a href="dashboard.php" class="btn-dashboard">⬅ Volver al Dashboard</a>
</body>
</html>

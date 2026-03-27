<?php
require_once __DIR__ . '/includes/db.php';

// --- 1️⃣ Profesores
$profesores = $pdo->query("SELECT id, nombre FROM profesores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$profesor_id = $_GET['profesor_id'] ?? null;

// Fecha seleccionada por el usuario (cualquier día)
// Si no hay, por defecto hoy
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$mensaje = "";

// --- 2️⃣ Guardar ausencias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profesor_id'])) {
    $profesor_id = $_POST['profesor_id'];
    $ausencias = $_POST['ausencias'] ?? [];
    $observaciones = $_POST['observaciones'] ?? [];
    $aulas = $_POST['aula'] ?? [];
    $fechas = $_POST['fecha'] ?? [];

    // Para cada ausencia marcada: eliminar cualquier registro existente con la misma fecha/tramo/profesor y volver a insertar
    $stmt_insert = $pdo->prepare("INSERT INTO ausencias (profesor_id, tramo_id, dia_semana, fecha, observaciones, aula) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_delete = $pdo->prepare("DELETE FROM ausencias WHERE profesor_id = ? AND tramo_id = ? AND fecha = ?");

    foreach ($ausencias as $key => $tramo_id) {
        // key = "<id_tramo>_<dia>" (por ejemplo "12_lunes")
        list($id_tramo, $dia) = explode('_', $key);
        $obs = $observaciones[$key] ?? '';
        $aula = $aulas[$key] ?? '';
        $fecha_ausencia = $fechas[$key] ?? date('Y-m-d');

        // normalizar
        $dia_norm = strtolower($dia);

        // eliminar posible duplicado y guardar
        $stmt_delete->execute([$profesor_id, $id_tramo, $fecha_ausencia]);
        $stmt_insert->execute([$profesor_id, $id_tramo, $dia_norm, $fecha_ausencia, $obs, $aula]);
    }

    $mensaje = "✅ Ausencias actualizadas correctamente.";
    // actualizar la fecha seleccionada a la que envió el formulario (por si venimos de POST)
    $fecha_seleccionada = $_POST['fecha_base'] ?? $fecha_seleccionada;
}

// --- 3️⃣ Tramos
$tramos = $pdo->query("
    SELECT id, descripcion, dia_semana, hora_inicio, hora_fin 
    FROM tramos 
    ORDER BY FIELD(dia_semana,'lunes','martes','miércoles','jueves','viernes'), hora_inicio
")->fetchAll(PDO::FETCH_ASSOC);

// --- 4️⃣ Construir cuadrante por turno y hora
$dias = ['lunes','martes','miércoles','jueves','viernes'];
$cuadrante = ['diurno'=>[], 'vespertino'=>[]];
foreach ($tramos as $t) {
    $hora = "{$t['hora_inicio']} - {$t['hora_fin']}";
    $turno = stripos($t['descripcion'], 'vespertino') !== false ? 'vespertino' : 'diurno';
    $dia_normalizado = strtolower(trim($t['dia_semana']));
    $cuadrante[$turno][$hora][$dia_normalizado] = $t;
}

// --- 5️⃣ Calcular lunes de la semana que contiene la fecha seleccionada
try {
    $dt = new DateTime($fecha_seleccionada);
} catch (Exception $e) {
    $dt = new DateTime(); // fallback hoy
    $fecha_seleccionada = $dt->format('Y-m-d');
}

// ISO day of week: 1 (Monday) - 7 (Sunday)
$iso = (int)$dt->format('N');
$days_to_subtract = $iso - 1; // si es Monday -> 0, Tuesday -> 1, ...
$monday = clone $dt;
if ($days_to_subtract > 0) {
    $monday->modify("-{$days_to_subtract} days");
}
$fecha_lunes = $monday->format('Y-m-d');

// Fechas para lunes..viernes
$fechas_dia = [];
foreach ($dias as $i => $dia) {
    $d = clone $monday;
    if ($i > 0) $d->modify("+{$i} days");
    $fechas_dia[$dia] = $d->format('Y-m-d');
}

// --- 6️⃣ Obtener ausencias actuales del profesor en la semana (lunes..viernes)
$ausencias_actuales = [];
if ($profesor_id) {
    $stmt = $pdo->prepare("SELECT * FROM ausencias WHERE profesor_id=? AND fecha BETWEEN ? AND ?");
    $stmt->execute([$profesor_id, $fechas_dia['lunes'], $fechas_dia['viernes']]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        // key unique por tramo y día de semana (no por fecha) para mostrar en la cuadricula
        $key = $row['tramo_id'] . '_' . strtolower($row['dia_semana']);
        // guardamos la fecha concreta del registro
        $ausencias_actuales[$key] = [
            'observaciones' => $row['observaciones'],
            'aula' => $row['aula'],
            'fecha' => $row['fecha']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asignar Ausencias (por semana)</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<style>
/* estilos mínimos específicos (puedes moverlos a estilos.css si prefieres) */
.scroll-container { max-height: 90vh; overflow: auto; padding: 10px; }
table { width: 100%; border-collapse: collapse; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; vertical-align:top; }
th { background:#007bff; color:#fff; }
input.fecha { width: 100%; box-sizing: border-box; }
textarea { width: 100%; box-sizing: border-box; height: 3.2vh; }
input.aula { width: 100%; box-sizing: border-box; }
</style>
</head>
<body>

<h2>Asignar / Modificar Ausencias (Semana)</h2>
<?php if($mensaje): ?><p style="color:green; font-weight:bold;"><?= $mensaje ?></p><?php endif; ?>

<div class="scroll-container">
    <form method="get" action="ausencias.php">
        <label>Profesor:</label>
        <select name="profesor_id" onchange="this.form.submit()">
            <option value="">-- Selecciona --</option>
            <?php foreach($profesores as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $profesor_id == $p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        &nbsp;&nbsp;

        <label>Fecha cualquiera de la semana (ej: miércoles 5):</label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha_seleccionada) ?>" onchange="this.form.submit()">
        <small>Se calculará la semana (lunes..viernes) que contiene la fecha seleccionada.</small>
    </form>

    <?php if($profesor_id): ?>
    <form method="post" action="ausencias.php">
        <input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">
        <!-- Enviamos tambíen la fecha base por comodidad -->
        <input type="hidden" name="fecha_base" value="<?= htmlspecialchars($fecha_seleccionada) ?>">

        <?php foreach($cuadrante as $turno => $horas): ?>
            <h3 style="background:#004b9b;color:#fff;padding:6px;margin-top:16px;"><?= strtoupper($turno) ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Tramo</th>
                        <?php foreach($dias as $dia): ?>
                            <th><?= ucfirst($dia) ?><br><small><?= $fechas_dia[$dia] ?></small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($horas as $hora => $columnas): ?>
                    <tr>
                        <td style="font-weight:bold;"><?= $hora ?></td>
                        <?php foreach($dias as $dia):
                            $t = $columnas[$dia] ?? null;
                            $key = $t ? $t['id'] . '_' . strtolower($dia) : null;
                            $checked = $key && isset($ausencias_actuales[$key]);
                            $obs = $checked ? $ausencias_actuales[$key]['observaciones'] : '';
                            $aula = $checked ? $ausencias_actuales[$key]['aula'] : '';
                            // fecha por columna (lunes..viernes)
                            $fecha_col = $fechas_dia[$dia];
                        ?>
                        <?php if($t): ?>
                            <td>
                                <label style="display:block; text-align:left;">
                                    <input type="checkbox" name="ausencias[<?= $key ?>]" value="<?= $t['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                    <strong> <?= htmlspecialchars($t['descripcion']) ?></strong>
                                </label>

                                <!-- fecha concreta para esta celda (se autocompleta según fecha seleccionada) -->
                                <input type="date" class="fecha" name="fecha[<?= $key ?>]" value="<?= htmlspecialchars($checked ? $ausencias_actuales[$key]['fecha'] : $fecha_col) ?>">

                                <textarea name="observaciones[<?= $key ?>]" placeholder="Observaciones"><?= htmlspecialchars($obs) ?></textarea>
                                <input type="text" class="aula" name="aula[<?= $key ?>]" placeholder="Aula" value="<?= htmlspecialchars($aula) ?>">
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

        <p><button type="submit">Guardar Ausencias</button></p>
    </form>
    <?php endif; ?>
</div>
<a href="dashboard.php" class="btn-dashboard">⬅ Volver al Dashboard</a>
</body>
</html>

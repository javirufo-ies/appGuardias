<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');

/**
 * =========================
 * DÍAS LECTIVOS
 * =========================
 */
$dias_lectivos = 0;

$inicio = new DateTime("$anio-$mes-01");
$fin = clone $inicio;
$fin->modify('last day of this month');

$tmp = clone $inicio;

while ($tmp <= $fin) {
    if ($tmp->format('N') <= 5) {
        $dias_lectivos++;
    }
    $tmp->modify('+1 day');
}

/**
 * =========================
 * TIPOS DE AUSENCIA
 * =========================
 */
$stmtTipos = $pdo->query("
    SELECT id, codigo_mostrar
    FROM tipos_ausencia
");
$tipos_ausencia = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

$tipos = [];
foreach ($tipos_ausencia as $t) {
    $tipos[(int)$t['id']] = $t;
}

/**
 * =========================
 * CONSULTA: POR PROFESOR + DÍA
 * =========================
 */
$stmt = $pdo->prepare("
    SELECT 
        p.nombre,
        a.fecha,
        MIN(a.tipo) as tipo_ausencia_id
    FROM ausencias a
    JOIN profesores p ON p.id = a.profesor_id
    WHERE MONTH(a.fecha)=? AND YEAR(a.fecha)=?
    GROUP BY p.id, a.fecha
    ORDER BY p.nombre, a.fecha
");

$stmt->execute([$mes, $anio]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * =========================
 * AGRUPAR POR PROFESOR
 * =========================
 */
$data = [];

foreach ($rows as $r) {

    $nombre = $r['nombre'];

    if (!isset($data[$nombre])) {
        $data[$nombre] = [];
    }

    $data[$nombre][] = $r;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>

table { width:100%; border-collapse: collapse; margin-top:20px;}
th,td { border:1px solid #ccc; padding:8px; vertical-align: top; }
th { background:#333; color:white; }
form { margin-bottom:20px; }
</style>
</head>
<body>

<h2>📊 Informe de ausencias</h2>

<form method="get" action="dashboard.php">
    <input type="hidden" name="seccion" value="informefaltas">

    <label>Mes:</label>
    <select name="mes">
        <?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $m==$mes?'selected':'' ?>>
                <?= $m ?>
            </option>
        <?php endfor; ?>
    </select>

    <label>Año:</label>
    <input type="number" name="anio" value="<?= $anio ?>" style="width:80px;">

    <button type="submit">Actualizar</button>
</form>

<p>Días lectivos: <b><?= $dias_lectivos ?></b></p>

<div style="margin-bottom:15px;">
    <button onclick="window.print()" style="padding:6px 12px; cursor:pointer;">
        🖨️ Imprimir listado
    </button>
</div>

<table>
<tr>
    <th>Profesor</th>
    <th>Fechas</th>
</tr>

<?php if (empty($data)): ?>

<tr>
    <td colspan="2" style="text-align:center; padding:20px;">
        ⚠️ No hay ausencias registradas en este mes
    </td>
</tr>

<?php else: ?>

<?php foreach ($data as $nombre => $items): ?>

<tr>

<td><?= htmlspecialchars($nombre) ?></td>

<td>

<?php
$lineas = [];

foreach ($items as $i) {

    $tipo = $tipos[(int)($i['tipo_ausencia_id'] ?? 0)] ?? null;

    $codigo = !empty($tipo['codigo_mostrar'])
        ? '(' . $tipo['codigo_mostrar'] . ')'
        : '';

    $fecha = date('d/m', strtotime($i['fecha']));

    $lineas[] = "{$fecha} {$codigo}";
}

echo implode('<br>', $lineas);
?>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</table>

</body>
</html>
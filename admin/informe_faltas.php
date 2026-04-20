<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';
$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');

// --- días lectivos
$dias_lectivos = 0;
$inicio = new DateTime("$anio-$mes-01");
$fin = clone $inicio;
$fin->modify('last day of this month');

$tmp = clone $inicio;
while ($tmp <= $fin) {
    if ($tmp->format('N') <= 5) $dias_lectivos++;
    $tmp->modify('+1 day');
}

// --- consulta
$stmt = $pdo->prepare("
    SELECT 
        p.nombre,
        COUNT(DISTINCT a.fecha) as total_dias,
        GROUP_CONCAT(DISTINCT DATE_FORMAT(a.fecha,'%d/%m') ORDER BY a.fecha) as dias
    FROM ausencias a
    JOIN profesores p ON p.id = a.profesor_id
    WHERE MONTH(a.fecha)=? AND YEAR(a.fecha)=?
    GROUP BY p.id
    ORDER BY total_dias DESC
");
$stmt->execute([$mes,$anio]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial; margin:20px; }
table { width:100%; border-collapse: collapse; margin-top:20px;}
th,td { border:1px solid #ccc; padding:8px; }
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
    <button onclick="window.print()" style="padding:6px 12px; font-size:14px; cursor:pointer;">
        🖨️ Imprimir listado
    </button>
</div>
<table>
<tr>
<th>Profesor</th>
<th>Total días</th>
<th>%</th>
<th>Fechas</th>
</tr>

<?php 

if (empty($data)): ?>
<tr>
    <td colspan="4" style="text-align:center; padding:20px;">
        ⚠️ No hay ausencias registradas en este mes
    </td>
</tr>
<?php else: ?>

<?php foreach($data as $d): 
    $porcentaje = $dias_lectivos ? round(($d['total_dias']/$dias_lectivos)*100,1) : 0;
?>
<tr>
<td><?= htmlspecialchars($d['nombre']) ?></td>
<td><?= $d['total_dias'] ?></td>
<td><?= $porcentaje ?>%</td>
<td><?= $d['dias'] ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>

</body>
</html>
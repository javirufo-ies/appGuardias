<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');

/**
 * =========================
 * NAVEGACIÓN
 * =========================
 */
$fecha = new DateTime("$anio-$mes-01");

$prev = clone $fecha;
$prev->modify('-1 month');

$next = clone $fecha;
$next->modify('+1 month');

/**
 * =========================
 * TIPOS DE AUSENCIA
 * =========================
 */
$stmtTipos = $pdo->query("
    SELECT id, codigo_mostrar
    FROM tipos_ausencia
");

$tipos_ausencia = [];
foreach ($stmtTipos->fetchAll(PDO::FETCH_ASSOC) as $t) {
    $tipos_ausencia[(int)$t['id']] = $t;
}

/**
 * =========================
 * AUSENCIAS (POR DÍA + PROFESOR)
 * =========================
 */
$stmt = $pdo->prepare("
    SELECT 
        DATE(a.fecha) as fecha,
        p.nombre,
        MIN(a.tipo) as tipo_ausencia_id
    FROM ausencias a
    JOIN profesores p ON p.id = a.profesor_id
    WHERE MONTH(a.fecha)=? AND YEAR(a.fecha)=?
    GROUP BY a.fecha, p.id
    ORDER BY a.fecha, p.nombre
");

$stmt->execute([$mes, $anio]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * =========================
 * MAPA CALENDARIO
 * =========================
 */
$ausencias = [];

foreach ($rows as $r) {

    $dia = (int)date('j', strtotime($r['fecha']));

    $tipo = $tipos_ausencia[(int)($r['tipo_ausencia_id'] ?? 0)] ?? null;

    $codigo = !empty($tipo['codigo_mostrar'])
        ? '(' . $tipo['codigo_mostrar'] . ')'
        : '';

    $ausencias[$dia][] = $r['nombre'] . ' ' . $codigo;
}

/**
 * =========================
 * CALENDARIO
 * =========================
 */
$primer_dia = new DateTime("$anio-$mes-01");
$inicio_semana = (int)$primer_dia->format('N');
$dias_mes = (int)$primer_dia->format('t');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
body { font-family: Arial; margin:20px; }
h2 { text-align:center; }

.nav {
    display:flex;
    justify-content: space-between;
    margin-bottom:10px;
}

.calendario {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.dia {
    border:1px solid #ccc;
    min-height:120px;
    padding:5px;
    font-size:12px;
}

.numero { font-weight:bold; }

.ausente { background:#f8d7da; }
</style>

</head>
<body>

<div class="nav">
    <a href="dashboard.php?seccion=calendarioausencias&mes=<?= $prev->format('n') ?>&anio=<?= $prev->format('Y') ?>">
        ⬅ Mes anterior
    </a>

    <h2><?= $mes ?>/<?= $anio ?></h2>

    <a href="dashboard.php?seccion=calendarioausencias&mes=<?= $next->format('n') ?>&anio=<?= $next->format('Y') ?>">
        Mes siguiente ➡
    </a>
</div>

<div class="calendario">

<?php
/**
 * Espacios vacíos al inicio del mes
 */
for ($i = 1; $i < $inicio_semana; $i++) {
    echo "<div></div>";
}

/**
 * Días del mes
 */
for ($d = 1; $d <= $dias_mes; $d++) {

    $clase = isset($ausencias[$d]) ? 'dia ausente' : 'dia';

    echo "<div class='$clase'>";
    echo "<div class='numero'>$d</div>";

    if (isset($ausencias[$d])) {
        foreach ($ausencias[$d] as $p) {
            echo "<div>$p</div>";
        }
    }

    echo "</div>";
}
?>

</div>

</body>
</html>
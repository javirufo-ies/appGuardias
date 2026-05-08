<?php
require_once __DIR__ . '/includes/db.php';

/**
 * =========================
 * DÍAS Y CONTROL DE JORNADA
 * =========================
 */
$dias = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
$dia_actual = strtolower($dias[date('N') - 1]);

if (!in_array($dia_actual, ['lunes','martes','miércoles','jueves','viernes'])) {
    echo "<h2 style='text-align:center; padding:20px;'>No hay guardias programadas para hoy.</h2>";
    return;
}

$hora_actual = date('H:i:s');
$hora_corte = '14:25';
$turno_texto = ($hora_actual < $hora_corte) ? 'diurno' : 'vespertino';



$fecha_hoy = date('Y-m-d');

/**
 * =========================
 * TIPOS DE AUSENCIA (BD → UI)
 * =========================
 */
$stmt = $pdo->query("SELECT * FROM tipos_ausencia");
$tipos_ausencia = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tipos = [];
foreach ($tipos_ausencia as $t) {
    $tipos[$t['id']] = $t;
}

/**
 * =========================
 * TRAMOS
 * =========================
 */
$stmt = $pdo->prepare("
    SELECT * FROM tramos 
    WHERE LOWER(dia_semana) = ? AND LOWER(descripcion) LIKE ?
    ORDER BY hora_inicio
");
$stmt->execute([$dia_actual, "%$turno_texto%"]);
$tramos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="h-full min-h-0 flex flex-col overflow-hidden">
<div class="flex-1 min-h-0 overflow-auto">

<table id="tabla-cuadrante" class="tabla-guardias w-full text-[clamp(10px,1.1vw,160px)]">

<thead>
<tr class="bg-stone-100">
    <th class="border border-gray-400 px-2 py-[clamp(2px,0.4vw,8px)]">Tramo</th>
    <th class="border border-gray-400 px-2 py-[clamp(2px,0.4vw,8px)]">Profesores Guardia</th>
    <th class="border border-gray-400 px-2 py-[clamp(2px,0.4vw,8px)]">Profesor ausente - Grupo / Aula - Observaciones</th>
</tr>
</thead>

<tbody>

<?php foreach ($tramos as $tramo): ?>

<?php
echo "<!-- Tramo: {$tramo['descripcion']} -->";

$es_ahora = ($hora_actual >= $tramo['hora_inicio'] && $hora_actual <= $tramo['hora_fin'])
    ? "class='bg-lime-300 outline outline-2 outline-amber-400 -outline-offset-2 font-bold'"
    : "";

/**
 * =========================
 * GUARDIAS
 * =========================
 */
$stmt = $pdo->prepare("
    SELECT p.id, p.nombre
    FROM profesores p 
    JOIN horarios h ON h.profesor_id = p.id
    WHERE h.tramo_horario = ?
      AND h.dia_semana = ?
      AND h.tipo = 'Guardias'
");
$stmt->execute([$tramo['id'], date('N')]);
$profesores_guardia = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * =========================
 * AUSENCIAS
 * =========================
 */
$stmt = $pdo->prepare("
    SELECT a.*, p.nombre, h.grupo
    FROM ausencias a
    JOIN profesores p ON a.profesor_id = p.id
    LEFT JOIN horarios h 
        ON h.profesor_id = a.profesor_id 
        AND h.tramo_horario = a.tramo_id 
        AND h.tipo='Docencia'
    WHERE a.tramo_id = ? AND a.fecha = ?
");
$stmt->execute([$tramo['id'], $fecha_hoy]);
$ausentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * INDEXAR AUSENCIAS
 */
$ausentes_por_id = [];
foreach ($ausentes as $a) {
    $ausentes_por_id[$a['profesor_id']] = $a;
}

/**
 * =========================
 * GUARDIAS CON FORMATO DINÁMICO
 * =========================
 */
$lista_profesores = [];

foreach ($profesores_guardia as $p) {

    if (isset($ausentes_por_id[$p['id']])) {

        $ausencia = $ausentes_por_id[$p['id']];
        $tipo = $tipos[$ausencia['tipo']] ?? null;

        $nombre = htmlspecialchars($p['nombre']);

        if ($tipo) {
            $clase = $tipo['clase_css'] ?? '';
            $codigo = $tipo['codigo_mostrar'] ?? '';
        } else {
            $clase = 'text-red-600 font-bold';
            $codigo = 'F';
        }
        if ($codigo == '') {
            $codigo = 'F';
            $clase = 'text-red-600 font-bold';
        }

        $lista_profesores[] = "<span class='{$clase}'>{$nombre} ({$codigo})</span>";

    } else {
        $lista_profesores[] = htmlspecialchars($p['nombre']);
    }
}

$lista_profesores_html = !empty($lista_profesores)
    ? implode('<br>', $lista_profesores)
    : '-';

/**
 * =========================
 * AUSENCIAS AGRUPADAS
 * =========================
 */
$ids_guardia = array_flip(array_column($profesores_guardia, 'id'));

$ausentes_agrupados = [];

foreach ($ausentes as $a) {

    if (isset($ids_guardia[$a['profesor_id']])) {
        continue;
    }

    $id = $a['profesor_id'];

    if (!isset($ausentes_agrupados[$id])) {
        $ausentes_agrupados[$id] = [
            'nombre' => $a['nombre'],
            'grupos' => [],
            'aula' => $a['aula'] ?? '',
            'observaciones' => [],
            'tipo' => $a['tipo'] ?? null
        ];
    }

    if (!empty($a['grupo']) && !in_array($a['grupo'], $ausentes_agrupados[$id]['grupos'])) {
        $ausentes_agrupados[$id]['grupos'][] = $a['grupo'];
    }

    if (!empty($a['observaciones']) && !in_array($a['observaciones'], $ausentes_agrupados[$id]['observaciones'])) {
        $ausentes_agrupados[$id]['observaciones'][] = $a['observaciones'];
    }
}

/**
 * =========================
 * RENDER AUSENCIAS
 * =========================
 */
$lista_ausentes = [];

foreach ($ausentes_agrupados as $a) {

    $tipo = $tipos[$a['tipo']] ?? null;

    $clase = $tipo['clase_css'] ?? 'text-black';
    $codigo = $tipo['codigo_mostrar'] ?? '';
    $codigo = !empty($tipo['codigo_mostrar'])
    ? '(' . $tipo['codigo_mostrar'] . ')'
    : '';

    $grupos = implode(', ', $a['grupos']);
    $aula = $a['aula'];
    $observaciones = $a['observaciones'];

    $texto = "<span class='{$clase}'>";
    $texto .= "<b>{$a['nombre']}</b> {$codigo}";

    if ($grupos) $texto .= " {$grupos}";
    if ($aula) $texto .= " / {$aula}";
    if ($observaciones) {
        $texto .= " - <span class='observaciones'>" . implode('; ', $observaciones) . "</span>";
    }

    $texto .= "</span>";

    $lista_ausentes[] = $texto;
}

/**
 * =========================
 * OUTPUT FINAL
 * =========================
 */
echo "<tr {$es_ahora}>";

echo "<td class='border border-gray-400 px-2 py-[clamp(2px,0.4vw,8px)]'>
{$tramo['hora_inicio']} - {$tramo['hora_fin']}
</td>";

echo "<td class='border border-gray-400 px-2 py-[clamp(2px,0.4vw,8px)]'>
{$lista_profesores_html}
</td>";

echo "<td class='border border-gray-400 px-2 py-[clamp(2px,0.4vw,8px)]'>"
. (empty($lista_ausentes) ? '&nbsp;' : implode('<hr>', $lista_ausentes))
. "</td>";

echo "</tr>";
?>

<?php endforeach; ?>

</tbody>
</table>

</div>
</div>
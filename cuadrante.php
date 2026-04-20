<?php
require_once __DIR__ . '/includes/db.php';

$dias = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
$dia_actual = strtolower($dias[date('N') - 1]);

if (!in_array($dia_actual, ['lunes','martes','miércoles','jueves','viernes'])) {
    echo "<h2 style='text-align:center; padding:20px;'>No hay guardias programadas para hoy.</h2>";
    return;
}

$hora_actual = date('H:i');
$hora_corte = '14:25';
$turno_texto = ($hora_actual < $hora_corte) ? 'diurno' : 'vespertino';

// Tramos
$stmt = $pdo->prepare("
    SELECT * FROM tramos 
    WHERE LOWER(dia_semana) = ? AND LOWER(descripcion) LIKE ?
    ORDER BY hora_inicio
");
$stmt->execute([$dia_actual, "%$turno_texto%"]);
$tramos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fecha_hoy = date('Y-m-d');
?>

<script>

function actualizarReloj() {
    
    const ahora = new Date();
    const horas = String(ahora.getHours()).padStart(2,'0');
    const minutos = String(ahora.getMinutes()).padStart(2,'0');
    const segundos = String(ahora.getSeconds()).padStart(2,'0');
    document.getElementById('reloj').textContent = `${horas}:${minutos}:${segundos}`;
    
}
setInterval(actualizarReloj, 1000);
actualizarReloj();

window.addEventListener('load', () => {
    document.querySelectorAll('.cuadrante').forEach(fitTextToContainer);
});

window.addEventListener('resize', () => {
    document.querySelectorAll('.cuadrante').forEach(fitTextToContainer);
});
</script>


<h2 class="titulo-cuadrante">
    <span class="titulo-izq">
        <img src="/images/logo.png" alt="Logo centro" style="height:40px;">
        <img src="/images/logoies.png" alt="Logo centro" style="height:40px;">
    </span>

    <span class="titulo-centro">
        Guardias: <?= ucfirst($dia_actual) ?> (<?= date('d/m/Y') ?>)
    </span>

    <span class="titulo-der" id="reloj"></span>
</h2>


<div class="scroll-container">
<table class='tabla-guardias' id="tabla-cuadrante">

<thead>
<tr>
    <th class="tramo">Tramo</th>
    <th class="guardia">Profesores Guardia</th>
    <th class="ausencia">Profesor ausente - Grupo / Aula - Observaciones</th>
    <!--<th class="observaciones">Observaciones</th>-->
</tr>
</thead>

<tbody>

<?php
foreach ($tramos as $tramo) {
echo "<!-- Procesando tramo: {$tramo['descripcion']} ({$tramo['hora_inicio']} - {$tramo['hora_fin']})  $hora_actual-->";
    $es_ahora = ($hora_actual >= $tramo['hora_inicio'] && $hora_actual <= $tramo['hora_fin'])
        ? "class='fila-actual'" : "";

    // =========================
    // GUARDIAS
    // =========================
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
    // =========================
    // AUSENCIAS
    // =========================
    $stmt = $pdo->prepare("
        SELECT a.profesor_id, p.nombre, h.grupo, a.aula, a.observaciones
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

    // =========================
    // INDEXAR AUSENTES
    // =========================
    $ausentes_por_id = [];
    foreach ($ausentes as $a) {
        $ausentes_por_id[$a['profesor_id']] = $a;
    }

    // =========================
    // GUARDIAS CON (F)
    // =========================
    $lista_profesores = [];

    foreach ($profesores_guardia as $p) {
        if (isset($ausentes_por_id[$p['id']])) {
            $lista_profesores[] = "<span style='color:red;'>{$p['nombre']} (F)</span>";
        } else {
            $lista_profesores[] = $p['nombre'];
        }
    }

    $lista_profesores_html = !empty($lista_profesores)
        ? implode('<br>', $lista_profesores)
        : '-';

    // =========================
    // AUSENCIAS (SIN GUARDIAS)
    // =========================
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
                'observaciones' => []
            ];
        }

        if (!empty($a['grupo']) && !in_array($a['grupo'], $ausentes_agrupados[$id]['grupos'])) {
            $ausentes_agrupados[$id]['grupos'][] = $a['grupo'];
        }

        if (!empty($a['observaciones']) && !in_array($a['observaciones'], $ausentes_agrupados[$id]['observaciones'])) {
            $ausentes_agrupados[$id]['observaciones'][] = $a['observaciones'];
        }
    }

    // =========================
    // CONSTRUIR COLUMNAS ALINEADAS
    // =========================
    $lista_ausentes = [];
    $lista_obs = [];

    foreach ($ausentes_agrupados as $a) {        
        $grupos = implode(', ', $a['grupos']);
        $aula = $a['aula'];
        $observaciones = $a['observaciones'];

        $texto = "<b>{$a['nombre']}</b>";
        if ($grupos) $texto .= " {$grupos}";
        if ($aula)   $texto .= " / {$aula}";
        if ($observaciones) $texto .= " - <span class='observaciones'> " . implode('; ', $observaciones) . "</span>";
        $lista_ausentes[] = $texto;
/*
        // OBSERVACIONES (alineadas)
        if (!empty($a['observaciones'])) {
            $lista_obs[] = implode('<br>', $a['observaciones']);
        } else {
            $lista_obs[] = '';
        }
*/            
    }

    // =========================
    // OUTPUT
    // =========================
    echo "<tr {$es_ahora}>";

    echo "<td class='tramo'>{$tramo['hora_inicio']} - {$tramo['hora_fin']}</td>";

    echo "<td class='guardia'>{$lista_profesores_html}</td>";

    echo "<td class='ausencia'>" . (empty($lista_ausentes) ? '' : implode('<hr>', $lista_ausentes)) . "</td>";

//    echo "<td class='observaciones'>" . (empty($lista_obs) ? '' : implode('<hr>', $lista_obs)) . "</td>";

    echo "</tr>";
}
?>

</tbody>
</table>
</div>
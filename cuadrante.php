<?php
require_once __DIR__ . '/admin/includes/db.php';

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

<h2 class="titulo-cuadrante">Guardias: <?= ucfirst($dia_actual) ?> (<?= date('d/m/Y') ?>)</h2>

<div class="scroll-container">
<table class='tabla-guardias'>

<thead>
<tr>
    <th>Tramo</th>
    <th>Profesores Guardia</th>
    <th>Ausencias - Grupo / Aula</th>
    <th>Observaciones</th>
</tr>
</thead>

<tbody>
<?php

foreach ($tramos as $tramo) {

    $es_ahora = ($hora_actual >= $tramo['hora_inicio'] && $hora_actual <= $tramo['hora_fin'])
        ? "class='fila-actual'" : "";

    // =========================
    // GUARDIAS (CORREGIDO)
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
    $lista_obs = [];

    foreach ($ausentes as $a) {

        // 🔴 EXCLUIR GUARDIAS
        if (isset($ids_guardia[$a['profesor_id']])) {
            continue;
        }

        $id = $a['profesor_id'];

        if (!isset($ausentes_agrupados[$id])) {
            $ausentes_agrupados[$id] = [
                'nombre' => $a['nombre'],
                'grupos' => [],
                'aula' => $a['aula'] ?? ''
            ];
        }

        // Grupos sin duplicar
        if (!empty($a['grupo']) && !in_array($a['grupo'], $ausentes_agrupados[$id]['grupos'])) {
            $ausentes_agrupados[$id]['grupos'][] = $a['grupo'];
        }

        // Observaciones únicas
        if (!empty($a['observaciones']) && !in_array($a['observaciones'], $lista_obs)) {
            $lista_obs[] = $a['observaciones'];
        }
    }

    // Construcción salida ausencias
    $lista_ausentes = [];

    foreach ($ausentes_agrupados as $a) {

        $grupos = implode(', ', $a['grupos']);
        $aula = $a['aula'];

        $texto = "<b>{$a['nombre']}</b>";

        if ($grupos) $texto .= " {$grupos}";
        if ($aula)   $texto .= " / {$aula}";

        $lista_ausentes[] = $texto;
    }

    // =========================
    // OUTPUT
    // =========================
    echo "<tr {$es_ahora}>";

    echo "<td>{$tramo['hora_inicio']} - {$tramo['hora_fin']}</td>";

    echo "<td>{$lista_profesores_html}</td>";

    echo "<td>" . (empty($lista_ausentes) ? '' : implode('<hr>', $lista_ausentes)) . "</td>";

    echo "<td>" . (empty($lista_obs) ? '' : implode('<hr>', $lista_obs)) . "</td>";

    echo "</tr>";
}
?>

</tbody>
</table>
</div>
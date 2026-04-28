<?php
/**
 * ============================================================
 * SISTEMA DE GESTIÓN DE AUSENCIAS DEL PROFESORADO
 * ============================================================
 *
 * Este script implementa la gestión completa de ausencias:
 *
 * FUNCIONALIDADES:
 * - Selección de profesor
 * - Selección de semana (a partir de una fecha)
 * - Visualización del horario semanal
 * - Marcado/desmarcado de ausencias por tramo
 * - Gestión de ausencias por día completo
 * - Asignación de aula
 * - Asignación de observaciones
 * - Asignación de tipo de ausencia por tramo o en bloque diario
 *
 * BASE DE DATOS:
 * - profesores
 * - horarios
 * - tramos
 * - ausencias
 * - tipos_ausencia
 *
 * ============================================================
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';


/**
 * ============================================================
 * CONFIGURACIÓN DE DÍAS DE LA SEMANA
 * ============================================================
 * Relación numérica (ISO-8601) → nombre del día
 * Se usa para:
 * - Construcción de calendario semanal
 * - Indexación de tramos
 * - Persistencia en base de datos
 * ============================================================
 */
$dias_num = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'];


/**
 * ============================================================
 * PARÁMETROS DE ENTRADA
 * ============================================================
 * profesor_id: profesor seleccionado
 * fecha_seleccionada: fecha de referencia de la semana
 * mensaje: feedback de operación
 * ============================================================
 */
$profesor_id = $_GET['profesor_id'] ?? null;
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$mensaje = "";


/**
 * ============================================================
 * CÁLCULO DEL LUNES DE LA SEMANA
 * ============================================================
 * A partir de cualquier fecha se obtiene el lunes
 * de la semana correspondiente.
 * ============================================================
 */
$dt = new DateTime($fecha_seleccionada);
$iso = (int)$dt->format('N');
$monday = clone $dt;
$monday->modify('-'.($iso-1).' days');


/**
 * ============================================================
 * GENERACIÓN DE FECHAS POR DÍA
 * ============================================================
 * Genera un array con la fecha exacta de cada día de la semana:
 * [1 => lunes, 2 => martes, ...]
 * ============================================================
 */
$fechas_dia = [];
foreach ($dias_num as $num=>$nombre){
    $d = clone $monday;
    $d->modify('+'.($num-1).' days');
    $fechas_dia[$num] = $d->format('Y-m-d');
}


/**
 * ============================================================
 * OBTENCIÓN DE PROFESORES
 * ============================================================
 * Se utiliza en el selector de la interfaz.
 * ============================================================
 */
$profesores = $pdo->query("SELECT id,nombre FROM profesores ORDER BY nombre")
    ->fetchAll(PDO::FETCH_ASSOC);


/**
 * ============================================================
 * TIPOS DE AUSENCIA
 * ============================================================
 * Catálogo de tipos posibles de ausencia:
 * - DAP
 * - Baja
 * - Formación
 * etc.
 * ============================================================
 */
$tipos_ausencia = $pdo->query("SELECT * FROM tipos_ausencia ORDER BY descripcion")
    ->fetchAll(PDO::FETCH_ASSOC);


/**
 * ============================================================
 * CARGA DE TRAMOS HORARIOS
 * ============================================================
 * Estructura:
 * $tramos_por_dia[día][id_tramo] = datos del tramo
 * ============================================================
 */
$tramos_por_dia = [];
$stmt = $pdo->query("
    SELECT *
    FROM tramos
    ORDER BY FIELD(dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes'),
             hora_inicio
");

foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $t){
    $dia_num = array_search($t['dia_semana'],$dias_num);
    $tramos_por_dia[$dia_num][$t['id']] = $t;
}


/**
 * ============================================================
 * HORARIO DE DOCENCIA DEL PROFESOR
 * ============================================================
 * Se indexa por id de tramo para acceso rápido.
 * ============================================================
 */
$horarios = [];

if($profesor_id){
    $stmt = $pdo->prepare("
        SELECT dia_semana,tramo_horario,asignatura,grupo,aula
        FROM horarios
        WHERE profesor_id=? AND tipo='Docencia'
    ");

    $stmt->execute([$profesor_id]);

    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $h){
        $horarios[$h['tramo_horario']] = $h;
    }
}


/**
 * ============================================================
 * HORARIO DE GUARDIAS DEL PROFESOR
 * ============================================================
 */
$guardias = [];

if($profesor_id){
    $stmt = $pdo->prepare("
        SELECT tramo_horario,dia_semana
        FROM horarios
        WHERE profesor_id=? AND tipo='Guardias'
    ");

    $stmt->execute([$profesor_id]);

    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $g){
        $guardias[$g['tramo_horario']] = $g;
    }
}


/**
 * ============================================================
 * AUSENCIAS EXISTENTES
 * ============================================================
 * Se cargan para:
 * - Preseleccionar checkboxes
 * - Mostrar valores previos
 * - Permitir edición incremental
 *
 * Se indexan por:
 * dia_tramo (ej: "3_15")
 * ============================================================
 */
$ausencias_existentes = [];
$observaciones_existentes = [];
$aulas_existentes = [];
$tipos_existentes = [];

if($profesor_id){

    $stmt = $pdo->prepare("
        SELECT tramo_id,dia_semana,aula,observaciones,tipo
        FROM ausencias
        WHERE profesor_id=?
        AND fecha BETWEEN ? AND ?
    ");

    $stmt->execute([
        $profesor_id,
        $fechas_dia[1],
        $fechas_dia[5]
    ]);

    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $a){

        // Clave compuesta día + tramo
        $key = $a['dia_semana'].'_'.$a['tramo_id'];

        $ausencias_existentes[$key] = true;
        $observaciones_existentes[$key] = $a['observaciones'];
        $aulas_existentes[$key] = $a['aula'];
        $tipos_existentes[$key] = $a['tipo'];
    }
}


/**
 * ============================================================
 * PROCESAMIENTO DEL FORMULARIO
 * ============================================================
 * Inserta, actualiza o elimina ausencias
 * ============================================================
 */
if ($_SERVER['REQUEST_METHOD']==='POST'){

    $profesor_id = $_POST['profesor_id'];

    $seleccion = $_POST['ausencia'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];
    $aulas = $_POST['aula'] ?? [];
    $tipos = $_POST['tipo'] ?? [];

    // Optimización para búsquedas O(1)
    $seleccion = array_flip($seleccion);


    /**
     * INSERT
     */
    $stmt_insert = $pdo->prepare("
        INSERT INTO ausencias
        (
            profesor_id,
            tramo_id,
            dia_semana,
            fecha,
            aula,
            observaciones,
            tipo
        )
        VALUES (?,?,?,?,?,?,?)
    ");


    /**
     * UPDATE
     */
    $stmt_update = $pdo->prepare("
        UPDATE ausencias
        SET aula=?,
            observaciones=?,
            tipo=?
        WHERE profesor_id=?
        AND tramo_id=?
        AND fecha=?
    ");


    /**
     * Recorre todos los tramos del horario semanal
     */
    foreach($tramos_por_dia as $dia_num=>$tramos){

        foreach($tramos as $tramo_id=>$t){

            $key = "{$dia_num}_{$tramo_id}";

            $aula_val = $aulas[$key]
                ?? ($horarios[$tramo_id]['aula'] ?? '');

            $obs_val = $observaciones[$key] ?? '';
            $tipo_val = $tipos[$key] ?? null;

            $existe = isset($ausencias_existentes[$key]);
            $marcado = isset($seleccion[$key]);


            // NUEVA AUSENCIA
            if($marcado && !$existe){

                $stmt_insert->execute([
                    $profesor_id,
                    $tramo_id,
                    $dias_num[$dia_num],
                    $fechas_dia[$dia_num],
                    $aula_val,
                    $obs_val,
                    $tipo_val
                ]);

            // ACTUALIZACIÓN
            } elseif($marcado && $existe){

                $current_aula = $aulas_existentes[$key] ?? '';
                $current_obs = $observaciones_existentes[$key] ?? '';
                $current_tipo = $tipos_existentes[$key] ?? '';

                if(
                    $current_aula !== $aula_val
                    || $current_obs !== $obs_val
                    || $current_tipo != $tipo_val
                ){

                    $stmt_update->execute([
                        $aula_val,
                        $obs_val,
                        $tipo_val,
                        $profesor_id,
                        $tramo_id,
                        $fechas_dia[$dia_num]
                    ]);
                }

            // ELIMINACIÓN
            } elseif(!$marcado && $existe){

                $pdo->prepare("
                    DELETE FROM ausencias
                    WHERE profesor_id=?
                    AND tramo_id=?
                    AND fecha=?
                ")->execute([
                    $profesor_id,
                    $tramo_id,
                    $fechas_dia[$dia_num]
                ]);
            }
        }
    }

    header("Location: dashboard.php?seccion=ausencias&ok=1&profesor_id=".$_POST['profesor_id']);
    exit;
}


/**
 * ============================================================
 * MENSAJE DE CONFIRMACIÓN
 * ============================================================
 */
if(isset($_GET['ok'])){
    echo "<p style='color:green;font-weight:bold;'>✅ Ausencias actualizadas</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ausencias de Profesores</title>

<style>

table{
    border-collapse: collapse;
    width:100%;
}

th,td{
    border:1px solid #ccc;
    padding:8px;
    text-align:center;
    vertical-align:top;
}

th{
    background:#007bff;
    color:white;
}

.celda{
    position:relative;
    min-height:50px;
}

.clase{
    background:#28a745;
    color:white;
    padding:3px;
    border-radius:4px;
    margin-bottom:2px;
}

.guardia{
    background:#ffc107;
    color:black;
    padding:3px;
    border-radius:4px;
    margin-bottom:2px;
}

.ausente{
    background:#dc3545 !important;
}

input[type=checkbox]{
    position:absolute;
    bottom:5px;
    right:5px;
}

input.aula,
textarea.observacion,
select.tipo-tramo{
    width:95%;
    margin-top:4px;
}

.select-dia{
    width:95%;
    margin-top:5px;
}

</style>

<script>

function marcarDiaCompleto(dia, checkbox){

    const cbs = document.querySelectorAll('input[data-dia="'+dia+'"]');

    cbs.forEach(cb=>{
        cb.checked = checkbox.checked;

        const td = cb.closest('td');

        if(cb.checked){
            td.classList.add('ausente');
        }else{
            td.classList.remove('ausente');
        }
    });
}

function toggleAusencia(td){

    const cb = td.querySelector('input[type=checkbox]');

    if(cb){

        cb.checked = !cb.checked;

        td.classList.toggle('ausente', cb.checked);
    }
}

// --- Aplicar tipo a todos los tramos del día
function aplicarTipoDia(dia, select){

    const selects = document.querySelectorAll('.tipo-dia-' + dia);

    selects.forEach(s=>{
        s.value = select.value;
    });
}

</script>

</head>

<body>

<h2>Horario y Ausencias de Profesores</h2>

<?php if($mensaje): ?>
<p style="color:green; font-weight:bold;">
    <?= $mensaje ?>
</p>
<?php endif; ?>

<form method="get" action="dashboard.php">

<input type="hidden" name="seccion" value="ausencias">

<label>Profesor:</label>

<select name="profesor_id" onchange="this.form.submit()">

<option value="">-- Selecciona --</option>

<?php foreach($profesores as $p): ?>

<option value="<?= $p['id'] ?>"
    <?= $profesor_id==$p['id']?'selected':'' ?>>

    <?= htmlspecialchars($p['nombre']) ?>

</option>

<?php endforeach; ?>

</select>

<label>Fecha cualquiera de la semana:</label>

<input
    type="date"
    name="fecha"
    value="<?= $fecha_seleccionada ?>"
    onchange="this.form.submit()"
>

</form>

<?php if($profesor_id): ?>

<form method="post">

<input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">

<h3>Horario completo</h3>

<table>

<tr>

<th>Hora / Tramo</th>

<?php foreach($dias_num as $num=>$nombre): ?>

<th>

    <?= $nombre ?>

    <br>

    <label>
        <input
            type="checkbox"
            onchange="marcarDiaCompleto(<?= $num ?>, this)"
        >
        Día completo
    </label>

    <br><br>

    <label>Tipo:</label>

    <select
        class="select-dia"
        onchange="aplicarTipoDia(<?= $num ?>, this)"
    >

        <option value="">-- Tipo --</option>

        <?php foreach($tipos_ausencia as $tipo): ?>

        <option value="<?= $tipo['id'] ?>">
            <?= htmlspecialchars($tipo['descripcion']) ?>
        </option>

        <?php endforeach; ?>

    </select>

</th>

<?php endforeach; ?>

</tr>

<?php

$tramos_lunes = array_values($tramos_por_dia[1]);

foreach($tramos_lunes as $i=>$t_lunes):

    echo "<tr>";

    echo "<td>".
        htmlspecialchars(
            $t_lunes['hora_inicio']
            .'-'
            .$t_lunes['hora_fin']
            .' '
            .$t_lunes['descripcion']
        ).
        "</td>";

    foreach($dias_num as $dia_num=>$dia_nombre):

        $tramos_dia = array_values($tramos_por_dia[$dia_num]);

        $t = $tramos_dia[$i] ?? null;

        $tramo_id = $t['id'] ?? 0;

        $h = $horarios[$tramo_id] ?? null;
        $g = $guardias[$tramo_id] ?? null;

        $key = "{$dia_num}_{$tramo_id}";

        $checked='';

        $clase_td = 'celda';

        if(($h || $g) && isset($ausencias_existentes[$key])){
            $checked='checked';
            $clase_td .= ' ausente';
        }

        echo "<td class='{$clase_td}'";

        echo ($h || $g)
            ? " onclick='toggleAusencia(this)'"
            : "";

        echo ">";

        if($h){

            echo "<div class='clase'>"
                .htmlspecialchars(
                    $h['asignatura']
                    .' '
                    .$h['grupo']
                    .' '
                    .$h['aula']
                )
                ."</div>";
        }

        if($g){
            echo "<div class='guardia'>Guardia</div>";
        }

        if($h || $g){

            echo "
            <input
                type='checkbox'
                name='ausencia[]'
                value='{$key}'
                data-dia='{$dia_num}'
                {$checked}
            >";

            // --- Aula
            $aula_val = $aulas_existentes[$key]
                ?? ($h['aula'] ?? '');

            echo "
            <input
                type='text'
                placeholder='Aula'
                class='aula'
                name='aula[{$key}]'
                value='".htmlspecialchars($aula_val)."'
            >";

            // --- Observaciones
            $obs_val = $observaciones_existentes[$key] ?? '';

            echo "
            <textarea
                placeholder='Observaciones'
                class='observacion'
                name='observacion[{$key}]'
            >".htmlspecialchars($obs_val)."</textarea>";

            // --- Tipo
            $tipo_actual = $tipos_existentes[$key] ?? '';

            echo "
            <select
                class='tipo-tramo tipo-dia-{$dia_num}'
                name='tipo[{$key}]'
            >";

            echo "<option value=''>-- Tipo --</option>";

            foreach($tipos_ausencia as $tipo){

                $selected = ($tipo_actual == $tipo['id'])
                    ? 'selected'
                    : '';

                echo "
                <option
                    value='{$tipo['id']}'
                    {$selected}
                >
                    ".htmlspecialchars($tipo['descripcion'])."
                </option>";
            }

            echo "</select>";
        }

        echo "</td>";

    endforeach;

    echo "</tr>";

endforeach;

?>

</table>

<br>

<button type="submit">
    Guardar ausencias
</button>

</form>

<?php endif; ?>

</body>
</html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$dias_num = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'];

$profesor_id = $_GET['profesor_id'] ?? null;
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$mensaje = "";

/**
 * ============================================================
 * SEMANA
 * ============================================================
 */
$dt = new DateTime($fecha_seleccionada);
$iso = (int)$dt->format('N');
$monday = clone $dt;
$monday->modify('-'.($iso-1).' days');

$fechas_dia = [];
foreach ($dias_num as $num=>$nombre){
    $d = clone $monday;
    $d->modify('+'.($num-1).' days');
    $fechas_dia[$num] = $d->format('Y-m-d');
}

/**
 * ============================================================
 * DATOS BASE
 * ============================================================
 */
$profesores = $pdo->query("SELECT id,nombre FROM profesores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tipos_ausencia = $pdo->query("SELECT * FROM tipos_ausencia ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

/**
 * ============================================================
 * TRAMOS
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
 * HORARIOS
 * ============================================================
 */
$horarios = [];
$guardias = [];

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
 * AUSENCIAS
 * ============================================================
 */
$ausencias_existentes = [];
$aulas_existentes = [];
$observaciones_existentes = [];
$tipos_existentes = [];
$ausencias_dia_completo = [];

if($profesor_id){

    $stmt = $pdo->prepare("
        SELECT tramo_id,dia_semana,fecha,aula,observaciones,tipo
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

        if((int)$a['tramo_id'] === 0){
            $ausencias_dia_completo[$a['fecha']] = true;
            continue;
        }

        $key = $a['tramo_id'];

        $ausencias_existentes[$key] = true;
        $aulas_existentes[$key] = $a['aula'];
        $observaciones_existentes[$key] = $a['observaciones'];
        $tipos_existentes[$key] = $a['tipo'];
    }
}

/**
 * ============================================================
 * POST
 * ============================================================
 */
if ($_SERVER['REQUEST_METHOD']==='POST'){

    $profesor_id = $_POST['profesor_id'];

    $seleccion = $_POST['ausencia'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];
    $aulas = $_POST['aula'] ?? [];
    $tipos = $_POST['tipo'] ?? [];
    $dia_completo = $_POST['dia_completo'] ?? [];
    $tipo_dia_completo = $_POST['tipo_ausencia_dia'] ?? [];
    $seleccion = array_flip($seleccion);

    $tipo_default = !empty($tipos) ? reset($tipos) : null;

    $stmt_insert = $pdo->prepare("
        INSERT INTO ausencias
        (profesor_id,tramo_id,dia_semana,fecha,aula,observaciones,tipo)
        VALUES (?,?,?,?,?,?,?)
    ");

    $stmt_exists_day = $pdo->prepare("
        SELECT id 
        FROM ausencias
        WHERE profesor_id = ?
        AND tramo_id = 0
        AND fecha = ?
        LIMIT 1
    ");

    foreach($dias_num as $dia_num=>$nombre){

        $fecha = $fechas_dia[$dia_num];

        /**
         * ============================================================
         * ✔ NUEVO: DÍA COMPLETO
         * ============================================================
         */
        if(!empty($dia_completo[$dia_num])){

            $tipo_val = $tipo_dia_completo[$dia_num] ?? $tipo_default;

            $stmt_exists_day->execute([$profesor_id, $fecha]);
            $existing = $stmt_exists_day->fetchColumn();

            if($existing){
                $pdo->prepare("
                    UPDATE ausencias
                    SET tipo = ?, dia_completo = 1
                    WHERE id = ?
                ")->execute([
                    $tipo_val,
                    $existing
                ]);

            } else {
                $stmt_insert->execute([
                    $profesor_id,
                    0,
                    $nombre,
                    $fecha,
                    '',
                    '',
                    $tipo_val                
                ]);
            }

            continue;
        }

        /**
         * ============================================================
         * ✔ NUEVO: CASO SIN TRAMOS (profesor sin horario)
         * ============================================================
         */
        if(empty($tramos_por_dia[$dia_num])){

            if(isset($seleccion[$dia_num.'_0'])){

                $tipo_val = $tipo_dia_completo[$dia_num] ?? $tipo_default;

                $stmt_exists_day->execute([$profesor_id, $fecha]);
                $existing = $stmt_exists_day->fetchColumn();

                if($existing){

                    $pdo->prepare("
                        UPDATE ausencias
                        SET tipo = ?, dia_completo = 0
                        WHERE id = ?
                    ")->execute([
                        $tipo_val,
                        $existing
                    ]);

                } else {

                    $stmt_insert->execute([
                        $profesor_id,
                        0,
                        $nombre,
                        $fecha,
                        '',
                        '',
                        $tipo_val                        
                    ]);
                }
            }

            continue;
        }

        /**
         * ============================================================
         * ✔ CASO NORMAL (TU LÓGICA ORIGINAL)
         * ============================================================
         */
        foreach($tramos_por_dia[$dia_num] as $tramo_id=>$t){

            $key = "{$dia_num}_{$tramo_id}";

            $marcado = isset($seleccion[$key]);

            $aula_val = $aulas[$key] ?? '';
            $obs_val = $observaciones[$key] ?? '';
            $tipo_val = $tipos[$key] ?? $tipo_default;

            $existe = isset($ausencias_existentes[$tramo_id]);

            if($marcado && !$existe){

                $stmt_insert->execute([
                    $profesor_id,
                    $tramo_id,
                    $nombre,
                    $fecha,
                    $aula_val,
                    $obs_val,
                    $tipo_val
                ]);
            }

            if(!$marcado && $existe){

                $pdo->prepare("
                    DELETE FROM ausencias
                    WHERE profesor_id=?
                    AND tramo_id=?
                    AND fecha=?
                ")->execute([
                    $profesor_id,
                    $tramo_id,
                    $fecha
                ]);
            }
        }
    }

    header("Location: dashboard.php?seccion=ausencias&ok=1&profesor_id=".$profesor_id);
    exit;
}

/**
 * ============================================================
 * MENSAJE OK
 * ============================================================
 */
if(isset($_GET['ok'])){
    echo "<p style='color:green;font-weight:bold;'>✅ Ausencias actualizadas</p>";
}
?>

<!-- ============================================================
     AQUÍ CONTINÚA TU HTML ORIGINAL SIN MODIFICAR
     (selector profesor, tabla, cuadrante, JS, etc.)
============================================================ -->

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
<!-- Encabezados de días con opciones de día completo y tipo de ausencia -->
<th>Hora / Tramo</th>
<?php foreach($dias_num as $num=>$nombre): ?>
<th>
    <?= $nombre ?>
    <br>
    <label>
        <input type="checkbox" name="dia_completo[<?= $num ?>]" onchange="marcarDiaCompleto(<?= $num ?>, this)" <?= isset($ausencias_dia_completo[$fechas_dia[$num]]) ? 'checked' : '' ?>>
        Día completo
    </label>
    <br>
    <label>Tipo:</label>
    <select class="select-dia" name="tipo_ausencia_dia[<?= $num ?>]" onchange="aplicarTipoDia(<?= $num ?>, this)" >        
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
// Usamos lunes como referencia para filas
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
        //if(($h || $g) && isset($ausencias_existentes[$key])){
        if(($h || $g) && isset($ausencias_existentes[$tramo_id])){
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
            error_log("Tramo ID: {$tramo_id}, Día: {$dia_num}, Clave: {$key}, Marcado: {$checked}");
            error_log("
            <input
                type='checkbox'
                name='ausencia[]'
                value='{$key}'
                data-dia='{$dia_num}'
                {$checked}
            >");
            // --- Aula
            //$aula_val = $aulas_existentes[$key]
            $aula_val = $aulas_existentes[$tramo_id]
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
            //$obs_val = $observaciones_existentes[$key] ?? '';
            $obs_val = $observaciones_existentes[$tramo_id] ?? '';
            echo "
            <textarea
                placeholder='Observaciones'
                class='observacion'
                name='observacion[{$key}]'
            >".htmlspecialchars($obs_val)."</textarea>";
            // --- Tipo
            //$tipo_actual = $tipos_existentes[$key] ?? '';
            $tipo_actual = $tipos_existentes[$tramo_id] ?? '';
            echo "
            <select
                class='tipo-tramo tipo-dia-{$dia_num}'
                name='tipo[{$key}]'
            >";            
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
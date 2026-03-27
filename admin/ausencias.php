<?php
require_once __DIR__ . '/includes/db.php';

// --- Configuración días
$dias_num = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes'];

// --- Obtener profesor y fecha
$profesor_id = $_GET['profesor_id'] ?? null;
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$mensaje = "";

// --- Calcular lunes de la semana
$dt = new DateTime($fecha_seleccionada);
$iso = (int)$dt->format('N');
$monday = clone $dt;
$monday->modify('-'.($iso-1).' days');

// --- Fechas de cada día
$fechas_dia = [];
foreach ($dias_num as $num=>$nombre){
    $d = clone $monday;
    $d->modify('+'.($num-1).' days');
    $fechas_dia[$num] = $d->format('Y-m-d');
}

// --- Profesores
$profesores = $pdo->query("SELECT id,nombre FROM profesores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// --- Cargar todos los tramos por día
$tramos_por_dia = [];
$stmt = $pdo->query("SELECT * FROM tramos ORDER BY FIELD(dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes'), hora_inicio");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $t){
    $dia_num = array_search($t['dia_semana'],$dias_num);
    $tramos_por_dia[$dia_num][$t['id']] = $t; // clave = id real del tramo
}

// --- Horario profesor
$horarios=[];
if($profesor_id){
    $stmt=$pdo->prepare("SELECT dia_semana,tramo_horario,asignatura,grupo,aula FROM horarios WHERE profesor_id=? AND tipo='Docencia'");
    $stmt->execute([$profesor_id]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $h){
        $horarios[$h['tramo_horario']] = $h; // clave = id del tramo
    }
}

// --- Guardias profesor
$guardias=[];
if($profesor_id){
    $stmt=$pdo->prepare("SELECT tramo_horario,dia_semana FROM horarios WHERE profesor_id=? AND tipo='Guardias'");
    $stmt->execute([$profesor_id]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $g){
        $guardias[$g['tramo_horario']] = $g; // clave = id del tramo
    }
}

// --- Ausencias existentes
$ausencias_existentes = [];
$observaciones_existentes = [];
$aulas_existentes = [];
if($profesor_id){
    $stmt=$pdo->prepare("SELECT tramo_id,dia_semana,aula,observaciones FROM ausencias WHERE profesor_id=? AND fecha BETWEEN ? AND ?");
    $stmt->execute([$profesor_id,$fechas_dia[1],$fechas_dia[5]]);
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $a){
        $ausencias_existentes[$a['tramo_id']] = true;
        $observaciones_existentes[$a['tramo_id']] = $a['observaciones'];
        $aulas_existentes[$a['tramo_id']] = $a['aula'];
    }
}

// --- Guardar ausencias con aula y observaciones (solo cambios)
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $profesor_id = $_POST['profesor_id'];
    $seleccion = $_POST['ausencia'] ?? [];
    $observaciones = $_POST['observacion'] ?? [];
    $aulas = $_POST['aula'] ?? [];

    $seleccion = array_flip($seleccion); // para búsquedas rápidas

    // --- Insertar o actualizar ausencias marcadas
    $stmt_insert = $pdo->prepare("INSERT INTO ausencias (profesor_id,tramo_id,dia_semana,fecha,aula,observaciones) VALUES (?,?,?,?,?,?)");
    $stmt_update = $pdo->prepare("UPDATE ausencias SET aula=?, observaciones=? WHERE profesor_id=? AND tramo_id=? AND fecha=?");

    foreach($tramos_por_dia as $dia_num=>$tramos){
        foreach($tramos as $tramo_id=>$t){
            $key = "{$dia_num}_{$tramo_id}";
            $aula_val = $aulas[$key] ?? ($horarios[$tramo_id]['aula'] ?? '');
            $obs_val = $observaciones[$key] ?? '';

            $existe = isset($ausencias_existentes[$tramo_id]);
            $marcado = isset($seleccion[$key]);

            if($marcado && !$existe){
                // Nueva ausencia
                $stmt_insert->execute([$profesor_id,$tramo_id,$dias_num[$dia_num],$fechas_dia[$dia_num],$aula_val,$obs_val]);
            } elseif($marcado && $existe){
                // Actualizar aula/observaciones si han cambiado
                $current_aula = $aulas_existentes[$tramo_id] ?? '';
                $current_obs = $observaciones_existentes[$tramo_id] ?? '';
                if($current_aula !== $aula_val || $current_obs !== $obs_val){
                    $stmt_update->execute([$aula_val,$obs_val,$profesor_id,$tramo_id,$fechas_dia[$dia_num]]);
                }
            } elseif(!$marcado && $existe){
                // Eliminar ausencia desmarcada
                $pdo->prepare("DELETE FROM ausencias WHERE profesor_id=? AND tramo_id=? AND fecha=?")
                    ->execute([$profesor_id,$tramo_id,$fechas_dia[$dia_num]]);
            }
        }
    }

    $mensaje = "✅ Ausencias actualizadas";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Ausencias de Profesores</title>
<style>
table { border-collapse: collapse; width:100%; }
th,td{border:1px solid #ccc; padding:8px; text-align:center; vertical-align:top;}
th{background:#007bff;color:white;}
.celda{position:relative; min-height:50px;}
.clase{background:#28a745;color:white;padding:3px;border-radius:4px; margin-bottom:2px;}
.guardia{background:#ffc107;color:black;padding:3px;border-radius:4px; margin-bottom:2px;}
.ausente{background:#dc3545 !important;}
input[type=checkbox]{position:absolute; bottom:5px; right:5px;}
input.aula, textarea.observacion{width:95%; margin-top:2px;}
</style>
<script>
function marcarDiaCompleto(dia, checkbox){
    const cbs = document.querySelectorAll('input[data-dia="'+dia+'"]');
    cbs.forEach(cb=>cb.checked=checkbox.checked);
    cbs.forEach(cb=>{
        const td = cb.closest('td');
        if(cb.checked){ td.classList.add('ausente'); } else { td.classList.remove('ausente'); }
    });
}
function toggleAusencia(td){
    const cb = td.querySelector('input[type=checkbox]');
    if(cb){
        cb.checked = !cb.checked;
        td.classList.toggle('ausente', cb.checked);
    }
}
</script>
</head>
<body>
<h2>Horario y Ausencias de Profesores</h2>
<?php if($mensaje): ?><p style="color:green; font-weight:bold;"><?= $mensaje ?></p><?php endif; ?>

<form method="get" action="ausencias.php">
<label>Profesor:</label>
<select name="profesor_id" onchange="this.form.submit()">
<option value="">-- Selecciona --</option>
<?php foreach($profesores as $p): ?>
<option value="<?= $p['id'] ?>" <?= $profesor_id==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
<?php endforeach; ?>
</select>
<label>Fecha cualquiera de la semana:</label>
<input type="date" name="fecha" value="<?= $fecha_seleccionada ?>" onchange="this.form.submit()">
</form>

<?php if($profesor_id): ?>
<form method="post">
<input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">

<h3>Horario completo</h3>
<table>
<tr>
<th>Hora / Tramo</th>
<?php foreach($dias_num as $num=>$nombre): ?>
<th><?= $nombre ?><br><label><input type="checkbox" onchange="marcarDiaCompleto(<?= $num ?>, this)"> Día completo</label></th>
<?php endforeach; ?>
</tr>

<?php
// Usamos lunes como referencia para filas
$tramos_lunes = array_values($tramos_por_dia[1]);
foreach($tramos_lunes as $i=>$t_lunes):
    echo "<tr>";
    echo "<td>".htmlspecialchars($t_lunes['hora_inicio'].'-'.$t_lunes['hora_fin'].' '.$t_lunes['descripcion'])."</td>";

    foreach($dias_num as $dia_num=>$dia_nombre):
        $tramos_dia = array_values($tramos_por_dia[$dia_num]);
        $t = $tramos_dia[$i] ?? null;
        $tramo_id = $t['id'] ?? 0;

        $h = $horarios[$tramo_id] ?? null;
        $g = $guardias[$tramo_id] ?? null;

        echo "<td class='celda'";
        $checked='';
        if(($h || $g) && isset($ausencias_existentes[$tramo_id])){
            $checked='checked';
            echo " class='celda ausente'";
        }

        echo ($h || $g) ? " onclick='toggleAusencia(this)'" : "";
        echo ">";

        if($h) echo "<div class='clase'>".htmlspecialchars($h['asignatura'].' '.$h['grupo'].' '.$h['aula'])."</div>";
        if($g) echo "<div class='guardia'>Guardia</div>";

        if($h || $g){
            echo "<input type='checkbox' name='ausencia[]' value='{$dia_num}_{$tramo_id}' data-dia='{$dia_num}' $checked>";
            // Aula y observación
            $aula_val = $aulas_existentes[$tramo_id] ?? ($h['aula'] ?? '');
            $obs_val = $observaciones_existentes[$tramo_id] ?? '';
            echo "<input type='text' placeholder='Aula' class='aula' name='aula[{$dia_num}_{$tramo_id}]' value='".htmlspecialchars($aula_val)."'>";
            echo "<textarea placeholder='Observaciones' class='observacion' name='observacion[{$dia_num}_{$tramo_id}]'>".htmlspecialchars($obs_val)."</textarea>";
        }

        echo "</td>";
    endforeach;

    echo "</tr>";
endforeach;
?>
</table>
<button type="submit">Guardar ausencias</button>
</form>
<?php endif; ?>
<a href="dashboard.php" class="btn-dashboard">⬅ Volver al Dashboard</a>
</body>
</html>
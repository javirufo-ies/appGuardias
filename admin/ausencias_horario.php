<?php
require_once __DIR__ . '/includes/db.php';

$dias = [1=>'lunes',2=>'martes',3=>'miércoles',4=>'jueves',5=>'viernes'];
$profesor_id = $_GET['profesor_id'] ?? null;

// --- Validar profesor
if (!$profesor_id) die("Profesor no especificado");
$stmt = $pdo->prepare("SELECT * FROM profesores WHERE id=?");
$stmt->execute([$profesor_id]);
$profesor = $stmt->fetch();
if (!$profesor) die("Profesor no encontrado");

// --- POST AJAX para mover clases/guardias
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax'])) {
    $tipo = $_POST['tipo'] ?? 'Docencia'; // Docencia o Guardia
    $dia_origen = $_POST['dia_origen'];
    $tramo_origen = $_POST['tramo_origen'];
    $dia_destino = $_POST['dia_destino'];
    $tramo_destino = $_POST['tramo_destino'];

    if ($tipo==='Docencia') {
        $stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id=? AND tramo_horario=? AND dia_semana=? AND tipo='Docencia'");
        $stmt->execute([$profesor_id, $tramo_origen, $dia_origen]);
        $clase = $stmt->fetch();
        if ($clase) {
            $pdo->prepare("DELETE FROM horarios WHERE profesor_id=? AND tramo_horario=? AND dia_semana=?")->execute([$profesor_id, $tramo_destino, $dia_destino]);
            $pdo->prepare("DELETE FROM horarios WHERE id=?")->execute([$clase['id']]);
            $pdo->prepare("INSERT INTO horarios 
                (profesor_id,dia_semana,tramo_horario,asignatura,grupo,aula,tipo,tramos)
                VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$profesor_id,$dia_destino,$tramo_destino,$clase['asignatura'],$clase['grupo'],$clase['aula'],'Docencia',$clase['tramos']]);
        }
    } else { // Guardia
        $stmt = $pdo->prepare("SELECT * FROM guardias WHERE profesor_id=? AND tramo_id=?");
        $stmt->execute([$profesor_id,$tramo_origen]);
        $guardia = $stmt->fetch();
        if ($guardia) {
            $pdo->prepare("DELETE FROM guardias WHERE profesor_id=? AND tramo_id=?")->execute([$profesor_id,$tramo_destino]);
            $pdo->prepare("DELETE FROM guardias WHERE id=?")->execute([$guardia['id']]);
            $pdo->prepare("INSERT INTO guardias (profesor_id,tramo_id) VALUES (?,?)")->execute([$profesor_id,$tramo_destino]);
        }
    }
    echo json_encode(['ok'=>true]);
    exit;
}

// --- Selección de semana
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
try { $dt = new DateTime($fecha_seleccionada); } catch(Exception $e){ $dt = new DateTime(); }
$iso = (int)$dt->format('N');
$monday = clone $dt; $monday->modify('-'.($iso-1).' days');
$fechas_dia = [];
foreach($dias as $num=>$nombre){
    $d=clone $monday;
    if($num>1) $d->modify('+'.($num-1).' days');
    $fechas_dia[$nombre]=$d->format('Y-m-d');
}

// --- Obtener tramos
$stmt = $pdo->query("SELECT * FROM tramos ORDER BY FIELD(dia_semana,'Lunes','Martes','Miércoles','Jueves','Viernes'),hora_inicio");
$tramos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cuadrante = [];
foreach($tramos as $t){
    $dia=strtolower($t['dia_semana']);
    $cuadrante[$dia][$t['id']]=$t;
}

// --- Obtener docencias del profesor
$stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id=?");
$stmt->execute([$profesor_id]);
$horarios_profesor = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $h){
    $dia=strtolower($h['dia_semana']);
    $horarios_profesor[$dia][$h['tramo_horario']]=$h;
}

// --- Obtener guardias del profesor
$stmt = $pdo->prepare("SELECT g.*, t.dia_semana FROM guardias g JOIN tramos t ON g.tramo_id=t.id WHERE profesor_id=?");
$stmt->execute([$profesor_id]);
$guardias_profesor = [];
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $g){
    $dia=strtolower($g['dia_semana']);
    $guardias_profesor[$dia][$g['tramo_id']]=$g;
}

// --- Obtener ausencias
$ausencias_actuales = [];
$stmt = $pdo->prepare("SELECT * FROM ausencias WHERE profesor_id=? AND fecha BETWEEN ? AND ?");
$stmt->execute([$profesor_id,$fechas_dia['lunes'],$fechas_dia['viernes']]);
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $a){
    $key=$a['tramo_id'].'_'.strtolower($a['dia_semana']);
    $ausencias_actuales[$key]=$a;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Horario y Ausencias</title>
<style>
table { border-collapse: collapse; width: 100%; }
td, th { border:1px solid #ccc; padding:5px; text-align:center; vertical-align:top; }
th { background:#007bff; color:#fff; }
.celda{min-height:50px; position:relative;}
.clase, .guardia{background:#28a745; color:#fff; padding:4px; border-radius:5px; cursor:grab; margin:2px;}
.guardia{background:#ffc107; color:#000;}
.celda.hover{background:#ffeeba;}
input.fecha, input.aula, textarea{width:100%; box-sizing:border-box;}
</style>
</head>
<body>
<h2>Horario y Ausencias: <?=htmlspecialchars($profesor['nombre'])?></h2>

<form method="get">
<label>Fecha cualquiera de la semana:</label>
<input type="date" name="fecha" value="<?=htmlspecialchars($fecha_seleccionada)?>" onchange="this.form.submit()">
</form>

<table>
<tr>
<th>Tramo</th>
<?php foreach($dias as $d): ?><th><?=ucfirst($d)?><br><input type="checkbox" class="dia_completo" data-dia="<?=$d?>">Todo el día</th><?php endforeach; ?>
</tr>
<?php
foreach($cuadrante as $dia_nombre=>$tramos_dia):
    foreach($tramos_dia as $tramo_id=>$t):
?>
<tr>
<td><?=substr($t['hora_inicio'],0,5).' - '.substr($t['hora_fin'],0,5)?></td>
<?php foreach($dias as $d):
    $clase=$horarios_profesor[$d][$tramo_id]??null;
    $guardia=$guardias_profesor[$d][$tramo_id]??null;
    $key=$tramo_id.'_'.$d;
?>
<td class="celda" data-dia="<?=$d?>" data-tramo="<?=$tramo_id?>">
<?php if($clase): ?>
<div class="clase" draggable="true" data-dia="<?=$d?>" data-tramo="<?=$tramo_id?>">
<strong><?=htmlspecialchars($clase['asignatura'])?></strong><br>
<?=htmlspecialchars($clase['grupo'])?><br>
<?=htmlspecialchars($clase['aula'])?>
</div>
<?php endif; ?>
<?php if($guardia): ?>
<div class="guardia" draggable="true" data-dia="<?=$d?>" data-tramo="<?=$tramo_id?>">Guardia</div>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php
    endforeach;
endforeach;
?>
</table>

<script>
let dragged=null;
document.querySelectorAll('.clase, .guardia').forEach(el=>{
    el.addEventListener('dragstart', e=>{ dragged={dia:el.dataset.dia,tramo:el.dataset.tramo,tipo:el.classList.contains('guardia')?'Guardia':'Docencia'}; });
});

document.querySelectorAll('.celda').forEach(c=>{
    c.addEventListener('dragover', e=>{ e.preventDefault(); c.classList.add('hover'); });
    c.addEventListener('dragleave', e=>{ c.classList.remove('hover'); });
    c.addEventListener('drop', e=>{
        e.preventDefault(); c.classList.remove('hover');
        if(!dragged) return;
        let dia_dest=c.dataset.dia, tramo_dest=c.dataset.tramo;
        if(dia_dest===dragged.dia && tramo_dest===dragged.tramo) return;
        let form=new URLSearchParams();
        form.append('ajax',1);
        form.append('profesor_id','<?=$profesor_id?>');
        form.append('tipo',dragged.tipo);
        form.append('dia_origen',dragged.dia);
        form.append('tramo_origen',dragged.tramo);
        form.append('dia_destino',dia_dest);
        form.append('tramo_destino',tramo_dest);
        fetch('ausencias_horario.php',{method:'POST',body:form}).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
    });
});

// Marcar día completo
document.querySelectorAll('.dia_completo').forEach(ch=>{
    ch.addEventListener('change', e=>{
        let dia=e.target.dataset.dia;
        document.querySelectorAll(`.celda[data-dia='${dia}'] input[type=checkbox]`).forEach(cb=>cb.checked=e.target.checked);
    });
});
</script>
</body>
</html>
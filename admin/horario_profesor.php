<?php
require_once __DIR__ . '/includes/db.php';

$dias = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes'
];

$profesor_id = $_GET['profesor_id'] ?? null;
if (!$profesor_id) die("Profesor no especificado");
depurar("Mostrando horario para profesor_id=".$profesor_id);

// =========================
// AJAX: mover clase o guardia
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {

    $profesor_id    = intval($_POST['profesor_id']);
    $tramo_origen   = intval($_POST['tramo_origen']);
    $tramo_destino  = intval($_POST['tramo_destino']);
    $tipo           = $_POST['tipo'] ?? 'docencia';
  
    if($tipo === 'docencia') {
        $stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id=? AND tramo_horario=? AND tipo='Docencia'");
        $stmt->execute([$profesor_id, $tramo_origen]);
        $clase = $stmt->fetch(PDO::FETCH_ASSOC);
        if($clase){
            // Borrar origen y destino
            $pdo->prepare("DELETE FROM horarios WHERE profesor_id=? AND tramo_horario=?")->execute([$profesor_id, $tramo_origen]);
            $pdo->prepare("DELETE FROM horarios WHERE profesor_id=? AND tramo_horario=?")->execute([$profesor_id, $tramo_destino]);
            // Insertar en destino
            $pdo->prepare("INSERT INTO horarios (profesor_id,dia_semana,tramo_horario,asignatura,grupo,aula,tipo,tramos) VALUES (?,?,?,?,?,?, 'Docencia',?)")
                ->execute([$profesor_id,$clase['dia_semana'],$tramo_destino,$clase['asignatura'],$clase['grupo'],$clase['aula'],$clase['tramos']]);
        }
    } elseif($tipo === 'guardia') {
        depurar("Intentando mover guardia del tramo " . $tramo_origen . " al " . $tramo_destino);
        $stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id=? AND tramo_horario=? AND tipo='Guardias'");
        $stmt->execute([$profesor_id, $tramo_origen]);
        $guardia = $stmt->fetch(PDO::FETCH_ASSOC);
        if($guardia){
            depurar("Moviendo guardia de tramo " . $tramo_origen . " a " . $tramo_destino);
            $pdo->prepare("DELETE FROM horarios WHERE profesor_id=? AND tramo_horario=?")->execute([$profesor_id, $tramo_origen]);
            $pdo->prepare("DELETE FROM horarios WHERE profesor_id=? AND tramo_horario=?")->execute([$profesor_id, $tramo_destino]);
            // Insertar en destino
            $pdo->prepare("INSERT INTO horarios (profesor_id,dia_semana,tramo_horario,asignatura,grupo,aula,tipo,tramos) VALUES (?,?,?,?,?,?, 'Guardias',?)")
                ->execute([$profesor_id,1+($tramo_destino/6),$tramo_destino,"","","",""]);
        }
    }

    echo json_encode(['ok'=>true]);
    exit;
}

// =========================
// PROFESOR
// =========================
$stmt = $pdo->prepare("SELECT nombre FROM profesores WHERE id=?");
$stmt->execute([$profesor_id]);
$profesor = $stmt->fetch();
if (!$profesor) die("Profesor no encontrado");

// =========================
// TRAMOS (solo necesitamos el número de tramos por día)
// =========================
$stmt = $pdo->query("SELECT * FROM tramos WHERE dia_semana='Lunes' ORDER BY hora_inicio");
$tramos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================
// HORARIOS
// =========================
$horarios = [];
$stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id=? AND tipo='Docencia'");
$stmt->execute([$profesor_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
    $horarios[$h['tramo_horario']] = $h; // clave = tramo_id
}

// =========================
// GUARDIAS
// =========================
$guardias = [];
$stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id=? AND tipo='Guardias'");
$stmt->execute([$profesor_id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $g) {
    $guardias[$g['tramo_horario']] = $g; // clave = tramo_id
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
table { border-collapse: collapse; width:100%; }
td, th { border:1px solid #ccc; padding:5px; text-align:center; vertical-align:top; }
th { background:#007bff; color:white; }
.celda { min-height:50px; position:relative; }
.clase { background:#28a745; color:white; padding:5px; border-radius:6px; cursor:grab; margin-bottom:2px; }
.guardia { background:#ffc107; color:#000; padding:4px; border-radius:4px; cursor:grab; margin-bottom:2px; }
.celda.hover { background:#ffeeba; }
</style>
</head>
<body>

<h2>Horario de <?= htmlspecialchars($profesor['nombre']) ?></h2>

<table>
<tr>
<th>Hora / Tramo</th>
<?php foreach($dias as $d): ?><th><?= $d ?></th><?php endforeach; ?>
</tr>

<?php foreach($tramos as $index => $t): 
    $tramo_num = $index + 1;
?>
<tr>
<td><?= $t['hora_inicio'] ?> - <?= $t['hora_fin'] ?><br><?= htmlspecialchars($t['descripcion']) ?></td>

<?php foreach($dias as $dia_num => $d):
    // Calcular tramo_id según tu sistema: dia_offset * tramos_por_dia + index
    $tramos_por_dia = count($tramos);
    $tramo_id = ($dia_num-1)*$tramos_por_dia + $tramo_num;

    $h = $horarios[$tramo_id] ?? null;
    $g = $guardias[$tramo_id] ?? null;
?>
<td class="celda" data-dia="<?= $dia_num ?>" data-tramo="<?= $tramo_id ?>">

<?php if($h): ?>
<div class="clase" draggable="true" data-tipo="docencia" data-dia="<?= $dia_num ?>" data-tramo="<?= $tramo_id ?>">
<strong><?= htmlspecialchars($h['asignatura']) ?></strong><br>
<?= htmlspecialchars($h['grupo']) ?><br>
<?= htmlspecialchars($h['aula']) ?>
</div>
<?php endif; ?>

<?php if($g): ?>
<div class="guardia" draggable="true" data-tipo="guardia" data-dia="<?= $dia_num ?>" data-tramo="<?= $tramo_id ?>">
Guardia
</div>
<?php endif; ?>

</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>

<script>
let dragged = null;

document.querySelectorAll('.clase, .guardia').forEach(el=>{
    el.addEventListener('dragstart', e=>{
        dragged = {
            tipo: el.dataset.tipo,
            dia: el.dataset.dia,
            tramo: el.dataset.tramo
        };
    });
});

document.querySelectorAll('.celda').forEach(cell=>{
    cell.addEventListener('dragover', e=>{ e.preventDefault(); cell.classList.add('hover'); });
    cell.addEventListener('dragleave', ()=>cell.classList.remove('hover'));
    cell.addEventListener('drop', e=>{
        e.preventDefault(); cell.classList.remove('hover');
        if(!dragged) return;
        let tramo_destino = cell.dataset.tramo;
        if(tramo_destino==dragged.tramo) return;

        fetch('<?= $_SERVER['PHP_SELF'] ?>?profesor_id=<?= $profesor_id ?>',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({
                ajax:1,
                tipo: dragged.tipo,
                profesor_id: <?= $profesor_id ?>,
                tramo_origen: dragged.tramo,
                tramo_destino: tramo_destino
            })
        }).then(res=>res.json()).then(data=>{
            if(data.ok) location.reload();
        });
    });
});
</script>
<a href="dashboard.php">⬅ Volver</a>
</body>
</html>
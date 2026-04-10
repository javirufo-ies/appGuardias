<?php
require_once __DIR__ . '/includes/db.php';

$profesor_id = $_GET['profesor_id'] ?? 0;

/* TRAMOS */
$tramos = $pdo->query("SELECT * FROM tramos ORDER BY id")->fetchAll();

/* HORARIOS */
$stmt = $pdo->prepare("SELECT * FROM horarios WHERE profesor_id = ?");
$stmt->execute([$profesor_id]);
$rows = $stmt->fetchAll();

/* INDEXADO */
$grid = [];
foreach ($rows as $r) {
    $grid[$r['tramo_horario']][$r['dia_semana']] = $r;
}

/* DÍAS */
$dias = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Horario Profesor</title>

<style>
.horario-grid {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial;
}

.horario-grid th,
.horario-grid td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: center;
    vertical-align: top;
}

.tramo {
    background: #f2f2f2;
    font-weight: bold;
    width: 120px;
}

.celda {
    height: 80px;
}

.docencia {
    background: #d9ecff;
    padding: 4px;
    border-radius: 4px;
}

.guardia {
    background: #ffe0e0;
    padding: 4px;
    font-weight: bold;
    border-radius: 4px;
}

button {
    margin-top: 3px;
    cursor: pointer;
}
</style>
</head>

<body>
<form method="GET" id="selectorProfesor">
    <label>Profesor:</label>

    <select name="profesor_id" onchange="this.form.submit()">
        <option value="">-- Selecciona --</option>

        <?php foreach ($profesores as $p): ?>
            <option value="<?= $p['id'] ?>"
                <?= ($profesor_id == $p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?>
            </option>
        <?php endforeach; ?>

    </select>
</form>
<?php
if (!$profesor_id) {
    echo "<p>Selecciona un profesor para ver su horario</p>";
    exit;
}
?>
<h2>Horario del profesor</h2>

<table class="horario-grid">

<thead>
<tr>
    <th>Tramo</th>
    <?php foreach ($dias as $d): ?>
        <th><?= $d ?></th>
    <?php endforeach; ?>
</tr>
</thead>

<tbody>

<?php foreach ($tramos as $t): ?>
<tr>

    <td class="tramo">
        <?= $t['hora_inicio'] ?> - <?= $t['hora_fin'] ?>
    </td>

    <?php foreach ($dias as $idDia => $nombre): ?>

        <?php $item = $grid[$t['id']][$idDia] ?? null; ?>

        <td class="celda">

            <?php if ($item): ?>

                <?php if ($item['tipo'] === 'Guardia'): ?>
                    <div class="guardia">🛡 Guardia</div>
                <?php else: ?>
                    <div class="docencia">
                        <b><?= htmlspecialchars($item['asignatura']) ?></b><br>
                        <?= htmlspecialchars($item['grupo']) ?><br>
                        Aula <?= htmlspecialchars($item['aula']) ?>
                    </div>
                <?php endif; ?>

                <button onclick="editar(<?= $item['id'] ?>)">✏️</button>
                <button onclick="borrar(<?= $item['id'] ?>)">🗑</button>

            <?php else: ?>

                <button onclick="crear(<?= $idDia ?>, <?= $t['id'] ?>)">➕</button>

            <?php endif; ?>

        </td>

    <?php endforeach; ?>

</tr>
<?php endforeach; ?>

</tbody>
</table>


<!-- MODAL -->
<div id="modal" style="display:none; position:fixed; top:10%; left:30%; width:40%; background:#fff; border:1px solid #ccc; padding:20px; z-index:999;">

<h3 id="tituloModal"></h3>

<form id="formHorario">

<input type="hidden" name="id" id="id">
<input type="hidden" name="profesor_id" value="<?= $profesor_id ?>">

<label>Tipo</label><br>
<select name="tipo" id="tipo" onchange="toggleCampos()">
    <option value="Docencia">Docencia</option>
    <option value="Guardia">Guardia</option>
</select>

<br><br>

<label>Asignatura</label><br>
<input type="text" name="asignatura" id="asignatura">

<br><br>

<label>Grupo</label><br>
<input type="text" name="grupo" id="grupo">

<br><br>

<label>Aula</label><br>
<input type="text" name="aula" id="aula">

<br><br>

<input type="hidden" name="dia_semana" id="dia_semana">
<input type="hidden" name="tramo_horario" id="tramo_horario">

<button type="submit">Guardar</button>
<button type="button" onclick="cerrar()">Cerrar</button>

</form>

</div>


<script>

function abrir() {
    document.getElementById('modal').style.display = 'block';
}

function cerrar() {
    document.getElementById('modal').style.display = 'none';
}

function toggleCampos() {
    const tipo = document.getElementById('tipo').value;

    const asignatura = document.getElementById('asignatura');
    const grupo = document.getElementById('grupo');
    const aula = document.getElementById('aula');

    if (tipo === 'Guardia') {
        asignatura.value = '';
        grupo.value = '';
        aula.value = '';

        asignatura.disabled = true;
        grupo.disabled = true;
        aula.disabled = true;
    } else {
        asignatura.disabled = false;
        grupo.disabled = false;
        aula.disabled = false;
    }
}

/* CREAR */
function crear(dia, tramo) {
    abrir();
    document.getElementById('tituloModal').innerText = "Crear";

    document.getElementById('id').value = "";
    document.getElementById('dia_semana').value = dia;
    document.getElementById('tramo_horario').value = tramo;

    document.getElementById('tipo').value = "Docencia";

    document.getElementById('asignatura').value = "";
    document.getElementById('grupo').value = "";
    document.getElementById('aula').value = "";

    toggleCampos();
}

/* EDITAR */
function editar(id) {
    fetch('horario_api.php?action=get&id=' + id)
        .then(r => r.json())
        .then(data => {

            abrir();
            document.getElementById('tituloModal').innerText = "Editar";

            document.getElementById('id').value = data.id;
            document.getElementById('tipo').value = data.tipo;

            document.getElementById('asignatura').value = data.asignatura || '';
            document.getElementById('grupo').value = data.grupo || '';
            document.getElementById('aula').value = data.aula || '';

            document.getElementById('dia_semana').value = data.dia_semana;
            document.getElementById('tramo_horario').value = data.tramo_horario;

            toggleCampos();
        });
}

/* BORRAR */
function borrar(id) {
    if (!confirm("¿Eliminar registro?")) return;

    fetch('horario_api.php?action=delete&id=' + id)
        .then(() => location.reload());
}

/* GUARDAR */
document.getElementById('formHorario').addEventListener('submit', function(e) {
    e.preventDefault();

    fetch('horario_api.php?action=save', {
        method: 'POST',
        body: new FormData(this)
    }).then(() => location.reload());
});

</script>

</body>
</html>
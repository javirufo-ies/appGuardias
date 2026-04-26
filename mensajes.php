<?php
require_once __DIR__ . '/includes/db.php';
$hoy = date('Y-m-d');

// Mensajes activos
$stmt = $pdo->prepare("SELECT * FROM mensajes WHERE fecha_inicio <= ? AND fecha_fin >= ? ORDER BY fecha_inicio ASC");
$stmt->execute([$hoy, $hoy]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Eventos calendario esta semana
$inicio = (new DateTime('monday this week'))->setTime(0, 0);
$fin = (new DateTime('sunday this week'))->setTime(23, 59, 59);

$stmt = $pdo->prepare("
SELECT date(inicio) as inicio, descripcion FROM eventos_calendario
WHERE inicio BETWEEN ? AND ?
ORDER BY inicio
");

$stmt->execute([
    $inicio->format('Y-m-d'),
    $fin->format('Y-m-d')
]);

$eventosSemana = $stmt->fetchAll(PDO::FETCH_ASSOC);
$agrupados = [];
foreach ($eventosSemana as $ev) {
    $fecha = $ev['inicio'];
    $agrupados[$fecha][] = $ev;
}



?>
<div class="panel-inferior">
    <div id="mensajes" class="mensajes">
        <?php foreach ($mensajes as $m): ?>
            <div class="mensaje">
                <div class="imagen">
                    <?php if ($m['imagen']): ?>
                        <img src="uploads/mensajes/<?= $m['imagen'] ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="texto-mensaje">
                    <p><?= htmlspecialchars($m['texto']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>    
    </div>
    <div id="actividades" class="actividades">
        <h3>Actividades esta semana</h3>
        <?php foreach ($agrupados as $fecha => $eventos): ?>
            <span class="texto-fecha">
                <?= $fecha . ":" ?>
            </span>
            <span class="texto-mensaje">
                <?php foreach ($eventos as $ev): ?>
                    <?= htmlspecialchars($ev['descripcion']) ?>
                <?php endforeach; ?>
            </span>
            <br>
        <?php endforeach; ?>

<!-- Contenido duplicado para hacer scroll -->
         <?php foreach ($agrupados as $fecha => $eventos): ?>
            <span class="texto-fecha">
                <?= $fecha . ":" ?>
            </span>
            <span class="texto-mensaje">
                <?php foreach ($eventos as $ev): ?>
                    <?= htmlspecialchars($ev['descripcion']) ?>
                <?php endforeach; ?>
            </span>
            <br>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // --- LÓGICA DEL CARRUSEL ---
    let index = 0;
    const slides = document.querySelectorAll('.slide');
    const total = slides.length;
    const contador = document.getElementById('contador');

    function mostrarSlide(i) {
        if (total === 0) return;
        slides.forEach(slide => slide.style.display = 'none');
        slides[i].style.display = 'flex';
        //if(contador) contador.textContent = (i + 1) + " / " + total;
    }

    if (total > 0) {
        mostrarSlide(index);
        setInterval(() => {
            index = (index + 1) % total;
            mostrarSlide(index);
        }, 3000);
    }
</script>
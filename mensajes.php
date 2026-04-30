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
<style>
.swiper,
.swiper-wrapper,
.swiper-slide {
    height: 100%;
}
</style>
<div id="mensajes" class="flex flex-col h-full overflow-hidden">

    <!-- TÍTULO FIJO -->
    <div class="shrink-0 bg-blue-700 text-white font-semibold text-center py-1 text-[clamp(12px,1.2vw,18px)]">
        Mensajes
    </div>

    <!-- CARRUSEL -->
    <div class="flex-1 min-h-0 overflow-hidden">

        <div class="swiper mensajes-swiper h-full w-full">
            <div class="swiper-wrapper h-full">

                <?php foreach ($mensajes as $m): ?>
                    <div class="swiper-slide h-full">

                        <div class="grid grid-cols-[25%_1fr] h-full border-r-2 border-[#CE1BF4]">

                            <div class="flex items-stretch justify-center h-full">
                                <?php if ($m['imagen']): ?>
                                    <img src="uploads/mensajes/<?= $m['imagen'] ?>"
                                         class="w-[60%]] h-[60%] object-contain object-center block">
                                <?php endif; ?>
                            </div>

                            <div class="flex h-full  p-2 overflow-hidden">
                                    <p class="text-[clamp(12px,1vw,16px)]">
                                    <?= htmlspecialchars($m['texto']) ?>
                                </p>
                            </div>

                        </div>

                    </div>
                <?php endforeach; ?>

            </div>
        </div>

    </div>
</div>
<div id="actividades" class="flex flex-col h-full overflow-hidden">

    <!-- TÍTULO FIJO -->
    <div class="shrink-0 bg-green-700 text-white font-semibold text-center py-1 text-[clamp(12px,1.2vw,18px)]">
        Actividades próximas
    </div>

    <!-- CARRUSEL / CONTENIDO -->
    <div class="flex-1 min-h-0 overflow-hidden">

        <div class="swiper actividades-swiper h-full w-full">
            <div class="swiper-wrapper h-full">

                <?php foreach ($agrupados as $fecha => $eventos): ?>
                    <div class="swiper-slide h-full overflow-auto p-2">

                        <div class="font-semibold text-[clamp(12px,1.1vw,18px)]">
                            <?= $fecha ?>
                        </div>

                        <?php foreach ($eventos as $ev): ?>
                            <div class="text-[clamp(12px,1vw,16px)]">
                                <?= htmlspecialchars($ev['descripcion']) ?>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endforeach; ?>

            </div>
        </div>

    </div>
</div>
<script>
const mensajesSwiper = new Swiper('.mensajes-swiper', {
    loop: true,
    autoplay: {
        delay: 5000,
        disableOnInteraction: false
    },
    effect: 'fade',
    fadeEffect: { crossFade: true }
});

const actividadesSwiper = new Swiper('.actividades-swiper', {
    loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false
    },
    effect: 'fade',
    fadeEffect: { crossFade: true }
});
</script>
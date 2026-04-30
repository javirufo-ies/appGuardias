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
<div class="swiper mensajes-swiper h-full w-full">

    <div class="swiper-wrapper " >

        <?php foreach ($mensajes as $m): ?>
            <div class="swiper-slide ">

                <div class="grid grid-cols-[25%_1fr] h-full border-r-2 border-[#CE1BF4]">

                    <div class="flex items-center justify-center p-2">
                        <?php if ($m['imagen']): ?>
                            <img src="uploads/mensajes/<?= $m['imagen'] ?>"
                                 class="max-w-full max-h-full object-contain">
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center p-2">
                        <p class="text-[clamp(12px,1.2vw,24px)]">
                            <?= htmlspecialchars($m['texto']) ?>
                        </p>
                    </div>

                </div>

            </div>
        <?php endforeach; ?>

    </div>

</div>
<div class="swiper actividades-swiper h-full w-full">

    <div class="swiper-wrapper">

        <?php foreach ($agrupados as $fecha => $eventos): ?>
            <div class="swiper-slide p-4">

                <h3 class="text-[clamp(14px,1.5vw,28px)] font-bold mb-2">
                    <?= $fecha ?>
                </h3>

                <div class="text-[clamp(12px,1.1vw,22px)]">

                    <?php foreach ($eventos as $ev): ?>
                        <?= htmlspecialchars($ev['descripcion']) ?><br>
                    <?php endforeach; ?>

                </div>

            </div>
        <?php endforeach; ?>

    </div>

</div>
<script>
const mensajesSwiper = new Swiper('.mensajes-swiper', {
    loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false
    },
    effect: 'fade',
    fadeEffect: { crossFade: true }
});

const actividadesSwiper = new Swiper('.actividades-swiper', {
    loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false
    },
    effect: 'fade',
    fadeEffect: { crossFade: true }
});
</script>
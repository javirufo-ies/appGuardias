<?php
require_once __DIR__ . '/admin/includes/db.php';
$hoy = date('Y-m-d');

// Mensajes activos
$stmt = $pdo->prepare("SELECT * FROM mensajes WHERE fecha_inicio <= ? AND fecha_fin >= ? ORDER BY fecha_inicio ASC");
$stmt->execute([$hoy, $hoy]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);



// --- CALENDARIO EXTRAESCOLARES
function parseICS($ics) {
    $eventos = [];
    $lineas = explode("\n", $ics);

    $evento = [];

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        if ($linea == "BEGIN:VEVENT") {
            $evento = [];
        }

        if (strpos($linea, "SUMMARY:") === 0) {
            $evento['titulo'] = substr($linea, 8);
        }

        if (strpos($linea, "DTSTART:") === 0) {
            $evento['inicio'] = substr($linea, 8);
        }

        if (strpos($linea, "DTEND:") === 0) {
            $evento['fin'] = substr($linea, 6);
        }

        if ($linea == "END:VEVENT") {
            $eventos[] = $evento;
        }
    }

    return $eventos;
}


function convertirFecha($fechaICS) {
    return DateTime::createFromFormat('Ymd\THis\Z', $fechaICS);
}


$url = "https://calendar.google.com/calendar/ical/c_caea1e0b1c3d1f275836e1e0acb4c80891d4967f32bac019dfabde4bf43bef63%40group.calendar.google.com/public/basic.ics";

$ics = file_get_contents($url);

if ($ics === false) {
    die("Error al obtener el calendario");
}

$eventos = parseICS($ics);

$inicioSemana = new DateTime('monday this week');
$finSemana = new DateTime('sunday this week 23:59:59');

$eventosSemana = [];

foreach ($eventos as $ev) {
    $inicio = convertirFecha($ev['inicio']);

    if ($inicio >= $inicioSemana && $inicio <= $finSemana) {
        $dia = $inicio->format('Y-m-d');
        $eventosSemana[$dia][] = [
            'titulo' => $ev['titulo'],
            'hora' => $inicio->format('H:i')
        ];
    }
}

var_dump($eventosSemana);
?>

<div id="mensajes-contenido">        
        <?php foreach($mensajes as $m): ?>
            <div class="slide">
                <div class="slide-content">
                    <?php if($m['imagen']): ?>
                        <img src="uploads/mensajes/<?= $m['imagen'] ?>" alt="">
                    <?php endif; ?>
                    <div class="slide-text">
                        <p><?= htmlspecialchars($m['texto']) ?></p>                        
                    </div>
                </div>
            </div>
        <?php endforeach; ?>    
</div>

<script>
// --- LÓGICA DEL CARRUSEL ---
let index = 0;
const slides = document.querySelectorAll('.slide');
const total = slides.length;
const contador = document.getElementById('contador');

function mostrarSlide(i) {
    if(total === 0) return;
    slides.forEach(slide => slide.style.display = 'none');
    slides[i].style.display = 'flex';
    //if(contador) contador.textContent = (i + 1) + " / " + total;
}

if(total > 0){
    mostrarSlide(index);
    setInterval(() => {
        index = (index + 1) % total;
        mostrarSlide(index);
    }, 3000);
}



</script>
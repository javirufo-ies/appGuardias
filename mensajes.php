<?php
require_once __DIR__ . '/includes/db.php';
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

        if (strpos($linea, "SUMMARY") === 0) {
            $evento['titulo'] = explode(":", $linea, 2)[1] ?? '';
        }

        if (strpos($linea, "DTSTART") === 0) {
            $evento['inicio'] = explode(":", $linea, 2)[1] ?? '';
        }

        if (strpos($linea, "DTEND") === 0) {
            $evento['fin'] = explode(":", $linea, 2)[1] ?? '';
        }

        if ($linea == "END:VEVENT") {
            if (!empty($evento)) {
                $eventos[] = $evento;
            }
        }
    }

    return $eventos;
}


function convertirFecha($fechaICS) {

    // Evento de día completo
    if (strlen($fechaICS) == 8) {
        return DateTime::createFromFormat('Ymd', $fechaICS);
    }

    // Evento con hora
    if (strpos($fechaICS, 'T') !== false) {
        return DateTime::createFromFormat('Ymd\THis', str_replace('Z','',$fechaICS));
    }

    return null;
}


$url = "https://calendar.google.com/calendar/embed?src=c_caea1e0b1c3d1f275836e1e0acb4c80891d4967f32bac019dfabde4bf43bef63%40group.calendar.google.com&ctz=Europe%2FMadrid";
function obtenerICS($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}


$ics = obtenerICS($url);
file_put_contents(__DIR__ . "/debug_ics.txt", $ics);error_reporting(E_ALL);
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
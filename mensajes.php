<?php
require_once __DIR__ . '/admin/includes/db.php';
$hoy = date('Y-m-d');

// Mensajes activos
$stmt = $pdo->prepare("SELECT * FROM mensajes WHERE fecha_inicio <= ? AND fecha_fin >= ? ORDER BY fecha_inicio ASC");
$stmt->execute([$hoy, $hoy]);
$mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

// --- LÓGICA DEL RELOJ ---
/*
function actualizarReloj() {
    const ahora = new Date();
    const h = String(ahora.getHours()).padStart(2, '0');
    const m = String(ahora.getMinutes()).padStart(2, '0');
    const s = String(ahora.getSeconds()).padStart(2, '0');
    document.getElementById('reloj-digital').textContent = `${h}:${m}:${s}`;
}
setInterval(actualizarReloj, 1000);
actualizarReloj();
*/
</script>
<?php
$dias = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
$dia_actual = strtolower($dias[date('N') - 1]);

if (!in_array($dia_actual, ['lunes','martes','miércoles','jueves','viernes'])) {
    echo "<h2 style='text-align:center; padding:20px;'>No hay guardias programadas para hoy.</h2>";
    return;
}
?>
<script>

    function actualizarReloj() {
        
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2,'0');
        const minutos = String(ahora.getMinutes()).padStart(2,'0');
        const segundos = String(ahora.getSeconds()).padStart(2,'0');
        reloj = document.getElementById('reloj');
        if (reloj) {
            reloj.innerHTML = `${horas}:${minutos}:${segundos}`;
        }
        
    }
    setInterval(actualizarReloj, 1000);
    actualizarReloj();

</script>

<div id="titulocuadrante"
     class="grid grid-cols-[auto_1fr_auto]
            items-center
            h-[clamp(40px,6vh,100px)]
            px-[clamp(4px,1vw,16px)]
            bg-white
            text-[#CE1BF4]
            overflow-hidden">

    <!-- LOGOS -->
    <div class="flex items-center
                gap-[clamp(2px,0.5vw,10px)]
                overflow-hidden">

        <img src="/images/logo.png"
             class="h-[clamp(22px,3vh,50px)] w-auto max-w-[180px] object-contain">

        <img src="/images/logoies.png"
             class="h-[clamp(22px,3vh,50px)] w-auto max-w-[180px] object-contain">
    </div>

    <!-- TITULO -->
    <div id="titulo-centro"
         class="min-w-0
                text-center
                truncate
                font-bold
                text-[clamp(12px,2vw,30px)]">

        Guardias: <?= ucfirst($dia_actual) ?> (<?= date('d/m/Y') ?>)

    </div>

    <!-- RELOJ -->
    <div id="reloj"
         class="justify-self-end
                whitespace-nowrap
                text-[clamp(10px,1.8vw,26px)]">
    </div>

</div>
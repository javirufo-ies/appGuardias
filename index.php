<html class="h-full" lang="es">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta charset="UTF-8">    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuadrante de Guardias</title>
    <!-- Enlace al único fichero de estilos -->
    <!--<link rel="stylesheet" href="assets/css/estilos.css?v=1.0">    -->


<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<!-- Swiper -->
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
/>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>




<style>
    html, body {
    height: 100%;
    margin: 0;
}
</style>
</head>

<body class="h-full m-0 p-0 overflow-hidden ">
    <div id="pantalla" class="grid h-screen grid-rows-[8fr_2fr] overflow-hidden">
        <div id="cuadrante" class="grid grid-rows-[auto_1fr] h-full overflow-hidden">
            <?php include __DIR__ . '/cuadrante.php'; ?>
        </div>
        <div id="panel-inferior" class="grid grid-cols-2 overflow-hidden border-t-2 border-[#CE1BF4]">
            <?php include __DIR__ . '/mensajes.php'; ?>
        </div>
    </div>


<script>
async function recargarCuadrante() {
    try {
        const res = await fetch('cuadrante.php');
        const html = await res.text();
        document.getElementById('cuadrante').innerHTML = html;

    } catch (e) {
        console.error('Error recargando cuadrante', e);
    }
}

// cada 1 minuto
setInterval(recargarCuadrante, 60000);

// carga inicial opcional
recargarCuadrante();
</script>
</body>

</html>

<?php
// index.php
?>
<html lang="es">
<head>
    <meta charset="UTF-8">    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuadrante de Guardias</title>
    <!-- Enlace al único fichero de estilos -->
    <link rel="stylesheet" href="assets/css/estilosTest.css?v=1.0">    
    <script>

        function ajustarTabla() {
            // Buscamos las filas reales que imprimió el PHP
            const filas = document.querySelectorAll(".tabla-guardias tbody tr");
            
            if (filas.length > 0) {
                document.documentElement.style.setProperty("--num-filas", filas.length);
            }
        }

    function ajustarMensajes() {
        const contenedor = document.getElementById("mensajes-contenido");
        const maxHeight = document.getElementById("mensajes").clientHeight;
        let fontSize = parseFloat(window.getComputedStyle(contenedor).fontSize);

        while(contenedor.scrollHeight > maxHeight && fontSize > 8){
            fontSize -= 0.5;
            contenedor.style.fontSize = fontSize + "px";
        }
}


function esperarTablaYajustar() {
    const filas = document.querySelectorAll(".tabla-guardias tbody tr");

    if (filas.length > 0) {
        ajustarTabla();
        ajustarMensajes();
    } else {
        setTimeout(esperarTablaYajustar, 50);
    }
}
        window.onload = function() {            
            esperarTablaYajustar();            
            setTimeout(() => {
                location.reload();  // recarga toda la página
            }, 60000); // Refrescar cada 60 segundos            
            window.addEventListener("resize",ajustarTabla);
            window.addEventListener("resize", ajustarMensajes);        

        };

    </script>

</head>
<body>
    <div class="wrapper">
    <div id="principal">
        <?php include __DIR__ . '/cuadranteTest.php'; ?>        
    </div>

    <div id="mensajes">        
        <?php include __DIR__ . '/mensajesTest.php'; ?>
    </div>
    </div>
            
</body>
</html>

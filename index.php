<?php
// index.php
?>
<html lang="es">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1">
    <meta charset="UTF-8">    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuadrante de Guardias</title>
    <!-- Enlace al único fichero de estilos -->
    <link rel="stylesheet" href="assets/css/estilos.css?v=1.0">    
    <script>
function ajustarTabla(tabla, contenedor) {
    let min = 8;
    let max = 40;

    while (min <= max) {
        let mid = Math.floor((min + max) / 2);
        tabla.style.fontSize = mid + "px";

        const desborda =
            tabla.scrollHeight > contenedor.clientHeight ||
            tabla.scrollWidth > contenedor.clientWidth;

        if (desborda) {
            max = mid - 1;
        } else {
            min = mid + 1;
        }
    }

    tabla.style.fontSize = max + "px";

    // --- CENTRADO VERTICAL ÚLTIMA FILA ---
    const ultimaFila = tabla.querySelector("tr:last-child");
    if (ultimaFila) {
        ultimaFila.querySelectorAll("td").forEach(td => {
            td.style.display = "flex";
            td.style.alignItems = "center";     // vertical
            td.style.justifyContent = "center"; // horizontal (opcional)
        });
    }
}


/*
        function ajustarTabla() {
            // Buscamos las filas reales que imprimió el PHP
            const filas = document.querySelectorAll(".tabla-guardias tbody tr");
            
            if (filas.length > 0) {
                document.documentElement.style.setProperty("--num-filas", filas.length);
            }
        }
*/
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
           // esperarTablaYajustar();            
            setTimeout(() => {
                location.reload();  // recarga toda la página
            }, 60000); // Refrescar cada 60 segundos            
           // window.addEventListener("resize",ajustarTabla);
           // window.addEventListener("resize", ajustarMensajes);    
            const contenedor = document.getElementById("principal");            
            const tabla = document.getElementById('tabla-cuadrante');
            ajustarTabla(tabla, contenedor);
        };

    </script>

</head>
<body>
    <div class="wrapper">
    <div id="principal">
        <?php include __DIR__ . '/cuadrante.php'; ?>        
    </div>

    <div id="mensajes">        
        <?php include __DIR__ . '/mensajes.php'; ?>
    </div>
    </div>
            
</body>
</html>

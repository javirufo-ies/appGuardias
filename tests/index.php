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
    function obtenerEscalaBase() {
        const ancho = window.innerWidth;

        if (ancho >= 3840) return 1.6; // 4K
        if (ancho >= 2560) return 1.3; // QHD
        if (ancho >= 1920) return 1.0; // Full HD
        return 0.9; // inferior
    }

    function ajustarTabla(tabla, contenedor) {
        const escala = obtenerEscalaBase();

        let min = 10;
        let max = 80; // importante: subir el máximo para 4K

        let mejor = min;

        while (min <= max) {
            let mid = Math.floor((min + max) / 2);

            tabla.style.fontSize = (mid * escala) + "px";

            const desborda =
                tabla.scrollHeight > contenedor.clientHeight ||
                tabla.scrollWidth > contenedor.clientWidth;

            if (desborda) {
                max = mid - 1;
            } else {
                mejor = mid;
                min = mid + 1;
            }
        }

        tabla.style.fontSize = (mejor * escala) + "px";

        // --- fuerza que “rellene mejor” visualmente ---
        const altoContenedor = contenedor.clientHeight;
        const altoTabla = tabla.scrollHeight;

        if (altoTabla < altoContenedor * 0.95) {
            // si sobra espacio, intentamos subir ligeramente
            tabla.style.fontSize = (parseFloat(tabla.style.fontSize) * 1.05) + "px";
        }

        // centrado última fila
        const ultimaFila = tabla.querySelector("tr:last-child");
        if (ultimaFila) {
            ultimaFila.querySelectorAll("td").forEach(td => {
                td.style.display = "flex";
                td.style.alignItems = "center";
                td.style.justifyContent = "center";
            });
        }
        tabla.style.lineHeight = "1.1";
        tabla.style.letterSpacing = "-0.2px";
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

/*
    function esperarTablaYajustar() {
        const filas = document.querySelectorAll(".tabla-guardias tbody tr");

        if (filas.length > 0) {
            ajustarTabla();
            ajustarMensajes();
        } else {
            setTimeout(esperarTablaYajustar, 50);
        }
    }

*/
        window.onload = function() {            
//            esperarTablaYajustar();            
            setTimeout(() => {
                location.reload();  // recarga toda la página
            }, 30000); // Refrescar cada 30 segundos            
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

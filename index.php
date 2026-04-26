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


</head>
<body>
    <div class="pantalla">
    <div id="cuadrante" class="cuadrante">
        <?php include __DIR__ . '/cuadrante.php'; ?>        
    </div>

    <div id="panel-mensajes" class="panel-mensajes">        
        <?php include __DIR__ . '/mensajes.php'; ?>
    </div>
    </div>
            
</body>
</html>

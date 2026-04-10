<?php
session_start();
if (!isset($_SESSION['usuario'])) header('Location: login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
    <div class="sidebar">
        <h2>Admin</h2>
        <ul>
            <li><a href="dashboard.php?seccion=profesores">Profesores</a></li>            
            <li><a href="dashboard.php?seccion=tramos">Tramos de clases</a></li>
            <li><a href="dashboard.php?seccion=guardias">Tramos de guardias</a></li>
            <li><a href="dashboard.php?seccion=ausencias">Ausencias</a></li>            
            <li><a href="dashboard.php?seccion=mensajes">Mensajes y actividades</a></li>
            <li><a href="dashboard.php?seccion=usuarios">Usuarios</a></li>
            <li><a href="dashboard.php?seccion=importahorarios">Importar horarios</a></li>
            <li><a href="dashboard.php?seccion=informefaltas">Informe de faltas</a></li>
            <li><a href="dashboard.php?seccion=calendarioausencias">Calendario de ausencias</a></li>
            <li><a href="logout.php">Cerrar sesión</a></li>
        </ul>
    </div>

    <div class="content">
        <?php
        $seccion = $_GET['seccion'] ?? 'inicio';
        switch($seccion) {
            case 'profesores':
                include 'profesores.php';
                break;
            case 'tramos':
                include 'tramos.php';
                break;
            case 'guardias':
                include 'guardias.php';
                break;
            case 'ausencias':
                include 'ausencias.php';
                break;
            case 'ausencias_ant':
                include 'ausenciascopy.php';
                break;
            case 'mensajes':
                include 'mensajes.php';
                break;
            case 'usuarios':
                include 'usuarios.php';
                break;
            case 'importahorarios':                
                include 'importahorarios.php';
                break;
            case 'informefaltas':
                include 'informe_faltas.php';
                break;
            case 'calendarioausencias':
                include 'calendario_ausencias.php';
                break;
            default:
                echo "<h1>Bienvenido, " . htmlspecialchars($_SESSION['usuario']) . "</h1>";
                echo "<p>Selecciona una opción del menú para comenzar.</p>";
        }
        ?>
    </div>


</body>
</html>

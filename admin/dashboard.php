<?php
session_start();
if (!isset($_SESSION['usuario'])) header('Location: login.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">

<div class="layout">

    <!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="brand">
        <img src="../images//logo.png" alt="Logo">
        <span class="text">Admin</span>
    </div>

    <nav class="menu">

        <a href="dashboard.php?seccion=profesores">
            <i class="fa-solid fa-chalkboard-user"></i>
            <span class="text">Profesores</span>
        </a>

        <a href="dashboard.php?seccion=tramos">
            <i class="fa-solid fa-clock"></i>
            <span class="text">Tramos</span>
        </a>
<!--
        <a href="dashboard.php?seccion=guardias">
            <i class="fa-solid fa-shield-halved"></i>
            <span class="text">Guardias</span>
        </a>
-->
        <a href="dashboard.php?seccion=ausencias">
            <i class="fa-solid fa-user-xmark"></i>
            <span class="text">Ausencias</span>
        </a>

        <a href="dashboard.php?seccion=mensajes">
            <i class="fa-solid fa-comment"></i>
            <span class="text">Mensajes</span>
        </a>

        <a href="dashboard.php?seccion=usuarios">
            <i class="fa-solid fa-users"></i>
            <span class="text">Usuarios</span>
        </a>

        <a href="dashboard.php?seccion=informefaltas">
            <i class="fa-solid fa-chart-line"></i>
            <span class="text">Informe</span>
        </a>
        <a href="dashboard.php?seccion=calendarioausencias">
            <i class="fa-solid fa-calendar-days"></i>
            <span class="text">Calendario de Ausencias</span>
        </a>

        <a href="dashboard.php?seccion=importarhorarios">
            <i class="fa-solid fa-file-import"></i>
            <span class="text">Importar horarios</span>
        </a>        

        <a href="logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="text">Salir</span>
        </a>

    </nav>

    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </button>
<button class="theme-btn" onclick="toggleTheme()">
    <i class="fa-solid fa-moon"></i>
    <span class="text">Tema</span>
</button>
</aside>

    <!-- CONTENT -->
    <main class="content animate">

        <header class="topbar">
<!--            <h1>Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?></h1> -->


        </header>

        <section class="page">
            <?php
            $seccion = $_REQUEST['seccion'] ?? 'inicio';
            switch($seccion) {
                case 'profesores': include 'profesores.php'; break;
                case 'tramos': include 'tramos.php'; break;
                case 'guardias': include 'guardias.php'; break;
                case 'ausencias': include 'ausencias.php'; break;
                case 'ausencias_ant': include 'ausenciascopy.php'; break;
                case 'mensajes': include 'mensajes.php'; break;
                case 'usuarios': include 'usuarios.php'; break;
                case 'importarhorarios': include 'importahorarios.php'; break;
                case 'informefaltas': include 'informe_faltas.php'; break;
                case 'calendarioausencias': include 'calendario_ausencias.php'; break;
                default:
                   // echo "<h2>Panel de control</h2><p>Selecciona una opción del menú.</p>";
            }
            ?>
        </section>

    </main>

</div>

<script>
function toggleTheme(){
    document.body.classList.toggle('dark');
}

function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    sb.classList.toggle('collapsed');

    localStorage.setItem(
        'sidebar',
        sb.classList.contains('collapsed') ? '1' : '0'
    );
}

window.addEventListener('load', () => {
    const state = localStorage.getItem('sidebar');
    if (state === '1') {
        document.getElementById('sidebar').classList.add('collapsed');
    }
});

</script>

</body>
</html>

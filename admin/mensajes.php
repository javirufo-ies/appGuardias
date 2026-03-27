<?php
require_once __DIR__ . '/includes/db.php';

$mensaje = "";

// --- Carpeta de imágenes ---
$upload_dir = __DIR__ . "/../uploads/mensajes/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// --- Obtener imágenes ya existentes ---
$imagenes_existentes = glob($upload_dir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
$imagenes_existentes = array_map('basename', $imagenes_existentes);

// --- Guardar mensaje ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = trim($_POST['texto'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $imagen = null;

    // Si selecciona una imagen existente
    if (!empty($_POST['imagen_existente'])) {
        $imagen = $_POST['imagen_existente'];
    }

    // O si sube una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name'] != '') {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $imagen);
    }

    // Insertar en BD
    $stmt = $pdo->prepare("INSERT INTO mensajes (fecha_inicio, fecha_fin, texto, imagen) VALUES (?, ?, ?, ?)");
    $stmt->execute([$fecha_inicio, $fecha_fin, $texto, $imagen]);
    $mensaje = "✅ Mensaje añadido correctamente";
}

// --- Obtener todos los mensajes ---
$mensajes = $pdo->query("SELECT * FROM mensajes ORDER BY fecha_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mensajes y Actividades</title>
<link rel="stylesheet" href="../assets/css/estilos.css">
<style>
table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
th, td { border:1px solid #ccc; padding:6px; text-align:center; vertical-align: middle; }
th { background-color: #007bff; color:white; }
img { max-width: 80px; max-height: 80px; }
button { padding:6px 12px; margin:2px; cursor:pointer; }
.preview {
    display:block;
    margin-top:10px;
    max-width:250px;
    border:1px solid #ccc;
    border-radius:8px;
}
</style>
</head>
<body>

<h2>Mensajes y Actividades</h2>
<?php if($mensaje): ?><p style="color:green;font-weight:bold;"><?= $mensaje ?></p><?php endif; ?>

<h3>Añadir mensaje</h3>
<form method="post" enctype="multipart/form-data">
    <label>Fecha inicio:</label>
    <input type="date" name="fecha_inicio" required><br><br>

    <label>Fecha fin:</label>
    <input type="date" name="fecha_fin" required><br><br>

    <label>Texto:</label><br>
    <textarea name="texto" required style="width:100%;height:80px;"></textarea><br><br>

    <label>Usar imagen existente:</label><br>
    <select name="imagen_existente" id="imagen_existente">
        <option value="">-- Ninguna --</option>
        <?php foreach ($imagenes_existentes as $img): ?>
            <option value="<?= htmlspecialchars($img) ?>"><?= htmlspecialchars($img) ?></option>
        <?php endforeach; ?>
    </select>
    <img id="preview" class="preview" style="display:none;"><br>

    <label>O subir una nueva imagen:</label>
    <input type="file" name="imagen" accept="image/*"><br><br>

    <button type="submit">Guardar</button>
</form>

<h3>Mensajes existentes</h3>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Fecha inicio</th>
            <th>Fecha fin</th>
            <th>Texto</th>
            <th>Imagen</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($mensajes as $m): ?>
        <tr>
            <td><?= $m['id'] ?></td>
            <td><?= htmlspecialchars($m['fecha_inicio']) ?></td>
            <td><?= htmlspecialchars($m['fecha_fin']) ?></td>
            <td><?= nl2br(htmlspecialchars($m['texto'])) ?></td>
            <td><?= $m['imagen'] ? '<img src="../uploads/mensajes/'.htmlspecialchars($m['imagen']).'" alt="">' : '' ?></td>
            <td>
                <a href="borrar_mensaje.php?id=<?= $m['id'] ?>" onclick="return confirm('¿Seguro que quieres borrar este mensaje?')">Borrar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<a href="dashboard.php" class="btn-dashboard">⬅ Volver al Dashboard</a>

<script>
const select = document.getElementById('imagen_existente');
const preview = document.getElementById('preview');
select.addEventListener('change', () => {
    if (select.value) {
        preview.src = '../uploads/mensajes/' + select.value;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>

</body>
</html>

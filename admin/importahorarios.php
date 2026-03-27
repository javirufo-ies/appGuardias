<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Importar horarios y guardias desde CSV</h2>";

// Cache de profesores para no duplicar búsquedas
$profesores_cache = [];

// Obtener todos los días de la semana y tramos diarios para referencia
$dias_semana = [];
foreach($pdo->query("SELECT id, dia_numero FROM dias_semana") as $row){
    $dias_semana[$row['id']] = $row['dia_numero'];
}

$tramos_diarios = [];
foreach($pdo->query("SELECT id, num_tramo FROM tramos_diarios") as $row){
    $tramos_diarios[$row['num_tramo']] = $row['id'];
}

if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK){
    $file = $_FILES['csv_file']['tmp_name'];

    if(($handle = fopen($file, 'r')) !== false){
        $header = fgetcsv($handle, 1000, ';'); // Saltar encabezado

        while(($data = fgetcsv($handle, 1000, ';')) !== false){
            $nombre          = trim($data[0]);
            $dia_numero      = intval($data[1]); // corresponde a dia_numero en dias_semana
            $num_tramo       = intval($data[2]); // corresponde a num_tramo en tramos_diarios
            $asignatura      = trim($data[3]);
            $grupo           = trim($data[4]);
            $aula            = trim($data[5]);
            $tipo            = trim($data[6]);

            // --- Insertar profesor si no existe
            $nombre_key = mb_strtolower($nombre);
            if(!isset($profesores_cache[$nombre_key])){
                $stmt = $pdo->prepare("SELECT id FROM profesores WHERE LOWER(TRIM(nombre)) = ?");
                $stmt->execute([$nombre_key]);
                $profesor_id = $stmt->fetchColumn();
                if(!$profesor_id){
                    $stmt = $pdo->prepare("INSERT INTO profesores (nombre) VALUES (?)");
                    $stmt->execute([$nombre]);
                    $profesor_id = $pdo->lastInsertId();
                }
                $profesores_cache[$nombre_key] = $profesor_id;
            } else {
                $profesor_id = $profesores_cache[$nombre_key];
            }

            // --- Obtener id del tramo diario
            if(!isset($tramos_diarios[$num_tramo])){
                echo "❌ No se encontró el tramo {$num_tramo} para {$nombre}.<br>";
                continue;
            }
            $tramo_id = $tramos_diarios[$num_tramo];

            // --- Calcular tramo_horario (opcional: para usar como identificador único en horarios)
            $tramo_horario = ($dia_numero-1)*6 + $num_tramo;

            try{
                if(strtolower($tipo) === 'docencia' or strtolower($tipo) === 'guardias'){
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO horarios (profesor_id, dia_semana, tramo_horario, asignatura, grupo, aula, tipo)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_insert->execute([
                        $profesor_id,
                        $dia_numero,
                        $tramo_horario,
                        $asignatura,
                        $grupo,
                        $aula,
                        $tipo
                    ]);
                    echo "✅ Docencia insertada: {$nombre} - Día {$dia_numero}, Tramo {$num_tramo}.<br>";
                /*} elseif(strtolower($tipo) === 'guardias'){
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO guardias (profesor_id, tramo_id, dia_semana)
                        VALUES (?, ?, ?)
                    ");
                    $stmt_insert->execute([$profesor_id, $tramo_id, $dia_numero]);
                    echo "✅ Guardia insertada: {$nombre} - Día {$dia_numero}, Tramo {$num_tramo}.<br>";
                    */
                } else {
                    echo "⚠ Tipo desconocido '{$tipo}' para {$nombre}.<br>";
                }

            } catch(PDOException $e){
                echo "❌ Error al insertar {$nombre}: ".$e->getMessage()."<br>";
            }
        }

        fclose($handle);
        echo "<strong>Importación completada.</strong>";
    } else {
        echo "No se pudo abrir el archivo CSV.";
    }
} else {
    echo "No se subió ningún archivo o hubo un error en la subida.";
}
?>

<form method="post" enctype="multipart/form-data" action="">
    <label>Subir archivo CSV:</label>
    <input type="file" name="csv_file" accept=".csv">
    <button type="submit">Importar</button>
</form>
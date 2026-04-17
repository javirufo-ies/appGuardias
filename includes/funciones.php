<?php

function agregarProfesor($pdo, $nombre) {
    $stmt = $pdo->prepare("INSERT INTO profesores (nombre) VALUES (?)");
    return $stmt->execute([$nombre]);
}

function agregarTramo($pdo, $dia_semana, $hora_inicio, $hora_fin, $descripcion) {
    $stmt = $pdo->prepare("INSERT INTO tramos (dia_semana, hora_inicio, hora_fin, descripcion) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$dia_semana, $hora_inicio, $hora_fin, $descripcion]);
}



function importarCSV($pdo, $archivo, $tipo) {
    if (!file_exists($archivo)) return ['ok'=>false,'mensaje'=>'Archivo no encontrado'];
    $handle = fopen($archivo, "r");
    if ($handle === FALSE) return ['ok'=>false,'mensaje'=>'No se pudo abrir el archivo'];
    $filasImportadas = 0;
    $filasError = 0;
    // Leer la primera línea para detectar BOM y delimitador
    $primeraLinea = fgets($handle);
    if ($primeraLinea === false) return ['ok'=>false,'mensaje'=>'Archivo vacío'];
    // Eliminar BOM si existe
    $primeraLinea = preg_replace('/^\x{FEFF}/u', '', $primeraLinea);
    // Detectar delimitador: si contiene ; asumimos que es punto y coma, si no, coma
    $delimitador = (strpos($primeraLinea, ';') !== false) ? ';' : ',';
    // Convertir la primera línea a array (cabecera)
    $cabecera = str_getcsv($primeraLinea, $delimitador);
    // Procesar las siguientes filas
    while (($datos = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
        // saltar filas vacías
        if (empty(array_filter($datos))) continue;
        // limpiar espacios
        $datos = array_map(function($campo) {
            // convertir a UTF-8 si viene en Latin-1
            return mb_convert_encoding($campo, 'UTF-8', 'ISO-8859-1');
            }, $datos);        
        try {
            
            if ($tipo === 'profesor') {
                if (!empty($datos[0])) {
                    // Concatenar nombre + apellidos
                    $nombreCompleto = trim($datos[1] . ' ' . ($datos[0] ?? ''));
                    agregarProfesor($pdo, $nombreCompleto);
                    $filasImportadas++;
                } else {
                    var_dump($datos);
                    $filasError++;
            }
            } elseif ($tipo === 'tramo') {
                if (!empty($datos[0])) {
                    agregarTramo($pdo, $datos[0], $datos[1], $datos[2], $datos[3] ?? '');
                    $filasImportadas++;
                } else {
                    $filasError++;
                }
            }
        } catch (Exception $e) {
            $filasError++;
        }
    }

    fclose($handle);

 return [
        'ok' => true,
        'mensaje' => "Importadas: $filasImportadas, Errores: $filasError"
    ];
}




function borrarGuardiasProfesor($pdo, $profesor_id) {
    $stmt = $pdo->prepare("DELETE FROM guardias WHERE profesor_id = ?");
    return $stmt->execute([$profesor_id]);
}

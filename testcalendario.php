<?php
require_once __DIR__ . '/includes/google_client.php';
 
$inicio = (new DateTime('monday this week'))->setTime(0,0);
$fin = (new DateTime('sunday this week'))->setTime(23,59,59);

$stmt = $pdo->prepare("
SELECT * FROM eventos_calendario
WHERE inicio BETWEEN ? AND ?
ORDER BY inicio
");

$stmt->execute([
    $inicio->format('Y-m-d H:i:s'),
    $fin->format('Y-m-d H:i:s')
]);

$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($events as $e) {
    echo $e['descripcion'] . "<br>";
}


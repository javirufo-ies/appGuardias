<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/google_client.php';

$url = "https://calendar.google.com/calendar/ical/ID/public/basic.ics";


$eventos = cogeCalendario(google_calendar);
$pdo->beginTransaction();
$stmt = $pdo->prepare("
    INSERT INTO eventos_calendario
    (uid, descripcion, inicio, fin)
    VALUES
    (:uid, :descripcion, :inicio, :fin)
    ON DUPLICATE KEY UPDATE
    uid = VALUES(uid),
    descripcion = VALUES(descripcion),
    inicio = VALUES(inicio),
    fin = VALUES(fin)
");

foreach ($eventos as $e) {

    $inicio = isset($e['inicio']) ? new DateTimeImmutable(parseICSDate($e['inicio'])) : null;
    $fin   = isset($e['fin']) ? new DateTimeImmutable(parseICSDate($e['fin'])) : null;
    

    $stmt->execute([        
        ':uid' => $e['uid'] ?? null,
        ':descripcion' => $e['descripcion'] ?? '',
        ':inicio' => $inicio ? $inicio->format('Y-m-d') : null,
        ':fin' => $fin ? $fin->format('Y-m-d') : null        
    ]);
}

$pdo->commit();

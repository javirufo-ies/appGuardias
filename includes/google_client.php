<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../admin/config.php';


function fetchICS($url) {

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => "Mozilla/5.0 (compatible; GuardiasApp/1.0)",
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: text/calendar,text/plain;q=0.9,*/*;q=0.8"
        ]
    ]);

    $data = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($data === false || $http !== 200) {
        throw new Exception("Error descargando ICS (HTTP $http)");
    }

    // 🔴 detección de HTML basura (tu problema actual)
    if (stripos($data, '<html') !== false || stripos($data, '<!doctype') !== false) {
        throw new Exception("El servidor devolvió HTML en vez de ICS");
    }

    return $data;
}


function getWeekRange() {
    $tz = new DateTimeZone('Europe/Madrid');

    $start = new DateTime('monday this week', $tz);
    $start->setTime(0, 0, 0);

    $end = new DateTime('sunday this week', $tz);
    $end->setTime(23, 59, 59);

    return [$start, $end];
}


function parseICS($ics) {

    // 🔧 unroll líneas plegadas (RFC 5545)
   // $ics = preg_replace("/\r\n[ \t]/", "", $ics);

    $lines = explode("\n", $ics);

    $events = [];
    $event = null;

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === "BEGIN:VEVENT") {
            $event = [];
            continue;
        }

        if ($line === "END:VEVENT") {
            if ($event) {
                $events[] = normalizeEvent($event);
            }
            $event = null;
            continue;
        }

        if ($event===null) continue;

        // separar clave:valor (con atributos tipo ;TZID=)
        if (strpos($line, ":") !== false) {
            [$key, $value] = explode(":", $line, 2);

            // limpiar parámetros tipo DTSTART;TZID=...
            $key = explode(";", $key)[0];

            $event[$key] = $value;
        }
    }

    return $events;
}

function parseICSDate($value) {

    if (!$value) return null;

    // formato fecha pura YYYYMMDD
    if (preg_match('/^\d{8}$/', $value)) {
        return DateTime::createFromFormat('Ymd', $value)
            ->format('Y-m-d');
    }

    // formato datetime YYYYMMDDTHHMMSSZ
    if (preg_match('/^\d{8}T\d{6}Z$/', $value)) {
        $dt = DateTime::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Europe/Madrid'));
        return $dt->format('Y-m-d H:i:s');
    }

    return $value;
}

function normalizeEvent($e) {

    return [
        'uid' => $e['UID'] ?? null,
        'descripcion' => $e['SUMMARY'] ?? '(Sin título)',
        'inicio' => parseICSDate($e['DTSTART'] ?? null),
        'fin'   => parseICSDate($e['DTEND'] ?? null)        
    ];
}

function normalizeICSFormat($ics) {
    // asegura saltos de línea correctos
    $ics = str_replace(["\r\n", "\r"], "\n", $ics);

    // fuerza separación de bloques críticos si vienen pegados
    $ics = preg_replace('/(BEGIN:VEVENT|END:VEVENT|BEGIN:VCALENDAR|END:VCALENDAR)/', "\n$1", $ics);

    return trim($ics);
}



function cogeCalendario($url){
    $datos = fetchICS($url);
    $eventos = parseICS(normalizeICSFormat($datos));
    foreach ($eventos as $e) {
        echo $e['inicio'] . " - " . $e['descripcion'] . "<br>";
    }
    return $eventos;
}

?>
<?php
date_default_timezone_set('Europe/Madrid');


/* BASE DE DATOS */
define('DB_HOST', 'localhost');
define('DB_NAME', 'guardias_db');
define('DB_USER', 'admin');
define('DB_PASS', 'primuxtech'); // o vacío, según MAMP                            

define('google_calendar', 'https://calendar.google.com/calendar/ical/c_caea1e0b1c3d1f275836e1e0acb4c80891d4967f32bac019dfabde4bf43bef63@group.calendar.google.com/public/basic.ics');

define('google_client_id', '867434824225-rcaa3i6usmkhsr2djfdrehc9ub6sauaj.apps.googleusercontent.com');
define('google_client_secret', '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');
define('google_redirect_uri', 'http://guardavalle.iesvjp.es/oauth2callback.php');
define('google_config', [
    'client_id' => google_client_id,
    'client_secret' => google_client_secret,
    'redirect_uri' => google_redirect_uri
]);
<?php
declare(strict_types=1);

// Bootstrap commun: config, session, sécurité, DB.

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' https: data:; script-src 'self' https: 'unsafe-inline'; style-src 'self' https: 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' https: data: blob:; frame-ancestors 'none'; base-uri 'self'; connect-src 'self' https:;");

date_default_timezone_set('Europe/Paris');

require_once __DIR__ . '/helpers.php';

$appRoot = dirname(__DIR__);
$dbPath = $appRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'app.sqlite';
$uploadsPath = $appRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

if (!is_dir($appRoot . DIRECTORY_SEPARATOR . 'data')) {
    mkdir($appRoot . DIRECTORY_SEPARATOR . 'data', 0755, true);
}
if (!is_dir($uploadsPath)) {
    mkdir($uploadsPath, 0755, true);
}

secure_session_start();
$pdo = db_connect($dbPath);


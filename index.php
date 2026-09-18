<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('Pragma: no-cache', true);
header('Expires: 0', true);

$page = $_GET['page'] ?? 'home';

$routes = [
    'home' => 'home.php',
    'check-in' => 'check-in.php',
    'sos' => 'sos.php',
    'progress' => 'progress.php',
    'future-me' => 'future-me.php',
];

if (!array_key_exists($page, $routes)) {
    header('Location: /?page=home');
    exit;
}

require __DIR__ . '/pages/' . $routes[$page];
<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow', true);

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
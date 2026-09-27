<?php

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow', true);
header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0',
    true
);
header('Pragma: no-cache', true);
header('Expires: 0', true);
header('Referrer-Policy: no-referrer', true);

require_once __DIR__ . '/includes/auth.php';

$page = trim(
    (string) ($_GET['page'] ?? 'home')
);

$routes = [
    'login' => 'login.php',
    'verify-login' => 'verify-login.php',
    'logout' => 'logout.php',
    'home' => 'home.php',
    'check-in' => 'check-in.php',
    'sos' => 'sos.php',
    'progress' => 'progress.php',
    'future-me' => 'future-me.php',
];

if (!array_key_exists($page, $routes)) {
    if (auth_is_authenticated()) {
        header('Location: /?page=home');
    } else {
        header('Location: /?page=login');
    }

    exit;
}

$publicPages = [
    'login',
    'verify-login',
];

if (!in_array($page, $publicPages, true)) {
    auth_require_login();
}

require __DIR__ . '/pages/' . $routes[$page];
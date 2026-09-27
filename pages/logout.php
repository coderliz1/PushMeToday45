<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /?page=home');
    exit;
}

$submittedCsrf = trim(
    (string) ($_POST['csrf_token'] ?? '')
);

if (!auth_csrf_is_valid($submittedCsrf)) {
    header('Location: /?page=home');
    exit;
}

auth_logout();

header('Location: /?page=login');
exit;
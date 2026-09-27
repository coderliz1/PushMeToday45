<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

header('Referrer-Policy: no-referrer', true);

if (auth_is_authenticated()) {
    header('Location: /?page=home');
    exit;
}

$plainToken = trim(
    (string) ($_GET['token'] ?? '')
);

if (
    $plainToken !== '' &&
    auth_complete_magic_login($plainToken)
) {
    header('Location: /?page=home');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta name="robots" content="noindex, nofollow">

    <title>Login Link Expired | PushMeToday45</title>

    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="login-page">
    <main class="login-shell">
        <section class="login-card">
            <div class="login-brand">
                PushMeToday<span>45</span>
            </div>

            <div class="login-status-icon" aria-hidden="true">
                ⏳
            </div>

            <p class="eyebrow">Link Unavailable</p>

            <h1>This login link doesn’t work.</h1>

            <p class="login-description">
                It may have expired, already been used, or
                become invalid.
            </p>

            <p class="login-help">
                Request a new secure link to continue.
            </p>

            <a
                class="login-submit-button login-link-button"
                href="/?page=login"
            >
                Send a New Magic Link
            </a>
        </section>
    </main>
</body>
</html>
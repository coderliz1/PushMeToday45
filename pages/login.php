<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (auth_is_authenticated()) {
    header('Location: /?page=home');
    exit;
}

auth_start_state_session();

$requestCompleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestStartedAt = microtime(true);

    $submittedCsrf = trim(
        (string) ($_POST['csrf_token'] ?? '')
    );

    if (auth_csrf_is_valid($submittedCsrf)) {
        $submittedEmail = trim(
            (string) ($_POST['email'] ?? '')
        );

        try {
            auth_request_magic_link($submittedEmail);
        } catch (Throwable $exception) {
            error_log(
                'Magic-link request failed: ' .
                $exception->getMessage()
            );
        }
    }

    $minimumResponseSeconds = 0.8;
    $elapsedSeconds = microtime(true) - $requestStartedAt;

    if ($elapsedSeconds < $minimumResponseSeconds) {
        usleep(
            (int) (
                ($minimumResponseSeconds - $elapsedSeconds)
                * 1000000
            )
        );
    }

    $requestCompleted = true;
}

$csrfToken = auth_csrf_token();

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

    <title>Log In | PushMeToday45</title>

    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="login-page">
    <main class="login-shell">
        <section class="login-card">
            <div class="login-brand">
                PushMeToday<span>45</span>
            </div>

            <?php if ($requestCompleted): ?>

                <div class="login-status-icon" aria-hidden="true">
                    ✉️
                </div>

                <p class="eyebrow">Magic Link Requested</p>

                <h1>Check your email</h1>

                <p class="login-description">
                    If this email is authorized, we’ve sent a
                    secure login link.
                </p>

                <p class="login-help">
                    The link expires in 15 minutes and can only
                    be used once. Check your spam folder if you
                    don’t see it.
                </p>

                <a
                    class="login-secondary-action"
                    href="/?page=login"
                >
                    Send another link
                </a>

            <?php else: ?>

                <div class="login-status-icon" aria-hidden="true">
                    🔥
                </div>

                <p class="eyebrow">Private Fitness Space</p>

                <h1>Welcome back.</h1>

                <p class="login-description">
                    Enter your email to continue.
                </p>

                <form
                    class="magic-login-form"
                    method="post"
                    action="/?page=login"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="field-group">
                        <label for="login_email">
                            Email address
                        </label>

                        <input
                            id="login_email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            inputmode="email"
                            required
                            placeholder="you@example.com"
                        >
                    </div>

                    <button
                        class="login-submit-button"
                        type="submit"
                    >
                        Send Magic Link
                    </button>
                </form>

                <p class="login-privacy-note">
                    🔒 Private. Single-user. Me, Myself &amp; I
                    Edition.
                </p>

            <?php endif; ?>
        </section>
    </main>
</body>
</html>
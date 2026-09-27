<?php

$pageTitle = $pageTitle ?? 'PushMeToday45';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <div class="app-shell">
        <header class="app-header">
            <a class="app-logo" href="/">
                PushMeToday<span>45</span>
            </a>

                        <div class="app-header-actions">
                <span
                    class="privacy-badge"
                    aria-label="Private app"
                >
                    Private
                </span>

                <form
                    class="logout-form"
                    method="post"
                    action="/?page=logout"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            auth_csrf_token(),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <button
                        class="logout-button"
                        type="submit"
                    >
                        Log Out
                    </button>
                </form>
            </div>
        </header>

        <main class="app-content">
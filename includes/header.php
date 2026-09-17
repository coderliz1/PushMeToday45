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

            <span class="privacy-badge" aria-label="Private app">
                Private
            </span>
        </header>

        <main class="app-content">
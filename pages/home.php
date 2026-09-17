<?php

$pageTitle = 'Home | PushMeToday45';
$activePage = 'home';

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="welcome-section">
    <p class="eyebrow">Your 45-Day Challenge</p>
    <h1>Hey, you showed up.</h1>
    <p class="welcome-message">
        One good decision at a time. That is how this gets done.
    </p>
</section>

<section class="challenge-card">
    <div class="challenge-heading">
        <div>
            <p class="card-label">Current progress</p>
            <h2>Day 1 of 45</h2>
        </div>

        <div class="progress-number">2%</div>
    </div>

    <div
        class="progress-track"
        role="progressbar"
        aria-label="Challenge progress"
        aria-valuemin="0"
        aria-valuemax="45"
        aria-valuenow="1"
    >
        <div class="progress-fill" style="width: 2%;"></div>
    </div>

    <p class="progress-caption">44 days remain. Let’s make today count.</p>
</section>

<section class="stats-grid" aria-label="Challenge statistics">
    <article class="stat-card stat-pink">
        <span class="stat-label">Weight</span>
        <strong>—</strong>
        <span class="stat-note">Add your first check-in</span>
    </article>

    <article class="stat-card stat-purple">
        <span class="stat-label">Waist</span>
        <strong>—</strong>
        <span class="stat-note">No measurement yet</span>
    </article>

    <article class="stat-card stat-mint">
        <span class="stat-label">Workouts</span>
        <strong>0</strong>
        <span class="stat-note">This challenge</span>
    </article>

    <article class="stat-card stat-blue">
        <span class="stat-label">Streak</span>
        <strong>0 days</strong>
        <span class="stat-note">Start today</span>
    </article>
</section>

<section class="action-stack">
    <a class="primary-action" href="/?page=check-in">
        <span>
            <strong>Daily Check-In</strong>
            <small>Log today’s progress</small>
        </span>

        <span class="action-arrow">→</span>
    </a>

    <a class="sos-action" href="/?page=sos">
        <span>
            <strong>I Need Help</strong>
            <small>Motivation for the moment you’re in</small>
        </span>

        <span class="action-arrow">→</span>
    </a>
</section>

<section class="future-card">
    <p class="card-label">A note from Future You</p>
    <blockquote>
        “Thank you for not quitting on the days when motivation disappeared.”
    </blockquote>
    <a href="/?page=future-me">Visit Future Me →</a>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
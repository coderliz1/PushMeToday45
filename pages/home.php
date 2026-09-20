<?php

$pageTitle = 'Home | PushMeToday45';
$activePage = 'home';

require_once dirname(__DIR__) . '/includes/functions.php';

$challenge = db()
    ->query(
        "SELECT *
         FROM challenges
         WHERE status = 'active'
         ORDER BY start_date DESC
         LIMIT 1"
    )
    ->fetch();

$challengeName = 'No active challenge';
$challengeSchedule = 'Choose dates to begin';
$progressPercent = 0;
$elapsedDays = 0;
$daysRemaining = 0;
$totalCalendarDays = 0;
$latestWeight = null;
$latestWaist = null;
$workoutCount = 0;
$checkInCount = 0;

if ($challenge) {
    $timezone = new DateTimeZone('America/New_York');
    $today = new DateTimeImmutable('today', $timezone);
    $startDate = new DateTimeImmutable($challenge['start_date'], $timezone);
    $endDate = new DateTimeImmutable($challenge['end_date'], $timezone);

    $challengeName = $challenge['name'];
    $challengeSchedule =
        $startDate->format('M j') . '–' . $endDate->format('M j, Y');

    $totalCalendarDays =
        (int) $startDate->diff($endDate)->format('%a') + 1;

    if ($today < $startDate) {
        $elapsedDays = 0;
    } elseif ($today > $endDate) {
        $elapsedDays = $totalCalendarDays;
    } else {
        $elapsedDays =
            (int) $startDate->diff($today)->format('%a') + 1;
    }

    $daysRemaining = max(0, $totalCalendarDays - $elapsedDays);

    if ($totalCalendarDays > 0) {
        $progressPercent = (int) round(
            ($elapsedDays / $totalCalendarDays) * 100
        );
    }

    $checkInStatement = db()->prepare(
        'SELECT
            check_in_date,
            weight,
            waist,
            activities
         FROM check_ins
         WHERE check_in_date BETWEEN :start_date AND :end_date
         ORDER BY check_in_date ASC'
    );

    $checkInStatement->execute([
        'start_date' => $challenge['start_date'],
        'end_date' => $challenge['end_date'],
    ]);

    $checkIns = $checkInStatement->fetchAll();
    $checkInCount = count($checkIns);

    foreach ($checkIns as $checkIn) {
        if ($checkIn['weight'] !== null) {
            $latestWeight = (float) $checkIn['weight'];
        }

        if ($checkIn['waist'] !== null) {
            $latestWaist = (float) $checkIn['waist'];
        }

        if (!empty($checkIn['activities'])) {
            $activities = array_map(
                'trim',
                explode(',', $checkIn['activities'])
            );

            $movementActivities = array_diff($activities, ['rest']);

            if ($movementActivities !== []) {
                $workoutCount++;
            }
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="welcome-section">
    <p class="eyebrow">
    <?= htmlspecialchars($challengeName, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <h1>Hey, you showed up.</h1>
    <p class="welcome-message">
        One good decision at a time. That is how this gets done.
    </p>
</section>

<section class="challenge-card">
    <div class="challenge-heading">
        <div>
            <p class="card-label">Current progress</p>
            <h2>
            <?= htmlspecialchars($challengeSchedule, ENT_QUOTES, 'UTF-8') ?>
            </h2>
        </div>

        <div class="progress-number">
        <?= $progressPercent ?>%
        </div>
    </div>

   <div
    class="progress-track"
    role="progressbar"
    aria-label="Challenge progress"
    aria-valuemin="0"
    aria-valuemax="<?= $totalCalendarDays ?>"
    aria-valuenow="<?= $elapsedDays ?>"
>
    <div
        class="progress-fill"
        style="width: <?= $progressPercent ?>%;"
    ></div>
</div>

    <p class="progress-caption">
    <?= $elapsedDays ?> calendar days in.
    <?= $daysRemaining ?> days remain.
    Let’s make today count.
</p>
</section>

<section class="stats-grid" aria-label="Challenge statistics">
    <article class="stat-card stat-pink">
    <span class="stat-label">Latest Weight</span>

    <strong>
        <?= $latestWeight !== null
            ? number_format($latestWeight, 1) . ' lb'
            : '—' ?>
    </strong>

    <span class="stat-note">
        <?= $latestWeight !== null
            ? 'Most recent measurement'
            : 'No weight recorded yet' ?>
    </span>
</article>

    <article class="stat-card stat-purple">
    <span class="stat-label">Latest Waist</span>

    <strong>
        <?= $latestWaist !== null
            ? number_format($latestWaist, 1) . ' in'
            : '—' ?>
    </strong>

    <span class="stat-note">
        <?= $latestWaist !== null
            ? 'Most recent measurement'
            : 'No waist recorded yet' ?>
    </span>
</article>

    <article class="stat-card stat-mint">
    <span class="stat-label">Workouts</span>
    <strong><?= $workoutCount ?></strong>
    <span class="stat-note">Logged this challenge</span>
</article>

    <article class="stat-card stat-blue">
    <span class="stat-label">Check-Ins</span>
    <strong><?= $checkInCount ?></strong>
    <span class="stat-note">Whenever you needed them</span>
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
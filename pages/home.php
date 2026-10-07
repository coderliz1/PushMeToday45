<?php

declare(strict_types=1);

$pageTitle = 'Home | PushMeToday45';
$activePage = 'home';
require_once dirname(__DIR__) . '/includes/challenges.php';

$timezone = new DateTimeZone('America/New_York');
$today = new DateTimeImmutable('today', $timezone);
$formError = null;
$submittedStartDate = $today->format('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !auth_csrf_is_valid($token)) {
            throw new InvalidArgumentException('Your form expired. Please try again.');
        }

        $action = $_POST['challenge_action'] ?? '';
        if ($action === 'start') {
            $startDate = $_POST['start_date'] ?? '';
            if (!is_string($startDate)) {
                throw new InvalidArgumentException('Please choose a valid start date.');
            }
            $submittedStartDate = $startDate;
            challenge_create($startDate);
            header('Location: /?page=home', true, 303);
            exit;
        }

        if ($action === 'finish') {
            $challengeId = filter_var(
                $_POST['challenge_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );
            if ($challengeId === false) {
                throw new InvalidArgumentException('Please choose a valid challenge.');
            }
            $finishedId = challenge_finish($challengeId);
            header('Location: /?page=progress&challenge_id=' . $finishedId, true, 303);
            exit;
        }

        throw new InvalidArgumentException('Please choose a challenge action.');
    } catch (InvalidArgumentException $exception) {
        $formError = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Challenge update failed: ' . $exception->getMessage());
        $formError = 'The challenge update could not be saved. Please try again.';
    }
}

$challenge = challenge_current();
$challengeName = 'Your next chapter';
$challengeSchedule = 'Choose your next start date';
$progressPercent = 0;
$elapsedDays = 0;
$daysRemaining = 0;
$totalCalendarDays = 0;
$latestWeight = null;
$latestWaist = null;
$workoutCount = 0;
$checkInCount = 0;
$isUpcoming = false;
$canFinish = false;
$progressCaption = 'Start a new 45-day challenge whenever you are ready.';

if ($challenge !== null) {
    $startDate = challenge_calendar_date((string) $challenge['start_date']);
    $endDate = challenge_calendar_date((string) $challenge['end_date']);
    $challengeName = (string) $challenge['name'];
    $challengeSchedule = $startDate->format('M j') . '–' . $endDate->format('M j, Y');
    $totalCalendarDays = (int) $startDate->diff($endDate)->format('%a') + 1;
    $isUpcoming = $today < $startDate;
    $canFinish = $today > $endDate;

    if ($isUpcoming) {
        $elapsedDays = 0;
    } elseif ($canFinish) {
        $elapsedDays = $totalCalendarDays;
    } else {
        $elapsedDays = (int) $startDate->diff($today)->format('%a') + 1;
    }
    $daysRemaining = max(0, $totalCalendarDays - $elapsedDays);
    $progressPercent = $totalCalendarDays > 0
        ? (int) round(($elapsedDays / $totalCalendarDays) * 100)
        : 0;

    if ($isUpcoming) {
        $progressCaption = 'Your challenge begins ' . $startDate->format('M j, Y') . '.';
    } elseif ($canFinish) {
        $progressCaption = 'Your challenge dates have ended. Finish it to view your results.';
    } else {
        $progressCaption = $elapsedDays . ' calendar days in. ' .
            $daysRemaining . ' days remain. Let’s make today count.';
    }

    $statement = db()->prepare(
        'SELECT check_in_date, weight, waist FROM check_ins
         WHERE challenge_id = :challenge_id ORDER BY check_in_date ASC'
    );
    $statement->execute(['challenge_id' => (int) $challenge['id']]);
    $checkIns = $statement->fetchAll();
    $checkInCount = count($checkIns);
    foreach ($checkIns as $checkIn) {
        if ($checkIn['weight'] !== null) {
            $latestWeight = (float) $checkIn['weight'];
        }
        if ($checkIn['waist'] !== null) {
            $latestWaist = (float) $checkIn['waist'];
        }
    }

    $statement = db()->prepare(
        'SELECT COUNT(*) FROM check_in_activities AS activity
         INNER JOIN check_ins AS check_in ON check_in.id = activity.check_in_id
         WHERE check_in.challenge_id = :challenge_id
           AND activity.activity_type <> :rest_type'
    );
    $statement->execute([
        'challenge_id' => (int) $challenge['id'],
        'rest_type' => 'rest_day',
    ]);
    $workoutCount = (int) $statement->fetchColumn();
}

require dirname(__DIR__) . '/includes/header.php';
?>

<section class="welcome-section">
    <p class="eyebrow"><?= htmlspecialchars($challengeName, ENT_QUOTES, 'UTF-8') ?></p>
    <h1>Hey, you showed up.</h1>
    <p class="welcome-message">One good decision at a time. That is how this gets done.</p>
</section>

<section class="challenge-card">
    <div class="challenge-heading">
        <div>
            <p class="card-label"><?= $isUpcoming ? 'Upcoming challenge' : 'Current progress' ?></p>
            <h2><?= htmlspecialchars($challengeSchedule, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
        <?php if ($challenge !== null): ?>
            <div class="progress-number"><?= $progressPercent ?>%</div>
        <?php endif; ?>
    </div>
    <?php if ($challenge !== null): ?>
        <div class="progress-track" role="progressbar" aria-label="Challenge progress"
             aria-valuemin="0" aria-valuemax="<?= $totalCalendarDays ?>"
             aria-valuenow="<?= $elapsedDays ?>">
            <div class="progress-fill" style="width: <?= $progressPercent ?>%;"></div>
        </div>
    <?php endif; ?>
    <p class="progress-caption"><?= htmlspecialchars($progressCaption, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($formError !== null): ?>
        <p role="alert"><?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($challenge === null): ?>
        <form method="post" action="/?page=home">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="challenge_action" value="start">
            <label for="challenge-start-date">Start date</label>
            <input id="challenge-start-date" type="date" name="start_date" required
                   min="<?= $today->format('Y-m-d') ?>"
                   value="<?= htmlspecialchars($submittedStartDate, ENT_QUOTES, 'UTF-8') ?>">
            <p class="progress-caption">Your start date counts as Day 1. The challenge lasts 45 calendar days.</p>
            <button type="submit">Start Challenge</button>
        </form>
    <?php elseif ($canFinish): ?>
        <form method="post" action="/?page=home">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="challenge_action" value="finish">
            <input type="hidden" name="challenge_id" value="<?= (int) $challenge['id'] ?>">
            <p class="progress-caption">Finish this challenge to view its recorded results. Your entries will be preserved.</p>
            <button type="submit">Finish Challenge</button>
        </form>
    <?php else: ?>
        <p class="progress-caption">Finish will be available <?= $endDate->modify('+1 day')->format('M j, Y') ?>.</p>
    <?php endif; ?>
    <p><a href="/?page=progress">View challenge progress and history →</a></p>
</section>

<section class="stats-grid" aria-label="Challenge statistics">
    <article class="stat-card stat-pink">
        <span class="stat-label">Latest Weight</span>
        <strong><?= $latestWeight !== null ? number_format($latestWeight, 1) . ' lb' : '—' ?></strong>
        <span class="stat-note"><?= $latestWeight !== null ? 'Most recent measurement' : 'No weight recorded yet' ?></span>
    </article>
    <article class="stat-card stat-purple">
        <span class="stat-label">Latest Waist</span>
        <strong><?= $latestWaist !== null ? number_format($latestWaist, 1) . ' in' : '—' ?></strong>
        <span class="stat-note"><?= $latestWaist !== null ? 'Most recent measurement' : 'No waist recorded yet' ?></span>
    </article>
    <article class="stat-card stat-mint">
        <span class="stat-label">Activities</span>
        <strong><?= $workoutCount ?></strong>
        <span class="stat-note">Logged this challenge</span>
    </article>
    <article class="stat-card stat-blue">
        <span class="stat-label">Check-Ins</span>
        <strong><?= $checkInCount ?></strong>
        <span class="stat-note">Dates with saved entries</span>
    </article>
</section>

<section class="action-stack">
    <a class="primary-action" href="/?page=check-in">
        <span><strong>Daily Check-In</strong><small>Log today’s progress</small></span>
        <span class="action-arrow">→</span>
    </a>
    <a class="sos-action" href="/?page=sos">
        <span><strong>I Need Help</strong><small>Motivation for the moment you’re in</small></span>
        <span class="action-arrow">→</span>
    </a>
</section>

<section class="future-card">
    <p class="card-label">A note from Future You</p>
    <blockquote>“Thank you for not quitting on the days when motivation disappeared.”</blockquote>
    <a href="/?page=future-me">Visit Future Me →</a>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

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

$homeState = challenge_app_state();
$challenge = $homeState['challenge'];
$isActive = $homeState['state'] === 'active';
$isUpcoming = $homeState['state'] === 'upcoming';
$canFinish = $homeState['state'] === 'awaiting_finish';

$escape = static fn (string $value): string =>
    htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$formatSchedule = static function (array $record): string {
    $start = challenge_calendar_date((string) $record['start_date']);
    $end = challenge_calendar_date((string) $record['end_date']);

    $startFormat = $start->format('Y') === $end->format('Y') ? 'M j' : 'M j, Y';

    return $start->format($startFormat) . '–' . $end->format('M j, Y');
};

$recapUrl = static fn (int $id): string =>
    '/?page=progress&challenge_id=' . $id . '&recap=1#challenge-recap';

$latestWeight = null;
$latestWaist = null;
$workoutCount = 0;
$checkInCount = 0;
$pastChallenges = [];

if ($isActive) {
    $startDate = challenge_calendar_date((string) $challenge['start_date']);
    $endDate = challenge_calendar_date((string) $challenge['end_date']);
    $challengeName = (string) $challenge['name'];
    $challengeSchedule = $formatSchedule($challenge);
    $totalCalendarDays = (int) $startDate->diff($endDate)->format('%a') + 1;
    $elapsedDays = (int) $startDate->diff($today)->format('%a') + 1;
    $daysRemaining = max(0, $totalCalendarDays - $elapsedDays);
    $progressPercent = (int) round(($elapsedDays / $totalCalendarDays) * 100);
    $progressCaption = $elapsedDays . ' calendar days in. ' .
        $daysRemaining . ' days remain. Let’s make today count.';

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
} else {
    $pastChallenges = db()->query(
        "SELECT ch.id, ch.start_date, ch.end_date,
                DATEDIFF(ch.end_date, ch.start_date) + 1 AS calendar_days,
                (SELECT COUNT(*) FROM check_ins AS daily
                 WHERE daily.challenge_id = ch.id) AS check_in_days,
                (SELECT COALESCE(SUM(activity.duration_minutes), 0)
                 FROM check_in_activities AS activity
                 INNER JOIN check_ins AS daily ON daily.id = activity.check_in_id
                 WHERE daily.challenge_id = ch.id
                   AND activity.activity_type <> 'rest_day') AS movement_minutes
         FROM challenges AS ch
         WHERE ch.status IN ('completed', 'archived')
         ORDER BY ch.end_date DESC, ch.id DESC"
    )->fetchAll();
}

$latestCompleted = $pastChallenges[0] ?? null;

require dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($isActive): ?>
    <section class="welcome-section">
        <p class="eyebrow"><?= $escape($challengeName) ?></p>
        <h1>Hey, you showed up.</h1>
        <p class="welcome-message">One good decision at a time. That is how this gets done.</p>
    </section>

    <section class="challenge-card">
        <div class="challenge-heading">
            <div>
                <p class="card-label">Current progress</p>
                <h2><?= $escape($challengeSchedule) ?></h2>
            </div>
            <div class="progress-number"><?= $progressPercent ?>%</div>
        </div>
        <div class="progress-track" role="progressbar" aria-label="Challenge progress"
             aria-valuemin="0" aria-valuemax="<?= $totalCalendarDays ?>"
             aria-valuenow="<?= $elapsedDays ?>">
            <div class="progress-fill" style="width: <?= $progressPercent ?>%;"></div>
        </div>
        <p class="progress-caption"><?= $escape($progressCaption) ?></p>

        <?php if ($formError !== null): ?>
            <p role="alert"><?= $escape($formError) ?></p>
        <?php endif; ?>

        <p class="progress-caption">Finish will be available <?= $endDate->modify('+1 day')->format('M j, Y') ?>.</p>
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
<?php else: ?>
    <section class="welcome-section">
        <?php if ($latestCompleted !== null): ?>
            <p class="eyebrow">🏆 Challenge #<?= (int) $latestCompleted['id'] ?> complete</p>
            <h1><?= (int) $latestCompleted['calendar_days'] ?> challenge days. Done.</h1>
            <p class="welcome-message"><?= $escape($formatSchedule($latestCompleted)) ?></p>
            <a class="recap-launch" href="<?= $escape($recapUrl((int) $latestCompleted['id'])) ?>">View My Recap →</a>
        <?php else: ?>
            <p class="eyebrow">Your next chapter</p>
            <h1>Hey, you showed up.</h1>
            <p class="welcome-message">One good decision at a time. That is how this gets done.</p>
        <?php endif; ?>
    </section>

    <section class="challenge-card">
        <?php if ($formError !== null): ?>
            <p role="alert"><?= $escape($formError) ?></p>
        <?php endif; ?>

        <?php if ($isUpcoming): ?>
            <?php $daysUntilStart = (int) $homeState['days_until_start']; ?>
            <p class="card-label">🔥 Next 45</p>
            <h2>Challenge #<?= (int) $challenge['id'] ?></h2>
            <p>Starts <?= $escape(challenge_calendar_date((string) $challenge['start_date'])->format('F j, Y')) ?>.</p>
            <p class="progress-caption"><?= $daysUntilStart ?> <?= $daysUntilStart === 1 ? 'day' : 'days' ?> to go. Check-In opens on Day 1.</p>
        <?php elseif ($canFinish): ?>
            <p class="card-label">Your challenge dates have ended</p>
            <h2>Ready to wrap up Challenge #<?= (int) $challenge['id'] ?>?</h2>
            <p><?= $escape($formatSchedule($challenge)) ?></p>
            <form method="post" action="/?page=home">
                <input type="hidden" name="csrf_token" value="<?= $escape(auth_csrf_token()) ?>">
                <input type="hidden" name="challenge_action" value="finish">
                <input type="hidden" name="challenge_id" value="<?= (int) $challenge['id'] ?>">
                <p class="progress-caption">Finish to see your recap. Your entries will be preserved.</p>
                <button class="recap-launch" type="submit">Finish Challenge</button>
            </form>
        <?php else: ?>
            <p class="card-label">🔥 Your next chapter</p>
            <h2>Ready for your next 45?</h2>
            <p>Choose when you’re ready to start again.</p>
            <form method="post" action="/?page=home">
                <input type="hidden" name="csrf_token" value="<?= $escape(auth_csrf_token()) ?>">
                <input type="hidden" name="challenge_action" value="start">
                <label for="challenge-start-date">Start date</label>
                <input id="challenge-start-date" type="date" name="start_date" required
                       min="<?= $today->format('Y-m-d') ?>"
                       value="<?= $escape($submittedStartDate) ?>">
                <p class="progress-caption">Your start date is Day 1. Choose today to start now or a future date to schedule it.</p>
                <button class="recap-launch" type="submit">Start / Schedule My Next 45</button>
            </form>
        <?php endif; ?>
    </section>

    <?php if ($pastChallenges !== []): ?>
        <section class="progress-panel" aria-labelledby="my-45s-heading">
            <p class="card-label">Challenge history</p>
            <h2 id="my-45s-heading">🏆 My 45s</h2>
            <div class="check-in-history">
                <?php foreach ($pastChallenges as $pastChallenge): ?>
                    <article class="stat-card stat-purple">
                        <h3>Challenge #<?= (int) $pastChallenge['id'] ?> ✓</h3>
                        <p><?= $escape($formatSchedule($pastChallenge)) ?></p>
                        <p class="stat-note">
                            <?= (int) $pastChallenge['calendar_days'] ?> challenge days ·
                            <?= (int) $pastChallenge['check_in_days'] ?> dates with entries ·
                            <?= number_format((int) $pastChallenge['movement_minutes']) ?> movement minutes
                        </p>
                        <a class="recap-launch" href="<?= $escape($recapUrl((int) $pastChallenge['id'])) ?>">View Recap →</a>
                    </article>
                <?php endforeach; ?>
            </div>
            <p><a href="/?page=progress">View challenge progress and history →</a></p>
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="action-stack">
    <?php if ($isActive): ?>
        <a class="primary-action" href="/?page=check-in">
            <span><strong>Daily Check-In</strong><small>Log today’s progress</small></span>
            <span class="action-arrow">→</span>
        </a>
    <?php endif; ?>

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
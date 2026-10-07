<?php

$pageTitle = 'Progress | PushMeToday45';
$activePage = 'progress';

require_once dirname(__DIR__) . '/includes/challenges.php';

$availableChallenges = db()->query(
    "SELECT * FROM challenges
     WHERE status IN ('upcoming', 'active', 'completed', 'archived')
     ORDER BY start_date DESC, id DESC"
)->fetchAll();

$challenge = challenge_current();
$selectionError = null;

if (isset($_GET['challenge_id'])) {
    $selectedId = filter_var(
        $_GET['challenge_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $challenge = null;

    if ($selectedId !== false) {
        foreach ($availableChallenges as $availableChallenge) {
            if ((int) $availableChallenge['id'] === $selectedId) {
                $challenge = $availableChallenge;
                break;
            }
        }
    }

    if ($challenge === null) {
        $selectionError = 'That challenge could not be found. Please choose a challenge below.';
    }
} elseif ($challenge === null && $availableChallenges !== []) {
    $challenge = $availableChallenges[0];
}

$progressCaption = 'Choose a challenge to see its progress.';

$challengeName = 'No challenge selected';
$challengeSchedule = 'Your challenge history';
$progressPercent = 0;
$daysRemaining = 0;
$checkIns = [];
$workoutCount = 0;
$totalMovementMinutes = 0;
$startingWeight = null;
$latestWeight = null;
$startingWaist = null;
$latestWaist = null;
$weightChange = null;
$waistChange = null;
$weightEntries = [];
$chartPoints = [];
$waistEntryCount = 0;

$moodLabels = [
    'great' => 'Great',
    'good' => 'Good',
    'okay' => 'Okay',
    'rough' => 'Rough',
    'struggling' => 'Rough',
    'irritated' => 'Irritated',
    'anxious_stressed' => 'Anxious / Stressed',
    'tired' => 'Tired',
];

if ($challenge) {
    $timezone = new DateTimeZone('America/New_York');
    $today = new DateTimeImmutable('today', $timezone);
    $startDate = new DateTimeImmutable(
        $challenge['start_date'],
        $timezone
    );
    $endDate = new DateTimeImmutable(
        $challenge['end_date'],
        $timezone
    );

    $challengeName = $challenge['name'];
    $challengeSchedule =
        $startDate->format('M j') .
        '–' .
        $endDate->format('M j, Y');

    $totalDays =
        (int) $startDate->diff($endDate)->format('%a') + 1;

    if ($today < $startDate) {
        $elapsedDays = 0;
    } elseif ($today > $endDate) {
        $elapsedDays = $totalDays;
    } else {
        $elapsedDays =
            (int) $startDate->diff($today)->format('%a') + 1;
    }

    $daysRemaining = max(0, $totalDays - $elapsedDays);

    if (in_array($challenge['status'], ['completed', 'archived'], true)) {
        $progressCaption = 'Completed challenge — your recorded results are shown below.';
    } elseif ($today < $startDate) {
        $progressCaption = 'Your challenge begins ' . $startDate->format('M j, Y') . '.';
    } elseif ($today > $endDate) {
        $progressCaption = 'The scheduled dates have ended. This challenge remains open until you finish it.';
    } else {
        $progressCaption = $daysRemaining . ' calendar days remain.';
    }

    if ($totalDays > 0) {
        $progressPercent = (int) round(
            ($elapsedDays / $totalDays) * 100
        );
    }

    $statement = db()->prepare(
        'SELECT
            check_in.*,
            COALESCE(
                (
                    SELECT mood_entry.mood
                    FROM check_in_moods AS mood_entry
                    WHERE mood_entry.check_in_id = check_in.id
                    ORDER BY
                        mood_entry.occurred_at DESC,
                        mood_entry.id DESC
                    LIMIT 1
                ),
                check_in.mood
            ) AS display_mood
         FROM check_ins AS check_in
         WHERE check_in.challenge_id = :challenge_id
         ORDER BY check_in.check_in_date ASC'
    );

    $statement->execute([
        'challenge_id' => (int) $challenge['id'],
    ]);

    $checkIns = $statement->fetchAll();

    foreach ($checkIns as $checkIn) {
        if ($checkIn['weight'] !== null) {
            $weight = (float) $checkIn['weight'];

            if ($startingWeight === null) {
                $startingWeight = $weight;
            }

            $latestWeight = $weight;

            $weightEntries[] = [
                'date' => $checkIn['check_in_date'],
                'weight' => $weight,
            ];
        }

        if ($checkIn['waist'] !== null) {
            $waistEntryCount++;
            $waist = (float) $checkIn['waist'];

            if ($startingWaist === null) {
                $startingWaist = $waist;
            }

            $latestWaist = $waist;
        }
    }

    $activityStatement = db()->prepare(
        'SELECT
            COUNT(*) AS activity_count,
            COALESCE(SUM(activity.duration_minutes), 0)
                AS movement_minutes
         FROM check_in_activities AS activity
         INNER JOIN check_ins AS check_in
            ON check_in.id = activity.check_in_id
         WHERE check_in.challenge_id = :challenge_id
           AND activity.activity_type <> :rest_type'
    );

    $activityStatement->execute([
        'challenge_id' => (int) $challenge['id'],
        'rest_type' => 'rest_day',
    ]);

    $activityTotals = $activityStatement->fetch();

    if ($activityTotals) {
        $workoutCount =
            (int) $activityTotals['activity_count'];

        $totalMovementMinutes =
            (int) $activityTotals['movement_minutes'];
    }

    if (
        count($weightEntries) >= 2 &&
        $startingWeight !== null &&
        $latestWeight !== null
    ) {
        $weightChange = $startingWeight - $latestWeight;
    }

    if (
        $waistEntryCount >= 2 &&
        $startingWaist !== null &&
        $latestWaist !== null
    ) {
        $waistChange = $startingWaist - $latestWaist;
    }

    if ($weightEntries !== []) {
        $weightValues = array_column(
            $weightEntries,
            'weight'
        );

        $minimumWeight = min($weightValues);
        $maximumWeight = max($weightValues);
        $weightRange = max(
            1,
            $maximumWeight - $minimumWeight
        );

        $entryCount = count($weightEntries);

        foreach ($weightEntries as $index => $entry) {
            $x = $entryCount === 1
                ? 160
                : 10 + (
                    ($index / ($entryCount - 1)) * 300
                );

            $y = 130 - (
                (
                    ($entry['weight'] - $minimumWeight)
                    / $weightRange
                ) * 110
            );

            $chartPoints[] = [
                'coordinates' =>
                    round($x, 1) .
                    ',' .
                    round($y, 1),
                'x' => round($x, 1),
                'y' => round($y, 1),
                'weight' => $entry['weight'],
                'date' => $entry['date'],
            ];
        }
    }
}

$recentCheckIns = array_reverse($checkIns);

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="page-heading">
    <p class="eyebrow">Your progress</p>

    <h1>Look how far you’ve come.</h1>

    <p>
        Every entry tells part of the story. Checking in is optional;
        your progress still counts.
    </p>
</section>

<?php if ($availableChallenges !== []): ?>
    <section class="progress-panel">
        <form method="get" action="/">
            <input type="hidden" name="page" value="progress">
            <label for="progress-challenge">Choose a challenge</label>
            <select id="progress-challenge" name="challenge_id">
                <?php foreach ($availableChallenges as $availableChallenge): ?>
                    <option
                        value="<?= (int) $availableChallenge['id'] ?>"
                        <?= $challenge !== null &&
                            (int) $challenge['id'] === (int) $availableChallenge['id']
                            ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $availableChallenge['name'] . ' · ' .
                            $availableChallenge['start_date'] . '–' .
                            $availableChallenge['end_date'] . ' · ' .
                            ucfirst($availableChallenge['status']),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">View Challenge</button>
        </form>
        <?php if ($selectionError !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($selectionError, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="challenge-card">
    <div class="challenge-heading">
        <div>
            <p class="card-label">
                <?= htmlspecialchars(
                    $challengeName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <h2>
                <?= htmlspecialchars(
                    $challengeSchedule,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
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
        aria-valuemax="100"
        aria-valuenow="<?= $progressPercent ?>"
    >
        <div
            class="progress-fill"
            style="width: <?= $progressPercent ?>%;"
        ></div>
    </div>

    <p class="progress-caption">
        <?= htmlspecialchars($progressCaption, ENT_QUOTES, 'UTF-8') ?>
    </p>
</section>

<section
    class="stats-grid"
    aria-label="Progress statistics"
>
    <article class="stat-card stat-pink">
        <span class="stat-label">Weight Change</span>

        <strong>
            <?= $weightChange !== null
                ? number_format(
                    abs($weightChange),
                    1
                ) . ' lb'
                : '—' ?>
        </strong>

        <span class="stat-note">
            <?php if ($weightChange === null): ?>
                More measurements needed
            <?php elseif ($weightChange > 0): ?>
                Lost this challenge
            <?php elseif ($weightChange < 0): ?>
                Gained this challenge
            <?php else: ?>
                Holding steady
            <?php endif; ?>
        </span>
    </article>

    <article class="stat-card stat-purple">
        <span class="stat-label">Waist Change</span>

        <strong>
            <?= $waistChange !== null
                ? number_format(
                    abs($waistChange),
                    1
                ) . ' in'
                : '—' ?>
        </strong>

        <span class="stat-note">
            <?php if ($waistChange === null): ?>
                More measurements needed
            <?php elseif ($waistChange > 0): ?>
                Lost this challenge
            <?php elseif ($waistChange < 0): ?>
                Gained this challenge
            <?php else: ?>
                Holding steady
            <?php endif; ?>
        </span>
    </article>

    <article class="stat-card stat-mint">
        <span class="stat-label">Activities</span>

        <strong><?= $workoutCount ?></strong>

        <span class="stat-note">
            <?= $totalMovementMinutes ?> min movement
        </span>
    </article>

    <article class="stat-card stat-blue">
        <span class="stat-label">Check-Ins</span>

        <strong><?= count($checkIns) ?></strong>

        <span class="stat-note">
            Optional progress entries
        </span>
    </article>
</section>

<section class="progress-panel">
    <div class="section-heading">
        <div>
            <p class="card-label">Weight trend</p>
            <h2>Your measurements</h2>
        </div>
    </div>

    <?php if ($chartPoints === []): ?>
        <p class="empty-state">
            Add a weight during a check-in to begin your trend chart.
        </p>
    <?php else: ?>
        <div class="weight-chart">
            <svg
                viewBox="0 0 320 150"
                role="img"
                aria-label="Weight trend chart"
            >
                <?php if (count($chartPoints) > 1): ?>
                    <polyline
                        points="<?= htmlspecialchars(
                            implode(
                                ' ',
                                array_column(
                                    $chartPoints,
                                    'coordinates'
                                )
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="4"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                <?php endif; ?>

                <?php foreach ($chartPoints as $point): ?>
                    <circle
                        cx="<?= $point['x'] ?>"
                        cy="<?= $point['y'] ?>"
                        r="6"
                        fill="currentColor"
                    >
                        <title>
                            <?= htmlspecialchars(
                                $point['date'] .
                                ': ' .
                                number_format(
                                    $point['weight'],
                                    1
                                ) .
                                ' lb',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </title>
                    </circle>
                <?php endforeach; ?>
            </svg>
        </div>

        <div class="chart-summary">
            <span>
                Start:
                <strong>
                    <?= number_format(
                        $startingWeight,
                        1
                    ) ?> lb
                </strong>
            </span>

            <span>
                Latest:
                <strong>
                    <?= number_format(
                        $latestWeight,
                        1
                    ) ?> lb
                </strong>
            </span>
        </div>
    <?php endif; ?>
</section>

<section class="progress-panel">
    <div class="section-heading">
        <div>
            <p class="card-label">History</p>
            <h2>Recent check-ins</h2>
        </div>
    </div>

    <?php if ($recentCheckIns === []): ?>
        <p class="empty-state">
            Your check-in history will appear here.
        </p>
    <?php else: ?>
        <div class="check-in-history">
            <?php foreach ($recentCheckIns as $checkIn): ?>
                <?php
                $displayMood = $checkIn['display_mood'];

                $displayMoodLabel =
                    $displayMood !== null
                    ? (
                        $moodLabels[$displayMood]
                        ?? ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $displayMood
                            )
                        )
                    )
                    : 'No mood recorded';
                ?>

                <article class="history-entry">
                    <div>
                        <strong>
                            <?= (
                                new DateTimeImmutable(
                                    $checkIn['check_in_date']
                                )
                            )->format('M j, Y') ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(
                                $displayMoodLabel,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>
                    </div>

                    <div class="history-measurements">
                        <span>
                            Weight:

                            <strong>
                                <?= $checkIn['weight'] !== null
                                    ? number_format(
                                        (float) $checkIn['weight'],
                                        1
                                    ) . ' lb'
                                    : '—' ?>
                            </strong>
                        </span>

                        <span>
                            Waist:

                            <strong>
                                <?= $checkIn['waist'] !== null
                                    ? number_format(
                                        (float) $checkIn['waist'],
                                        1
                                    ) . ' in'
                                    : '—' ?>
                            </strong>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
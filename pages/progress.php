<?php

$pageTitle = 'Progress | PushMeToday45';
$activePage = 'progress';

require_once dirname(__DIR__) . '/includes/recap.php';

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


$recapCards = [];
$recapSummary = [];
$recapIsComplete = $challenge !== null &&
    in_array($challenge['status'], ['completed', 'archived'], true);
$showRecap = $challenge !== null && (
    $recapIsComplete || ($_GET['recap'] ?? '') === '1'
);

if ($showRecap) {
    $recap = challenge_recap_data((int) $challenge['id']);
    $activityLabels = [
        'walk' => 'Walking', 'walking' => 'Walking',
        'run' => 'Running', 'running' => 'Running',
        'strength' => 'Strength', 'cycling' => 'Cycling',
        'bike' => 'Cycling', 'yoga' => 'Yoga',
        'pilates' => 'Pilates', 'swimming' => 'Swimming',
        'stretching' => 'Stretching', 'dance' => 'Dance',
        'dancing' => 'Dancing', 'treadmill' => 'Treadmill',
        'other' => 'Other',
    ];
    $readable = static fn (string $value): string =>
        ucfirst(str_replace('_', ' ', $value));
    $topActivities = array_map(
        static fn (string $value): string => $activityLabels[$value] ?? $readable($value),
        $recap['top_activities']
    );
    $topMoods = array_map(
        static fn (string $value): string => $moodLabels[$value] ?? $readable($value),
        $recap['top_moods']
    );
    $addCard = static function (
        string $title, string $number, string $description, string $color
    ) use (&$recapCards): void {
        $recapCards[] = compact('title', 'number', 'description', 'color');
    };
    $addCard(
        $recapIsComplete ? 'Your challenge, wrapped.' : 'Your story so far.',
        (string) $recap['logged_days'],
        'Dates with saved entries. ' . $challengeSchedule .
        ($recapIsComplete ? '. Here is what you recorded.' : '. This is a live preview; your challenge is still open.'),
        'purple'
    );
    $addCard(
        'Every activity added up.', (string) $recap['activity_count'],
        'Activities logged across ' . $recap['active_days'] .
        ' calendar days. Rest-day entries are excluded.', 'pink'
    );
    $minutes = $recap['movement_minutes'];
    $durationDescription = $recap['duration_entry_count'] > 0
        ? intdiv($minutes, 60) . ' hours and ' . ($minutes % 60) .
            ' minutes of recorded movement. Activities without a duration add no minutes.'
        : 'No activity durations were recorded. Your logged activities still count.';
    $addCard('You made time to move.', (string) $minutes . ' min', $durationDescription, 'mint');
    $addCard(
        count($topActivities) > 1 ? 'Your top activities tied.' : 'Your most logged activity.',
        $topActivities !== [] ? implode(' + ', $topActivities) : 'Still to come',
        $topActivities !== []
            ? 'Logged ' . $recap['top_activity_count'] . ' times' .
                (count($topActivities) > 1 ? ' each.' : '.')
            : 'No movement activities were recorded for this challenge.',
        'blue'
    );
    $addCard(
        'You kept coming back.', (string) $recap['longest_activity_streak'] . ' days',
        'Your longest run of consecutive calendar days with a logged movement activity. Rest days do not extend this streak.',
        'purple'
    );
    if ($recap['busiest_dates'] !== []) {
        $busiestDateLabels = array_map(
            static fn (string $date): string => challenge_calendar_date($date)->format('M j'),
            $recap['busiest_dates']
        );
        $addCard(
            count($busiestDateLabels) > 1 ? 'Your biggest movement days tied.' : 'Your biggest movement day.',
            (string) $recap['busiest_minutes'] . ' min',
            implode(', ', $busiestDateLabels) .
                (count($busiestDateLabels) > 1 ? ' — each had this many recorded minutes.' : ' — your highest recorded daily total.'),
            'mint'
        );
    }
    $addCard(
        'You checked in with yourself.', (string) $recap['mood_count'],
        $topMoods !== []
            ? 'Mood entries. Most logged: ' . implode(' + ', $topMoods) .
                ' (' . $recap['top_mood_count'] . (count($topMoods) > 1 ? ' each).' : ').')
            : 'Mood entries. No moods were recorded for this challenge.',
        'pink'
    );
    $addCard(
        'You made room for your thoughts.', (string) $recap['note_count'],
        'Standalone notes saved. Notes attached to activities and moods are not included in this count.',
        'blue'
    );
    foreach (['weight' => 'lb', 'waist' => 'in'] as $field => $unit) {
        $measurement = $recap['measurements'][$field];
        if ($measurement['count'] >= 2) {
            $change = $measurement['change'];
            $direction = $change < 0 ? 'decrease' : ($change > 0 ? 'increase' : 'change');
            $description = number_format($measurement['first'], 1) . ' → ' .
                number_format($measurement['last'], 1) . ' ' . $unit . '. ' .
                challenge_calendar_date($measurement['first_date'])->format('M j') . ' to ' .
                challenge_calendar_date($measurement['last_date'])->format('M j') .
                '. First to latest recorded measurement.';
            $value = number_format(abs($change), 1) . ' ' . $unit . ' ' . $direction;
        } else {
            $value = 'More entries needed';
            $description = 'Two measurements on different dates are needed to show a change.';
        }
        $addCard(ucfirst($field) . ', in perspective.', $value, $description, 'purple');
    }
    $recapSummary = [
        'Dates with entries' => (string) $recap['logged_days'],
        'Movement activities' => (string) $recap['activity_count'],
        'Recorded movement' => (string) $minutes . ' min',
        'Longest activity streak' => (string) $recap['longest_activity_streak'] . ' days',
        'Mood entries' => (string) $recap['mood_count'],
        'Standalone notes' => (string) $recap['note_count'],
    ];
    $addCard(
        $recapIsComplete ? 'A chapter worth keeping.' : 'Your story is still growing.',
        'Your summary',
        'These highlights reflect what you recorded. You can revisit them from challenge history.',
        'mint'
    );
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


<?php if ($challenge !== null): ?>
    <section class="progress-panel">
        <p class="card-label"><?= $recapIsComplete ? 'Your challenge recap' : 'Your story so far' ?></p>
        <a class="recap-launch" href="/?page=progress&amp;challenge_id=<?= (int) $challenge['id'] ?>&amp;recap=1#challenge-recap">
            <?= $recapIsComplete ? 'View Recap' : 'Preview Recap' ?>
        </a>
        <?php if (!$recapIsComplete): ?>
            <p class="progress-caption">A live preview of your recorded entries. Your challenge stays open.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($recapCards !== []): ?>
    <section class="challenge-recap" id="challenge-recap" aria-label="Challenge recap">
        <p class="card-label"><?= $recapIsComplete ? 'Challenge Wrapped' : 'Live recap preview' ?></p>
        <div id="recap-slides">
            <?php foreach ($recapCards as $index => $card): ?>
                <article class="recap-card recap-card--<?= htmlspecialchars($card['color'], ENT_QUOTES, 'UTF-8') ?>"
                         aria-labelledby="recap-title-<?= $index ?>">
                    <p class="recap-eyebrow">Your challenge · <?= $index + 1 ?> / <?= count($recapCards) ?></p>
                    <h2 id="recap-title-<?= $index ?>" tabindex="-1"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <span class="recap-number"><?= htmlspecialchars($card['number'], ENT_QUOTES, 'UTF-8') ?></span>
                    <p class="recap-description"><?= htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($index === count($recapCards) - 1): ?>
                        <dl class="recap-summary">
                            <?php foreach ($recapSummary as $label => $value): ?>
                                <div>
                                    <dt><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
                                    <dd><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                        <p><a href="/?page=home">Back to Home →</a></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <nav class="recap-controls" aria-label="Recap cards" hidden style="display: none;">
            <button class="recap-button" type="button" id="recap-back">Back</button>
            <span class="recap-counter" id="recap-counter" aria-live="polite" aria-atomic="true"></span>
            <button class="recap-button" type="button" id="recap-next">Next</button>
        </nav>
        <noscript><p>All recap cards are displayed below each other because JavaScript is disabled.</p></noscript>
    </section>
    <script>
    (() => {
        const root = document.getElementById('challenge-recap');
        if (!root) return;
        const slides = Array.from(root.querySelectorAll('.recap-card'));
        const back = document.getElementById('recap-back');
        const next = document.getElementById('recap-next');
        const counter = document.getElementById('recap-counter');
        let current = 0;
        const show = (index, focus = true) => {
            current = Math.max(0, Math.min(slides.length - 1, index));
            slides.forEach((slide, position) => { slide.hidden = position !== current; });
            back.disabled = current === 0;
            next.textContent = current === slides.length - 1 ? 'Replay' : 'Next';
            counter.textContent = `${current + 1} of ${slides.length}`;
            if (focus) slides[current].querySelector('h2').focus({preventScroll: true});
        };
        back.addEventListener('click', () => show(current - 1));
        next.addEventListener('click', () => show(current === slides.length - 1 ? 0 : current + 1));
        root.addEventListener('keydown', event => {
            if (event.target.closest('input, select, textarea')) return;
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                event.preventDefault();
                show(current + (event.key === 'ArrowRight' ? 1 : -1));
            }
        });
        let touchStart = null;
        root.addEventListener('touchstart', event => {
            touchStart = event.touches.length === 1
                ? {x: event.touches[0].clientX, y: event.touches[0].clientY} : null;
        }, {passive: true});
        root.addEventListener('touchend', event => {
            if (!touchStart || event.changedTouches.length !== 1) return;
            const dx = event.changedTouches[0].clientX - touchStart.x;
            const dy = event.changedTouches[0].clientY - touchStart.y;
            touchStart = null;
            if (Math.abs(dx) > 65 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                show(current + (dx < 0 ? 1 : -1));
            }
        }, {passive: true});
        show(0, false);
        const controls = root.querySelector('.recap-controls');
        controls.hidden = false;
        controls.style.display = 'flex';
    })();
    </script>
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
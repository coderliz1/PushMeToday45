<?php

$pageTitle = 'I Need Help | PushMeToday45';
$activePage = 'sos';

require_once dirname(__DIR__) . '/includes/functions.php';

$allowedCategories = [
    'cheat',
    'lazy',
    'struggling',
    'scale',
    'results',
    'motivate',
];

$selectedCategory = trim(
    (string) ($_POST['sos_category'] ?? '')
);

if (!in_array($selectedCategory, $allowedCategories, true)) {
    $selectedCategory = '';
}

$allowedCheatReasons = [
    'hungry',
    'craving',
    'emotional',
    'unsure',
];

$cheatReason = trim(
    (string) ($_POST['cheat_reason'] ?? '')
);

if (!in_array($cheatReason, $allowedCheatReasons, true)) {
    $cheatReason = '';
}

$cravingDetail = trim(
    (string) ($_POST['craving_detail'] ?? '')
);

$cravingDetail = substr($cravingDetail, 0, 255);

$allowedLazySteps = [
    'shoes_on',
    'start_timer',
    'finish_workout',
    'another_five',
    'stopped',
];

$lazyStep = trim(
    (string) ($_POST['lazy_step'] ?? '')
);

if (!in_array($lazyStep, $allowedLazySteps, true)) {
    $lazyStep = '';
}

$allowedStruggleReasons = [
    'physically_tired',
    'overwhelmed',
    'bad_mood',
    'food_sideways',
    'missed_workout',
    'everything',
];

$struggleReason = trim(
    (string) ($_POST['struggle_reason'] ?? '')
);

if (
    !in_array(
        $struggleReason,
        $allowedStruggleReasons,
        true
    )
) {
    $struggleReason = '';
}

$cheatInterventions = [
    'hungry' => [
        'push' => 'If you are genuinely hungry, eating is not cheating. The goal is to make a choice that actually satisfies you.',
        'action' => 'Choose a reasonable meal or snack with protein and fiber.',
        'why' => 'A satisfying choice reduces the chance that hunger turns into uncontrolled snacking later.',
        'fact' => 'Protein and fiber generally digest more slowly and can help you feel satisfied longer.',
    ],

    'craving' => [
        'push' => 'You do not have to defeat the craving forever. You only need to interrupt it for the next ten minutes.',
        'action' => 'Leave the kitchen and start a 10-minute craving timer.',
        'why' => 'Cravings often rise and fall like a wave. Creating a pause weakens the automatic habit loop.',
        'fact' => 'A craving can feel urgent without being permanent. Delaying the response gives the intensity time to change.',
    ],

    'emotional' => [
        'push' => 'This may be a need for relief, not food. Let’s change what your brain is responding to.',
        'action' => 'Change rooms and walk for five minutes.',
        'why' => 'Changing your environment interrupts the cue that is pushing you toward automatic eating.',
        'fact' => 'Stress can increase reward-seeking behavior, which can make highly enjoyable foods feel harder to resist.',
    ],

    'unsure' => [
        'push' => 'You do not need to figure everything out this second. Create a short pause and check again.',
        'action' => 'Drink some water, leave the food area, and wait ten minutes.',
        'why' => 'A deliberate pause separates an automatic reaction from a conscious decision.',
        'fact' => 'Physical hunger often builds gradually, while a craving is more likely to feel sudden and highly specific.',
    ],
];


$strugglePlans = [
    'physically_tired' => [
        'push' => 'Your body may need a lower gear today, not punishment.',
        'mission' => [
            'Drink a full glass of water.',
            'Eat one reasonable next meal.',
            'Do five minutes of gentle movement or stretching.',
            'Give yourself permission to rest afterward.',
        ],
        'why' => 'Scaling the plan down protects consistency without ignoring what your body is telling you.',
        'fact' => 'Fatigue can reduce decision-making and self-control, so a simpler plan is often easier to follow.',
    ],

    'overwhelmed' => [
        'push' => 'You do not need to solve the whole day. Handle the next small thing.',
        'mission' => [
            'Drink some water.',
            'Choose one task that takes under ten minutes.',
            'Eat one reasonable next meal.',
            'Take five slow breaths before choosing what comes next.',
        ],
        'why' => 'Reducing the number of decisions lowers mental overload and makes action feel manageable.',
        'fact' => 'Breaking a large problem into smaller actions can reduce avoidance and improve follow-through.',
    ],

    'bad_mood' => [
        'push' => 'A bad mood is real, but it does not get to make every decision today.',
        'mission' => [
            'Change rooms or step outside.',
            'Move for ten minutes.',
            'Drink some water.',
            'Do one thing that makes tomorrow easier.',
        ],
        'why' => 'Changing your environment and moving your body interrupts the emotional loop.',
        'fact' => 'Even brief physical activity can produce an immediate improvement in mood for many people.',
    ],

    'food_sideways' => [
        'push' => 'One meal did not ruin anything. The next choice still belongs to you.',
        'mission' => [
            'Do not skip your next meal as punishment.',
            'Choose a normal, reasonable next meal.',
            'Drink some water.',
            'Continue the day without trying to “make up” for it.',
        ],
        'why' => 'Returning to your normal plan stops one imperfect choice from becoming an all-day spiral.',
        'fact' => 'Long-term progress reflects repeated patterns, not one meal or snack.',
    ],

    'missed_workout' => [
        'push' => 'The planned workout may be gone, but the entire day is not.',
        'mission' => [
            'Do ten minutes of any movement.',
            'Stretch one tight area.',
            'Prepare what you need for the next workout.',
            'Count the recovery, then move on.',
        ],
        'why' => 'A smaller backup plan protects the habit even when the original plan fails.',
        'fact' => 'Consistency is easier to maintain when a habit has a smaller fallback version.',
    ],

    'everything' => [
        'push' => 'Today does not need to become impressive. It only needs to stop getting worse.',
        'mission' => [
            'Drink a full glass of water.',
            'Eat one reasonable next meal.',
            'Move gently for five minutes.',
            'Check in, then let today be enough.',
        ],
        'why' => 'A minimum viable day replaces perfection with a few actions you can still control.',
        'fact' => 'Completing small actions can rebuild self-efficacy—the belief that you can influence what happens next.',
    ],
];

$currentStrugglePlan = null;

if (
    $selectedCategory === 'struggling' &&
    $struggleReason !== ''
) {
    $currentStrugglePlan = $strugglePlans[$struggleReason];
}



$currentIntervention = null;

if (
    $selectedCategory === 'cheat' &&
    $cheatReason !== ''
) {
    $currentIntervention = $cheatInterventions[$cheatReason];
}


$latestScaleDate = null;
$latestScaleChange = null;
$sevenDayWeightTrend = null;
$scaleMeasurementCount = 0;
$cycleMayAffectScale = false;

if ($selectedCategory === 'scale') {
    $scaleStatement = db()->query(
        'SELECT
            check_in_date,
            weight,
            period_status
         FROM check_ins
         WHERE weight IS NOT NULL
         ORDER BY check_in_date DESC
         LIMIT 60'
    );

    $scaleMeasurements = $scaleStatement->fetchAll();
    $scaleMeasurementCount = count($scaleMeasurements);

    if ($scaleMeasurementCount >= 1) {
        $latestMeasurement = $scaleMeasurements[0];
        $latestScaleDate = $latestMeasurement['check_in_date'];

        $cycleMayAffectScale = in_array(
            $latestMeasurement['period_status'] ?? '',
            ['started', 'ongoing'],
            true
        );
    }

    if ($scaleMeasurementCount >= 2) {
        $latestScaleChange =
            (float) $scaleMeasurements[0]['weight'] -
            (float) $scaleMeasurements[1]['weight'];
    }

    $timezone = new DateTimeZone('America/New_York');
    $today = new DateTimeImmutable('today', $timezone);
    $sevenDayStart = $today->modify('-6 days');

    $sevenDayMeasurements = array_values(
        array_filter(
            $scaleMeasurements,
            static function (array $measurement) use (
                $sevenDayStart,
                $today,
                $timezone
            ): bool {
                $measurementDate = new DateTimeImmutable(
                    $measurement['check_in_date'],
                    $timezone
                );

                return
                    $measurementDate >= $sevenDayStart &&
                    $measurementDate <= $today;
            }
        )
    );

    if (count($sevenDayMeasurements) >= 2) {
        $newestSevenDayWeight =
            (float) $sevenDayMeasurements[0]['weight'];

        $oldestSevenDayMeasurement =
            $sevenDayMeasurements[
                count($sevenDayMeasurements) - 1
            ];

        $oldestSevenDayWeight =
            (float) $oldestSevenDayMeasurement['weight'];

        $sevenDayWeightTrend =
            $newestSevenDayWeight - $oldestSevenDayWeight;
    }
}


require dirname(__DIR__) . '/includes/header.php';

?>


<?php if ($selectedCategory === 'scale'): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Zoom Out</p>
        <h1>The scale does not get the final word.</h1>

        <?php if ($latestScaleDate !== null): ?>
            <p class="sos-introduction">
                Latest measurement:
                <?= htmlspecialchars(
                    date(
                        'M j, Y',
                        strtotime($latestScaleDate)
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>
    </section>

    <section class="sos-scale-grid">
        <article class="sos-scale-stat sos-scale-latest">
            <span>Latest change</span>

            <strong>
                <?php if ($latestScaleChange !== null): ?>
                    <?= $latestScaleChange > 0 ? '+' : '' ?>
                    <?= number_format($latestScaleChange, 1) ?> lb
                <?php else: ?>
                    —
                <?php endif; ?>
            </strong>

            <small>
                <?= $latestScaleChange !== null
                    ? 'Compared with your previous measurement'
                    : 'Two measurements are needed' ?>
            </small>
        </article>

        <article class="sos-scale-stat sos-scale-trend">
            <span>7-day trend</span>

            <strong>
                <?php if ($sevenDayWeightTrend !== null): ?>
                    <?= $sevenDayWeightTrend > 0 ? '+' : '' ?>
                    <?= number_format($sevenDayWeightTrend, 1) ?> lb
                <?php else: ?>
                    —
                <?php endif; ?>
            </strong>

            <small>
                <?= $sevenDayWeightTrend !== null
                    ? 'Oldest to newest measurement this week'
                    : 'Not enough measurements this week' ?>
            </small>
        </article>
    </section>

    <section class="sos-intervention-card sos-ai-card">
        <p class="card-label">Your Push</p>

        <p class="sos-intervention-text">
            A single scale reading is information—not a verdict.
            Look at the direction over time, not one noisy number.
        </p>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">🧠 Why the Number Moves</p>

        <p class="sos-intervention-text">
            Daily weight can shift because of water, sodium,
            carbohydrate intake, digestion, bowel movements,
            soreness after exercise, and hormonal changes.
        </p>

        <?php if ($cycleMayAffectScale): ?>
            <p class="sos-cycle-note">
                Your latest check-in also shows that your period
                started or is ongoing, which may contribute to
                temporary water-weight changes.
            </p>
        <?php endif; ?>
    </section>

    <section class="sos-intervention-card sos-fact-card">
        <p class="card-label">💡 Real-Ass Fact</p>

        <p class="sos-intervention-text">
            Short-term scale changes do not automatically represent
            body-fat gain or loss. Trends across multiple measurements
            are more useful than one isolated reading.
        </p>
    </section>

    <div class="sos-intervention-actions">
        <a class="sos-good-button" href="/?page=home">
            ✅ I’m Good Now
        </a>

        <a class="sos-secondary-button" href="/?page=progress">
            View My Progress
        </a>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>


<?php if ($currentStrugglePlan !== null): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Minimum Viable Day</p>
        <h1>We are saving the day—not perfecting it.</h1>
    </section>

    <section class="sos-intervention-card sos-ai-card">
        <p class="card-label">Your Push</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentStrugglePlan['push'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-action-card">
        <p class="card-label">⚡ Today’s Mission</p>

        <ul class="sos-mission-list">
            <?php foreach (
                $currentStrugglePlan['mission'] as $missionItem
            ): ?>
                <li>
                    <?= htmlspecialchars(
                        $missionItem,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">🧠 Why This Works</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentStrugglePlan['why'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-fact-card">
        <p class="card-label">💡 Real-Ass Fact</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentStrugglePlan['fact'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <div class="sos-intervention-actions">
        <a class="sos-good-button" href="/?page=home">
            ✅ I Have a Plan
        </a>

        <a class="sos-secondary-button" href="/?page=sos">
            Return to SOS
        </a>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>



<?php if (
    $selectedCategory === 'struggling' &&
    $struggleReason === ''
): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Save the Day</p>
        <h1>What kind of struggle is this?</h1>

        <p class="sos-introduction">
            Pick the answer that feels closest right now.
        </p>
    </section>

    <form
        class="sos-follow-up-list"
        method="post"
        action="/?page=sos"
    >
        <input
            type="hidden"
            name="sos_category"
            value="struggling"
        >

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="struggle_reason"
            value="physically_tired"
        >
            <span>😴</span>
            <strong>Physically tired</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="struggle_reason"
            value="overwhelmed"
        >
            <span>🧠</span>
            <strong>Mentally overwhelmed</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="struggle_reason"
            value="bad_mood"
        >
            <span>😔</span>
            <strong>Bad mood</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="struggle_reason"
            value="food_sideways"
        >
            <span>🍕</span>
            <strong>Food went sideways</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="struggle_reason"
            value="missed_workout"
        >
            <span>🏋️</span>
            <strong>Missed workout</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="struggle_reason"
            value="everything"
        >
            <span>💩</span>
            <strong>Everything</strong>
        </button>
    </form>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>



<?php if (
    $selectedCategory === 'lazy' &&
    $lazyStep === ''
): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Lower the Barrier</p>
        <h1>Don’t commit to the workout.</h1>

        <p class="sos-introduction">
            We are making this ridiculously easy.
        </p>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">Your Only Job</p>

        <p class="sos-big-instruction">
            Put your shoes on.
        </p>

        <p class="sos-intervention-text">
            That’s it. You are not agreeing to a full workout.
            You are only putting on your shoes.
        </p>
    </section>

    <form method="post" action="/?page=sos">
        <input
            type="hidden"
            name="sos_category"
            value="lazy"
        >

        <button
            class="sos-good-button"
            type="submit"
            name="lazy_step"
            value="shoes_on"
        >
            👟 Shoes Are On
        </button>
    </form>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>

<?php if (
    $selectedCategory === 'lazy' &&
    $lazyStep === 'shoes_on'
): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Step One Complete</p>
        <h1>Shoes are on. Nice.</h1>

        <p class="sos-introduction">
            Now give yourself only five minutes.
        </p>
    </section>

    <section class="sos-intervention-card sos-action-card">
        <p class="card-label">⚡ Do This Right Now</p>

        <p class="sos-big-instruction">
            Move for five minutes.
        </p>

        <p class="sos-intervention-text">
            Walk, stretch, use the treadmill, or begin the first
            part of your workout. You can stop after five minutes
            without calling the day a failure.
        </p>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">🧠 Why This Works</p>

        <p class="sos-intervention-text">
            Starting requires more mental effort than continuing.
            A tiny commitment lowers the barrier and creates momentum.
        </p>
    </section>

    <form method="post" action="/?page=sos">
        <input
            type="hidden"
            name="sos_category"
            value="lazy"
        >

        <button
            class="sos-good-button"
            type="submit"
            name="lazy_step"
            value="start_timer"
        >
            ⏱️ Start 5-Minute Timer
        </button>
    </form>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>

<?php if (
    $selectedCategory === 'lazy' &&
    in_array(
        $lazyStep,
        ['start_timer', 'another_five'],
        true
    )
): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Exit timer
        </a>

        <p class="eyebrow">Five-Minute Start</p>
        <h1>Just keep moving.</h1>

        <p class="sos-introduction">
            You do not need to make any decisions until the timer ends.
        </p>
    </section>

    <section class="sos-timer-card">
        <p class="card-label">Time Remaining</p>

        <div
            id="sos-countdown"
            class="sos-countdown"
            data-seconds="300"
            aria-live="polite"
        >
            05:00
        </div>

        <p id="sos-timer-message" class="sos-timer-message">
            Start moving. You only promised five minutes.
        </p>
    </section>

    <form
        id="sos-timer-outcomes"
        class="sos-timer-outcomes"
        method="post"
        action="/?page=sos"
        hidden
    >
        <input
            type="hidden"
            name="sos_category"
            value="lazy"
        >

        <button
            class="sos-good-button"
            type="submit"
            name="lazy_step"
            value="finish_workout"
        >
            🔥 Finish My Workout
        </button>

        <button
            class="sos-secondary-button"
            type="submit"
            name="lazy_step"
            value="another_five"
        >
            👍 Another Five Minutes
        </button>

        <button
            class="sos-secondary-button"
            type="submit"
            name="lazy_step"
            value="stopped"
        >
            🏁 I’m Stopping, but I Showed Up
        </button>
    </form>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>

<?php if (
    $selectedCategory === 'lazy' &&
    in_array(
        $lazyStep,
        ['finish_workout', 'stopped'],
        true
    )
): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">You Showed Up</p>

        <?php if ($lazyStep === 'finish_workout'): ?>
            <h1>Momentum unlocked. 🔥</h1>

            <p class="sos-introduction">
                You did not wait for motivation. You created it by starting.
            </p>
        <?php else: ?>
            <h1>Five minutes still counts. 🏁</h1>

            <p class="sos-introduction">
                You kept the promise you made. That is a successful day,
                even if you stop here.
            </p>
        <?php endif; ?>
    </section>

    <section class="sos-intervention-card sos-fact-card">
        <p class="card-label">💡 Real-Ass Fact</p>

        <?php if ($lazyStep === 'finish_workout'): ?>
            <p class="sos-intervention-text">
                Action often creates motivation after you begin.
                Motivation does not always need to come first.
            </p>
        <?php else: ?>
            <p class="sos-intervention-text">
                Completing a small commitment builds self-trust.
                Five intentional minutes are better than abandoning
                the day completely.
            </p>
        <?php endif; ?>
    </section>

    <div class="sos-intervention-actions">
        <a class="sos-good-button" href="/?page=home">
            ✅ I’m Good Now
        </a>

        <a class="sos-secondary-button" href="/?page=sos">
            Return to SOS
        </a>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>


<?php if ($currentIntervention !== null): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Your Next Five Minutes</p>
        <h1>Okay. Here’s the move.</h1>

        <?php if ($cravingDetail !== ''): ?>
            <p class="sos-selected-detail">
                You mentioned:
                <strong>
                    <?= htmlspecialchars(
                        $cravingDetail,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>
        <?php endif; ?>
    </section>

    <section class="sos-intervention-card sos-ai-card">
        <p class="card-label">Your Push</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentIntervention['push'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-action-card">
        <p class="card-label">⚡ Do This Right Now</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentIntervention['action'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">🧠 Why This Works</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentIntervention['why'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-fact-card">
        <p class="card-label">💡 Real-Ass Fact</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentIntervention['fact'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <div class="sos-intervention-actions">
        <form method="post" action="/?page=sos">
            <input
                type="hidden"
                name="sos_category"
                value="cheat"
            >

            <input
                type="hidden"
                name="cheat_reason"
                value="<?= htmlspecialchars(
                    $cheatReason,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="craving_detail"
                value="<?= htmlspecialchars(
                    $cravingDetail,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <button
                class="sos-secondary-button"
                type="submit"
                name="response_action"
                value="another"
            >
                🎲 Give Me Another Trick
            </button>

            <button
                class="sos-secondary-button"
                type="submit"
                name="response_action"
                value="teach"
            >
                🧠 Teach Me Something
            </button>
        </form>

        <a class="sos-good-button" href="/?page=home">
            ✅ I’m Good Now
        </a>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>

<?php if ($selectedCategory === 'cheat'): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Craving Intervention</p>
        <h1>What’s actually happening?</h1>

        <p class="sos-introduction">
            Pick the answer that feels closest right now.
        </p>
    </section>

    <form
        class="sos-follow-up-list"
        method="post"
        action="/?page=sos"
    >
        <input
            type="hidden"
            name="sos_category"
            value="cheat"
        >

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="cheat_reason"
            value="hungry"
        >
            <span>🍽️</span>
            <strong>I’m genuinely hungry</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="cheat_reason"
            value="craving"
        >
            <span>🍫</span>
            <strong>I’m specifically craving something</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="cheat_reason"
            value="emotional"
        >
            <span>😵</span>
            <strong>I’m stressed, bored, or emotional</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="cheat_reason"
            value="unsure"
        >
            <span>🤷</span>
            <strong>I don’t know</strong>
        </button>

        <div class="sos-detail-field">
            <label for="craving_detail">
                What are you craving?
                <small>Optional</small>
            </label>

            <input
                id="craving_detail"
                name="craving_detail"
                type="text"
                maxlength="255"
                placeholder="Example: chocolate, chips, pizza..."
            >
        </div>
    </form>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>

<section class="sos-heading">
    <a class="sos-back-link" href="/?page=home">← Back</a>

    <p class="eyebrow">SOS Support</p>
    <h1>I Need Help</h1>

    <p class="sos-introduction">
        Pick what you’re feeling right now.
    </p>
</section>

<form
    class="sos-choice-list"
    method="post"
    action="/?page=sos"
>
    <button
        class="sos-choice sos-choice-pink"
        type="submit"
        name="sos_category"
        value="cheat"
    >
        <span class="sos-choice-icon">🍪</span>

        <span class="sos-choice-text">
            <strong>I'm About to Cheat</strong>
            <small>Help me handle this craving.</small>
        </span>

        <span class="sos-choice-arrow">→</span>
    </button>

    <button
        class="sos-choice sos-choice-purple"
        type="submit"
        name="sos_category"
        value="lazy"
    >
        <span class="sos-choice-icon">🛋️</span>

        <span class="sos-choice-text">
            <strong>I'm Feeling Lazy</strong>
            <small>Help me get started.</small>
        </span>

        <span class="sos-choice-arrow">→</span>
    </button>

    <button
        class="sos-choice sos-choice-blue"
        type="submit"
        name="sos_category"
        value="struggling"
    >
        <span class="sos-choice-icon">😩</span>

        <span class="sos-choice-text">
            <strong>I'm Struggling Today</strong>
            <small>Help me save the day.</small>
        </span>

        <span class="sos-choice-arrow">→</span>
    </button>

    <button
        class="sos-choice sos-choice-mint"
        type="submit"
        name="sos_category"
        value="scale"
    >
        <span class="sos-choice-icon">😡</span>

        <span class="sos-choice-text">
            <strong>Scale Pissed Me Off</strong>
            <small>Help me understand the number.</small>
        </span>

        <span class="sos-choice-arrow">→</span>
    </button>

    <button
        class="sos-choice sos-choice-peach"
        type="submit"
        name="sos_category"
        value="results"
    >
        <span class="sos-choice-icon">📈</span>

        <span class="sos-choice-text">
            <strong>I'm Not Seeing Results</strong>
            <small>Show me what is actually changing.</small>
        </span>

        <span class="sos-choice-arrow">→</span>
    </button>

    <button
        class="sos-choice sos-choice-rose"
        type="submit"
        name="sos_category"
        value="motivate"
    >
        <span class="sos-choice-icon">❤️</span>

        <span class="sos-choice-text">
            <strong>Just Motivate Me</strong>
            <small>Give me the push I need.</small>
        </span>

        <span class="sos-choice-arrow">→</span>
    </button>
</form>

<section class="sos-reassurance">
    <p>It’s okay. You’re human. ♡</p>
</section>

<section class="sos-reminder-card">
    <p>
        You don’t have to do this perfectly.
        You just have to keep going. ♡
    </p>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
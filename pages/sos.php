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

$trickIndex = filter_var(
    $_POST['trick_index'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 0]]
);

if ($trickIndex === false) {
    $trickIndex = 0;
}

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

$allowedMotivationStyles = [
    'encourage',
    'coach',
    'laugh',
    'roast',
];

$motivationStyle = trim(
    (string) ($_POST['motivation_style'] ?? '')
);

if (
    !in_array(
        $motivationStyle,
        $allowedMotivationStyles,
        true
    )
) {
    $motivationStyle = '';
}

$allowedResponseActions = ['another', 'teach'];
$responseAction = trim(
    (string) ($_POST['response_action'] ?? '')
);

if (!in_array($responseAction, $allowedResponseActions, true)) {
    $responseAction = '';
}

/**
 * Ask OpenAI for one short, non-identifying SOS push.
 * Private measurements, notes, cycle information, and free-text
 * craving details are deliberately never passed to this function.
 */
function sos_generate_push(
    string $situation,
    string $fallback,
    string $responseAction = ''
): string {
    $requestType = $responseAction === 'teach'
        ? 'Teach one useful behavioral principle in plain language, then give one tiny action.'
        : 'Give a fresh, practical push toward one useful action in the next five minutes.';

    try {
        $aiPush = openai_generate_text(
            'You are the tiny accountability coach inside '
            . 'PushMeToday45. '
            . $requestType . ' '
            . 'Write two or three short sentences. Be supportive, '
            . 'specific, and conversational. Do not diagnose, shame, '
            . 'mention appearance, prescribe medical treatment, or '
            . 'make personalized medical claims. Return only the '
            . 'message with no heading or markdown.',
            'Non-identifying situation: ' . $situation,
            180
        );

        if ($aiPush !== '') {
            return $aiPush;
        }
    } catch (Throwable $exception) {
        error_log(
            'OpenAI SOS request failed: '
            . $exception->getMessage()
        );
    }

    return $fallback;
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

$cheatTricks = [
    'hungry' => [
        [
            'action' => 'Choose a reasonable meal or snack with protein and fiber.',
            'why' => 'A satisfying choice reduces the chance that hunger turns into uncontrolled snacking later.',
            'fact' => 'Protein and fiber generally digest more slowly and can help you feel satisfied longer.',
        ],
        [
            'action' => 'Put a balanced portion on a plate, sit down, and eat it without grazing from the package.',
            'why' => 'Creating a clear portion turns eating into a deliberate response to hunger instead of an open-ended snack loop.',
            'fact' => 'Eating directly from a large package can make the amount consumed harder to notice.',
        ],
        [
            'action' => 'Choose the reasonable option you actually want, then pause halfway through and check your hunger again.',
            'why' => 'A halfway pause gives fullness signals time to become easier to notice without treating food as forbidden.',
            'fact' => 'Physical fullness develops during a meal rather than appearing all at once with the first bite.',
        ],
    ],
    'craving' => [
        [
            'action' => 'Leave the kitchen and start a 10-minute craving timer.',
            'why' => 'Cravings often rise and fall like a wave. Creating a pause weakens the automatic habit loop.',
            'fact' => 'A craving can feel urgent without being permanent. Delaying the response gives its intensity time to change.',
        ],
        [
            'action' => 'Brush your teeth or chew mint gum, then move away from the food area.',
            'why' => 'A strong new taste and a change of location interrupt the sensory and environmental cues feeding the craving.',
            'fact' => 'Habits are strongly influenced by cues such as location, smell, sight, and routine.',
        ],
        [
            'action' => 'Decide on one reasonable portion, put it on a plate, and put the rest away before eating.',
            'why' => 'A planned portion replaces the all-or-nothing choice between total restriction and uncontrolled eating.',
            'fact' => 'Flexible, deliberate choices can be easier to sustain than treating a desired food as completely forbidden.',
        ],
    ],
    'emotional' => [
        [
            'action' => 'Change rooms and walk for five minutes.',
            'why' => 'Changing your environment interrupts the cue that is pushing you toward automatic eating.',
            'fact' => 'Stress can increase reward-seeking behavior, which can make highly enjoyable foods feel harder to resist.',
        ],
        [
            'action' => 'Name the feeling out loud, then write one sentence describing what you actually need right now.',
            'why' => 'Labeling an emotion creates distance from it and helps separate the feeling from the automatic urge to eat.',
            'fact' => 'Putting feelings into words can make an emotional reaction feel more manageable.',
        ],
        [
            'action' => 'Text or call someone, or step outside for five minutes before making a food decision.',
            'why' => 'Connection or a sensory reset can meet the need for relief without asking food to do that entire job.',
            'fact' => 'Boredom, loneliness, and stress can trigger eating even when physical hunger is low.',
        ],
    ],
    'unsure' => [
        [
            'action' => 'Drink some water, leave the food area, and wait ten minutes.',
            'why' => 'A deliberate pause separates an automatic reaction from a conscious decision.',
            'fact' => 'Physical hunger often builds gradually, while a craving is more likely to feel sudden and highly specific.',
        ],
        [
            'action' => 'Ask yourself whether several ordinary foods sound good or only one specific food does.',
            'why' => 'Broad interest in food often points toward hunger, while a very specific demand may be a craving.',
            'fact' => 'Hunger and cravings can overlap, so the goal is useful information—not a perfect diagnosis.',
        ],
        [
            'action' => 'Rate your hunger from 0 to 10, wait five minutes away from the kitchen, and rate it again.',
            'why' => 'Checking twice helps you notice whether the sensation is steadily building or changing with attention and environment.',
            'fact' => 'A short pause can make internal cues easier to notice before you choose what to do.',
        ],
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

    $struggleContexts = [
        'physically_tired' => 'The user feels physically tired and needs a minimum viable day.',
        'overwhelmed' => 'The user feels mentally overwhelmed and needs one manageable next step.',
        'bad_mood' => 'The user is in a bad mood and needs a small constructive reset.',
        'food_sideways' => 'The user had an imperfect food choice and needs help avoiding an all-or-nothing spiral.',
        'missed_workout' => 'The user missed a workout and needs a small fallback action without guilt.',
        'everything' => 'The whole day feels difficult and the user needs a compassionate minimum viable day.',
    ];

    $currentStrugglePlan['push'] = sos_generate_push(
        $struggleContexts[$struggleReason],
        $currentStrugglePlan['push'],
        $responseAction
    );
}


$motivationResponses = [
    'encourage' => [
        'push' => 'You do not need to feel fearless or perfectly motivated. You only need to make the next kind decision for yourself.',
        'action' => 'Choose one useful action that takes five minutes or less.',
        'why' => 'Small completed actions create evidence that you are still capable of moving forward.',
        'fact' => 'Self-efficacy grows when you successfully complete manageable actions—not only when you accomplish something huge.',
    ],

    'coach' => [
        'push' => 'You already know what needs to happen. Stop negotiating with the mood and complete the next rep, step, or reasonable choice.',
        'action' => 'Stand up and begin the next task before your brain starts another debate.',
        'why' => 'Immediate action interrupts avoidance and reduces the time available for excuses to grow.',
        'fact' => 'Motivation often follows action. Waiting to feel ready can keep the starting line moving farther away.',
    ],

    'laugh' => [
        'push' => 'Your brain has filed a formal complaint against effort. Management has reviewed it and denied the request.',
        'action' => 'Do five minutes of something useful purely to annoy your inner couch potato.',
        'why' => 'Humor can reduce the emotional weight of a task and make starting feel less threatening.',
        'fact' => 'A task can feel miserable before you begin and completely manageable once you are already doing it.',
    ],

    'roast' => [
        'push' => 'Respectfully, your goals cannot complete themselves while you sit there holding a committee meeting with your excuses.',
        'action' => 'Get up and do the smallest version of the thing you are avoiding.',
        'why' => 'A direct interruption can expose avoidance and redirect attention toward an action you can control.',
        'fact' => 'Your excuse does not need to disappear before you act. It can complain from the passenger seat.',
    ],
];

$currentMotivation = null;

if (
    $selectedCategory === 'motivate' &&
    $motivationStyle !== ''
) {
    /*
     * Start with the existing local response.
     * This remains available if OpenAI cannot be reached.
     */
    $currentMotivation =
        $motivationResponses[$motivationStyle];

    $motivationStyleDescriptions = [
        'encourage' => 'Warm, reassuring, and encouraging.',
        'coach' => 'Direct, energetic, and challenging like a coach.',
        'laugh' => 'Funny, playful, and supportive.',
        'roast' => 'A playful roast that is bold but never cruel.',
    ];

    try {
        $aiPush = openai_generate_text(
            'You are the tiny accountability coach inside '
            . 'PushMeToday45. Write a fresh motivational push that '
            . 'helps the user take one useful action in the next five '
            . 'minutes. Keep it to two or three short sentences. '
            . 'Do not diagnose, shame, insult appearance, mention '
            . 'weight, or make medical claims. Return only the '
            . 'motivational message with no title or markdown.',
            'Requested motivation style: '
            . $motivationStyleDescriptions[$motivationStyle],
            180
        );

        if ($aiPush !== '') {
            $currentMotivation['push'] = $aiPush;
        }
    } catch (Throwable $exception) {
        /*
         * Keep using the local response if the API is unavailable.
         * The private error goes only to the server error log.
         */
        error_log(
            'OpenAI motivation request failed: '
            . $exception->getMessage()
        );
    }
}



$currentIntervention = null;

if (
    $selectedCategory === 'cheat' &&
    $cheatReason !== ''
) {
    $currentIntervention = $cheatInterventions[$cheatReason];

    if ($responseAction === 'another') {
        $trickIndex++;
    }

    $reasonTricks = $cheatTricks[$cheatReason];
    $trickIndex %= count($reasonTricks);
    $currentIntervention = array_merge(
        $currentIntervention,
        $reasonTricks[$trickIndex]
    );

    $cheatContexts = [
        'hungry' => 'The user is genuinely hungry. Do not discourage eating; support a reasonable satisfying choice.',
        'craving' => 'The user has a specific craving and wants help pausing before acting automatically.',
        'emotional' => 'The user may want food because of stress, boredom, or emotion and needs a non-food interruption.',
        'unsure' => 'The user cannot tell whether this is hunger or a craving and needs a short neutral pause.',
    ];

    $currentIntervention['push'] = sos_generate_push(
        $cheatContexts[$cheatReason]
        . ' Technique selected for this response: '
        . $currentIntervention['action'],
        $currentIntervention['push'],
        $responseAction
    );
}

$scalePush = 'A single scale reading is information—not a verdict. Look at the direction over time, not one noisy number.';
$resultsPush = 'Progress is not limited to what the mirror or scale shows today. Repeated actions are evidence that you are building something—even before every result becomes obvious.';
$lazyPush = 'Do not promise yourself a perfect workout. Make starting so easy that your excuses have nothing useful to argue with.';

if ($selectedCategory === 'lazy' && $lazyStep === '') {
    $lazyPush = sos_generate_push(
        'The user is avoiding a workout. Reduce activation energy and lead directly into putting on their shoes.',
        $lazyPush,
        $responseAction
    );
}

if ($selectedCategory === 'scale') {
    $scalePush = sos_generate_push(
        'The user is frustrated by a scale reading. Emphasize normal short-term fluctuation and looking at trends. No measurements are provided.',
        $scalePush,
        $responseAction
    );
}

if ($selectedCategory === 'results') {
    $resultsPush = sos_generate_push(
        'The user feels that results are not visible yet. Encourage noticing multiple forms of progress and continuing one useful behavior. No personal statistics are provided.',
        $resultsPush,
        $responseAction
    );
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


$resultsChallengeName = null;
$resultsChallengeDay = 0;
$resultsTargetDays = 0;
$resultsWeightChange = null;
$resultsWaistChange = null;
$resultsStrengthWorkouts = 0;
$resultsCheckInCount = 0;

if ($selectedCategory === 'results') {
    $resultsChallenge = db()
        ->query(
            "SELECT *
             FROM challenges
             WHERE status = 'active'
             ORDER BY start_date DESC
             LIMIT 1"
        )
        ->fetch();

    if ($resultsChallenge) {
        $resultsChallengeName = $resultsChallenge['name'];
        $resultsTargetDays =
            (int) $resultsChallenge['target_days'];

        $timezone = new DateTimeZone('America/New_York');
        $today = new DateTimeImmutable('today', $timezone);

        $challengeStart = new DateTimeImmutable(
            $resultsChallenge['start_date'],
            $timezone
        );

        $challengeEnd = new DateTimeImmutable(
            $resultsChallenge['end_date'],
            $timezone
        );

        if ($today < $challengeStart) {
            $resultsChallengeDay = 0;
        } elseif ($today > $challengeEnd) {
            $resultsChallengeDay = $resultsTargetDays;
        } else {
            $calendarDay =
                (int) $challengeStart
                    ->diff($today)
                    ->format('%a') + 1;

            $resultsChallengeDay = min(
                $calendarDay,
                $resultsTargetDays
            );
        }

        $resultsStatement = db()->prepare(
            'SELECT
                check_in_date,
                weight,
                waist,
                activities
             FROM check_ins
             WHERE check_in_date
                BETWEEN :start_date AND :end_date
             ORDER BY check_in_date ASC'
        );

        $resultsStatement->execute([
            'start_date' => $resultsChallenge['start_date'],
            'end_date' => $resultsChallenge['end_date'],
        ]);

        $resultsCheckIns = $resultsStatement->fetchAll();
        $resultsCheckInCount = count($resultsCheckIns);

        $challengeWeights = [];
        $challengeWaists = [];

        foreach ($resultsCheckIns as $resultsCheckIn) {
            if ($resultsCheckIn['weight'] !== null) {
                $challengeWeights[] =
                    (float) $resultsCheckIn['weight'];
            }

            if ($resultsCheckIn['waist'] !== null) {
                $challengeWaists[] =
                    (float) $resultsCheckIn['waist'];
            }

            $activities = array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        (string) $resultsCheckIn['activities']
                    )
                )
            );

            if (in_array('strength', $activities, true)) {
                $resultsStrengthWorkouts++;
            }
        }

        if (count($challengeWeights) >= 2) {
            $resultsWeightChange =
                $challengeWeights[
                    count($challengeWeights) - 1
                ] - $challengeWeights[0];
        }

        if (count($challengeWaists) >= 2) {
            $resultsWaistChange =
                $challengeWaists[
                    count($challengeWaists) - 1
                ] - $challengeWaists[0];
        }
    }
}


require dirname(__DIR__) . '/includes/header.php';

?>


<?php if ($currentMotivation !== null): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Just Motivate Me</p>
        <h1>Your push is ready.</h1>
    </section>

    <section class="sos-intervention-card sos-ai-card">
        <p class="card-label">Your Push</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentMotivation['push'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-action-card">
        <p class="card-label">⚡ Do This Right Now</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentMotivation['action'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">🧠 Why This Works</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentMotivation['why'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-fact-card">
        <p class="card-label">💡 Real-Ass Fact</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $currentMotivation['fact'],
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
                value="motivate"
            >

            <input
                type="hidden"
                name="motivation_style"
                value="<?= htmlspecialchars(
                    $motivationStyle,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <button
                class="sos-secondary-button"
                type="submit"
            >
                🎲 Give Me Another Push
            </button>
        </form>

        <form method="post" action="/?page=sos">
            <input
                type="hidden"
                name="sos_category"
                value="motivate"
            >

            <button
                class="sos-secondary-button"
                type="submit"
            >
                Choose Another Style
            </button>
        </form>

        <a class="sos-good-button" href="/?page=home">
            ✅ I’m Good Now
        </a>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>



<?php if (
    $selectedCategory === 'motivate' &&
    $motivationStyle === ''
): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Choose Your Energy</p>
        <h1>How do you want me to motivate you?</h1>

        <p class="sos-introduction">
            Pick the style that will actually work right now.
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
            value="motivate"
        >

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="motivation_style"
            value="encourage"
        >
            <span>🌸</span>
            <strong>Encourage Me</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="motivation_style"
            value="coach"
        >
            <span>🔥</span>
            <strong>Coach Me</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="motivation_style"
            value="laugh"
        >
            <span>😂</span>
            <strong>Make Me Laugh</strong>
        </button>

        <button
            class="sos-follow-up-choice"
            type="submit"
            name="motivation_style"
            value="roast"
        >
            <span>☠️</span>
            <strong>Roast Me</strong>
        </button>
    </form>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>



<?php if ($selectedCategory === 'results'): ?>

    <section class="sos-heading">
        <a class="sos-back-link" href="/?page=sos">
            ← Back to SOS choices
        </a>

        <p class="eyebrow">Look at the Evidence</p>
        <h1>You may be making more progress than you feel.</h1>

        <?php if ($resultsChallengeName !== null): ?>
            <p class="sos-introduction">
                <?= htmlspecialchars(
                    $resultsChallengeName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>
        <?php endif; ?>
    </section>

    <?php if ($resultsChallengeName === null): ?>

        <section class="sos-intervention-card sos-action-card">
            <p class="card-label">No Active Challenge</p>

            <p class="sos-intervention-text">
                There is no active challenge available to calculate
                right now.
            </p>
        </section>

    <?php else: ?>

        <section class="sos-results-grid">
            <article class="sos-result-stat sos-result-day">
                <span>Challenge Day</span>

                <strong>
                    <?= $resultsChallengeDay ?>
                    /
                    <?= $resultsTargetDays ?>
                </strong>

                <small>You are still in it.</small>
            </article>

            <article class="sos-result-stat sos-result-weight">
                <span>Weight Change</span>

                <strong>
                    <?php if ($resultsWeightChange !== null): ?>
                        <?= $resultsWeightChange > 0 ? '+' : '' ?>
                        <?= number_format(
                            $resultsWeightChange,
                            1
                        ) ?> lb
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </strong>

                <small>
                    <?= $resultsWeightChange !== null
                        ? 'First to latest challenge measurement'
                        : 'Two measurements are needed' ?>
                </small>
            </article>

            <article class="sos-result-stat sos-result-waist">
                <span>Waist Change</span>

                <strong>
                    <?php if ($resultsWaistChange !== null): ?>
                        <?= $resultsWaistChange > 0 ? '+' : '' ?>
                        <?= number_format(
                            $resultsWaistChange,
                            1
                        ) ?> in
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </strong>

                <small>
                    <?= $resultsWaistChange !== null
                        ? 'First to latest challenge measurement'
                        : 'Two measurements are needed' ?>
                </small>
            </article>

            <article class="sos-result-stat sos-result-strength">
                <span>Strength Workouts</span>
                <strong><?= $resultsStrengthWorkouts ?></strong>
                <small>Logged during this challenge</small>
            </article>

            <article class="sos-result-stat sos-result-checkins">
                <span>Check-Ins</span>
                <strong><?= $resultsCheckInCount ?></strong>
                <small>Times you chose to show up</small>
            </article>
        </section>

    <?php endif; ?>

    <section class="sos-intervention-card sos-ai-card">
        <p class="card-label">Your Push</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $resultsPush,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </section>

    <section class="sos-intervention-card sos-why-card">
        <p class="card-label">🧠 Why This Works</p>

        <p class="sos-intervention-text">
            Looking at several kinds of progress fights the
            all-or-nothing belief that only one number counts.
        </p>
    </section>

    <section class="sos-intervention-card sos-fact-card">
        <p class="card-label">💡 Real-Ass Fact</p>

        <p class="sos-intervention-text">
            Body weight, waist measurements, fitness, habits, and
            consistency can change at different speeds. One measure
            can appear stuck while another is improving.
        </p>
    </section>

    <div class="sos-intervention-actions">
        <form method="post" action="/?page=sos">
            <input type="hidden" name="sos_category" value="results">

            <button
                class="sos-secondary-button"
                type="submit"
                name="response_action"
                value="another"
            >
                🎲 Give Me Another Push
            </button>
        </form>

        <form method="post" action="/?page=sos">
            <input type="hidden" name="sos_category" value="results">

            <button
                class="sos-secondary-button"
                type="submit"
                name="response_action"
                value="teach"
            >
                🧠 Teach Me Something
            </button>
        </form>

        <a class="sos-good-button" href="/?page=check-in">
            ✅ Add a Check-In
        </a>

        <a class="sos-secondary-button" href="/?page=progress">
            View Full Progress
        </a>
    </div>

    <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return; ?>

<?php endif; ?>



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
            <?= htmlspecialchars(
                $scalePush,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
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
        <form method="post" action="/?page=sos">
            <input type="hidden" name="sos_category" value="scale">

            <button
                class="sos-secondary-button"
                type="submit"
                name="response_action"
                value="another"
            >
                🎲 Give Me Another Perspective
            </button>
        </form>

        <form method="post" action="/?page=sos">
            <input type="hidden" name="sos_category" value="scale">

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
        <form method="post" action="/?page=sos">
            <input type="hidden" name="sos_category" value="struggling">
            <input
                type="hidden"
                name="struggle_reason"
                value="<?= htmlspecialchars(
                    $struggleReason,
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
                🎲 Give Me Another Push
            </button>
        </form>

        <form method="post" action="/?page=sos">
            <input type="hidden" name="sos_category" value="struggling">
            <input
                type="hidden"
                name="struggle_reason"
                value="<?= htmlspecialchars(
                    $struggleReason,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

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

    <section class="sos-intervention-card sos-ai-card">
        <p class="card-label">Your Push</p>

        <p class="sos-intervention-text">
            <?= htmlspecialchars(
                $lazyPush,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
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

            <input
                type="hidden"
                name="trick_index"
                value="<?= $trickIndex ?>"
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

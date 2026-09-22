<?php

$pageTitle = 'Future Me | PushMeToday45';
$activePage = 'future-me';

$learningCards = [
    [
        'icon' => '🦴',
        'category' => 'Bone Health',
        'title' => 'Your skeleton is participating too.',
        'fact' =>
            'Weight-bearing activity and resistance exercise help support bone health.',
        'translation' =>
            'Your dumbbells are not only working your muscles. Your bones noticed the assignment too. 😂',
        'more' =>
            'Bones are living tissue. Activities that place appropriate stress on them can help support bone strength over time.',
        'source' => 'National Institute of Arthritis and Musculoskeletal and Skin Diseases',
    ],
    [
        'icon' => '💪',
        'category' => 'Strength Training',
        'title' => 'Muscle is future-you equipment.',
        'fact' =>
            'Strength training can improve muscle strength and help support mobility as you age.',
        'translation' =>
            'Every squat is a tiny deposit into your stay-capable account.',
        'more' =>
            'Maintaining strength can make everyday activities such as carrying groceries, using stairs and standing from a chair easier.',
        'source' => 'National Institute on Aging',
    ],
    [
        'icon' => '⚖️',
        'category' => 'Balance',
        'title' => 'Balance is a skill—not just luck.',
        'fact' =>
            'Balance activities can help reduce fall risk and support safer movement.',
        'translation' =>
            'Standing on one foot near the counter is future-you training, not random flamingo behavior. 🦩',
        'more' =>
            'Balance uses your muscles, vision, inner ear and nervous system together. Like other physical abilities, it can be practiced.',
        'source' => 'Centers for Disease Control and Prevention',
    ],
    [
        'icon' => '❤️',
        'category' => 'Everyday Movement',
        'title' => 'Short movement still counts.',
        'fact' =>
            'Physical activity does not have to happen all at once to provide benefits.',
        'translation' =>
            'A five-minute walk is not fake exercise. Your body still received the memo.',
        'more' =>
            'Small periods of movement can be easier to repeat. Repeated activity can gradually become part of your normal routine.',
        'source' => 'Physical Activity Guidelines for Americans',
    ],
];

$timezone = new DateTimeZone('America/New_York');
$today = new DateTimeImmutable('today', $timezone);
$factIndex = (int) $today->format('z') % count($learningCards);
$todayFact = $learningCards[$factIndex];

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="future-me-hero">
    <p class="eyebrow">Future Me</p>

    <h1>Strong now. Strong later.</h1>

    <p class="future-me-introduction">
        Present You does the work.
        Future You gets the payoff.
    </p>

    <div class="future-me-purpose">
        <span>🧠 Learn</span>
        <span>🏋️ Do</span>
        <span>👵 Remember Why</span>
    </div>
</section>

<section class="future-learning-card">
    <div class="future-card-heading">
        <span class="future-card-icon">
            <?= htmlspecialchars(
                $todayFact['icon'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </span>

        <div>
            <p class="card-label">Today’s Real-Ass Fact</p>

            <span class="future-card-category">
                <?= htmlspecialchars(
                    $todayFact['category'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>
        </div>
    </div>

    <h2>
        <?= htmlspecialchars(
            $todayFact['title'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </h2>

    <p class="future-main-fact">
        <?= htmlspecialchars(
            $todayFact['fact'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>

    <div class="future-translation">
        <strong>Translation:</strong>

        <p>
            <?= htmlspecialchars(
                $todayFact['translation'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </div>

    <details class="future-more-details">
        <summary>Tell Me More</summary>

        <p>
            <?= htmlspecialchars(
                $todayFact['more'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

        <small>
            Source:
            <?= htmlspecialchars(
                $todayFact['source'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </small>
    </details>
</section>

<section class="future-challenge-card">
    <div class="future-card-heading">
        <span class="future-card-icon">🏋️</span>

        <div>
            <p class="card-label">Do Something Today</p>
            <span class="future-card-category">
                Approximately 8 minutes
            </span>
        </div>
    </div>

    <h2>Today’s Mini Strength Challenge</h2>

    <p class="future-challenge-introduction">
        Two rounds. Nothing complicated. Just show up and move.
    </p>

    <ol class="future-workout-list">
        <li>
            <strong>10</strong>
            chair or bodyweight squats
        </li>

        <li>
            <strong>10</strong>
            wall or incline push-ups
        </li>

        <li>
            <strong>10</strong>
            dumbbell rows on each side
        </li>

        <li>
            <strong>15</strong>
            glute bridges
        </li>
    </ol>

    <div class="future-workout-rounds">
        Repeat the list × 2
    </div>

    <details class="future-safety-details">
        <summary>Before You Start</summary>

        <p>
            Use a comfortable range of motion and stable support.
            Stop if you feel pain, dizziness or unusual shortness
            of breath.
        </p>
    </details>

    <button
        class="future-action-button"
        id="futureChallengeButton"
        type="button"
    >
        I’m Doing This
    </button>

    <div
        class="future-challenge-confirmation"
        id="futureChallengeConfirmation"
        hidden
    >
        <strong>🔥 Present You just showed up.</strong>

        <p>
            Future You approves. Go handle those eight minutes.
        </p>
    </div>
</section>

<?php

$futureMessages = [
    [
        'age' => '75-year-old you',
        'message' =>
            'Thanks for doing those squats back in 2026. I just got off the floor without calling anybody.',
        'takeaway' =>
            'Maintaining lower-body strength can support everyday movement and independence as you age.',
        'emoji' => '😂',
    ],
    [
        'age' => '70-year-old you',
        'message' =>
            'Thank you for training your balance. I caught myself after tripping over absolutely nothing.',
        'takeaway' =>
            'Strength and balance practice can help support stability and safer movement.',
        'emoji' => '😅',
    ],
    [
        'age' => '80-year-old you',
        'message' =>
            'Those little walks added up. My heart would like to submit a formal thank-you letter.',
        'takeaway' =>
            'Regular physical activity supports cardiovascular health and physical function throughout life.',
        'emoji' => '❤️',
    ],
    [
        'age' => '65-year-old you',
        'message' =>
            'Good call building muscle before I needed it. Very strategic. Extremely rude that aging requires planning, though.',
        'takeaway' =>
            'Building and maintaining muscle can support strength, mobility and daily function later in life.',
        'emoji' => '💪',
    ],
    [
        'age' => 'Future You',
        'message' =>
            'You thought eight minutes was too small to matter. I kept every single one of them.',
        'takeaway' =>
            'Small actions become meaningful when they are repeated consistently over time.',
        'emoji' => '✨',
    ],
];

$messageIndex = (
    (int) $today->format('z') + 1
) % count($futureMessages);

$futureMessage = $futureMessages[$messageIndex];

?>

<section class="future-message-card">
    <div class="future-message-label">
        <span>💌</span>

        <p>
            Message From
            <?= htmlspecialchars(
                $futureMessage['age'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </div>

    <blockquote>
        “<?= htmlspecialchars(
            $futureMessage['message'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>”
    </blockquote>

    <div class="future-message-emoji">
        <?= htmlspecialchars(
            $futureMessage['emoji'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

    <div class="future-message-takeaway">
        <strong>Why Future Me Cares</strong>

        <p>
            <?= htmlspecialchars(
                $futureMessage['takeaway'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>
    </div>
</section>

<?php

$surpriseItems = [
    [
        'icon' => '🧠',
        'category' => 'Fitness Fact',
        'title' => 'Starting small is still starting.',
        'content' =>
            'A short period of movement can lower the barrier to beginning. You do not need a perfect workout for movement to count.',
        'action' =>
            'Walk around for five minutes. You can stop afterward if you still want to.',
    ],
    [
        'icon' => '🏋️',
        'category' => 'Exercise Snack',
        'title' => 'Ten squats. That’s it.',
        'content' =>
            'Stand near something stable and perform ten comfortable chair or bodyweight squats.',
        'action' =>
            'Give me 10 controlled squats.',
    ],
    [
        'icon' => '⚖️',
        'category' => 'Balance Challenge',
        'title' => 'Try the flamingo test.',
        'content' =>
            'Stand beside a sturdy counter or chair. Lift one foot and see whether you can hold your balance for 20 seconds.',
        'action' =>
            'Try the left side and then the right side. Keep stable support within reach.',
    ],
    [
        'icon' => '🧘',
        'category' => 'Mobility Moment',
        'title' => 'Your shoulders requested a meeting.',
        'content' =>
            'Gently roll your shoulders backward five times, then reach both arms overhead without forcing the movement.',
        'action' =>
            'Take five slow breaths while keeping your shoulders relaxed.',
    ],
    [
        'icon' => '🔥',
        'category' => 'Menopause Minute',
        'title' => 'Your skeleton deserves advance planning.',
        'content' =>
            'Hormonal changes around menopause can affect bone health. Staying active and getting appropriate calcium and vitamin D remain important.',
        'action' =>
            'Treat strength and weight-bearing movement as long-term bone-health habits—not temporary punishment.',
    ],
    [
        'icon' => '👵',
        'category' => 'Message From Future You',
        'title' => 'Future You kept the receipt.',
        'content' =>
            'That tiny thing you almost skipped? I received the benefits. Thank you for doing it anyway.',
        'action' =>
            'Choose one useful action that takes less than five minutes and do it now.',
    ],
];

$showSurprise = (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['future_surprise'])
);

$surpriseItem = null;

if ($showSurprise) {
    $surpriseIndex = random_int(
        0,
        count($surpriseItems) - 1
    );

    $surpriseItem = $surpriseItems[$surpriseIndex];
}

?>

<section class="future-surprise-card">
    <div class="future-surprise-heading">
        <span>🎲</span>

        <div>
            <p class="card-label">Surprise Me</p>
            <h2>Give me something.</h2>
        </div>
    </div>

    <p class="future-surprise-introduction">
        A fact, tiny workout, mobility break or message from
        Future You. You won’t know until you press it.
    </p>

    <form method="post" action="/?page=future-me">
        <button
            class="future-surprise-button"
            type="submit"
            name="future_surprise"
            value="1"
        >
            🎲 Give Me Something
        </button>
    </form>

    <?php if ($surpriseItem !== null): ?>
        <article class="future-surprise-result">
            <div class="future-surprise-category">
                <span>
                    <?= htmlspecialchars(
                        $surpriseItem['icon'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $surpriseItem['category'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>

            <h3>
                <?= htmlspecialchars(
                    $surpriseItem['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h3>

            <p>
                <?= htmlspecialchars(
                    $surpriseItem['content'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <div class="future-surprise-action">
                <strong>Do This:</strong>

                <p>
                    <?= htmlspecialchars(
                        $surpriseItem['action'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            </div>
        </article>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
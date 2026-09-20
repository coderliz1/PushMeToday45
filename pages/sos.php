<?php

$pageTitle = 'I Need Help | PushMeToday45';
$activePage = 'sos';

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

$currentIntervention = null;

if (
    $selectedCategory === 'cheat' &&
    $cheatReason !== ''
) {
    $currentIntervention = $cheatInterventions[$cheatReason];
}

require dirname(__DIR__) . '/includes/header.php';

?>

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
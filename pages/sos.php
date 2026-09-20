<?php

$pageTitle = 'I Need Help | PushMeToday45';
$activePage = 'sos';

require dirname(__DIR__) . '/includes/header.php';

?>

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
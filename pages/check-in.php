<?php

$pageTitle = 'Daily Check-In | PushMeToday45';
$activePage = 'check-in';

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="page-heading">
    <p class="eyebrow">Daily progress</p>
    <h1>Check in with yourself.</h1>
    <p>Small updates give you the clearest picture of your progress.</p>
</section>

<form class="check-in-form" method="post">
    <section class="form-card">
        <div class="field-group">
            <label for="check_in_date">Date</label>
            <input
                id="check_in_date"
                name="check_in_date"
                type="date"
                value="<?= date('Y-m-d') ?>"
                required
            >
        </div>

        <div class="measurement-grid">
            <div class="field-group">
                <label for="weight">Weight</label>

                <div class="input-with-unit">
                    <input
                        id="weight"
                        name="weight"
                        type="number"
                        min="0"
                        step="0.1"
                        placeholder="160.0"
                    >
                    <span>lb</span>
                </div>
            </div>

            <div class="field-group">
                <label for="waist">Waist</label>

                <div class="input-with-unit">
                    <input
                        id="waist"
                        name="waist"
                        type="number"
                        min="0"
                        step="0.1"
                        placeholder="32.0"
                    >
                    <span>in</span>
                </div>
            </div>
        </div>

        <div class="field-group">
            <label for="steps">Steps <small>Optional</small></label>
            <input
                id="steps"
                name="steps"
                type="number"
                min="0"
                step="1"
                placeholder="Example: 7500"
            >
        </div>
    </section>

    <section class="form-card">
        <div class="section-heading">
            <div>
                <p class="card-label">Movement</p>
                <h2>What did you do today?</h2>
            </div>
        </div>

        <div class="activity-options">
            <label class="choice-chip">
                <input type="checkbox" name="activities[]" value="walking">
                <span>Walking</span>
            </label>

            <label class="choice-chip">
                <input type="checkbox" name="activities[]" value="strength">
                <span>Strength</span>
            </label>

            <label class="choice-chip">
                <input type="checkbox" name="activities[]" value="balance">
                <span>Balance</span>
            </label>

            <label class="choice-chip">
                <input type="checkbox" name="activities[]" value="stretching">
                <span>Stretching</span>
            </label>

            <label class="choice-chip">
                <input type="checkbox" name="activities[]" value="cardio">
                <span>Cardio</span>
            </label>

            <label class="choice-chip">
                <input type="checkbox" name="activities[]" value="rest">
                <span>Rest Day</span>
            </label>
        </div>
    </section>

    <section class="form-card">
        <div class="section-heading">
            <div>
                <p class="card-label">Mood</p>
                <h2>How are you feeling?</h2>
            </div>
        </div>

        <div class="mood-options">
            <label class="mood-choice">
                <input type="radio" name="mood" value="great">
                <span class="mood-face">😄</span>
                <span>Great</span>
            </label>

            <label class="mood-choice">
                <input type="radio" name="mood" value="good">
                <span class="mood-face">🙂</span>
                <span>Good</span>
            </label>

            <label class="mood-choice">
                <input type="radio" name="mood" value="okay">
                <span class="mood-face">😐</span>
                <span>Okay</span>
            </label>

            <label class="mood-choice">
                <input type="radio" name="mood" value="struggling">
                <span class="mood-face">😣</span>
                <span>Struggling</span>
            </label>
        </div>

        <div class="field-group notes-field">
            <label for="notes">Notes <small>Optional</small></label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
                placeholder="Anything you want Future You to remember?"
            ></textarea>
        </div>
    </section>

    <button class="save-button" type="submit">
        Save Check-In
    </button>
</form>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
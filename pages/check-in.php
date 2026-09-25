<?php

$pageTitle = 'Daily Check-In | PushMeToday45';
$activePage = 'check-in';

require_once dirname(__DIR__) . '/includes/functions.php';

$errors = [];
$saveSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkInDate = trim((string) ($_POST['check_in_date'] ?? ''));
    $weightInput = trim((string) ($_POST['weight'] ?? ''));
    $waistInput = trim((string) ($_POST['waist'] ?? ''));
    $stepsInput = trim((string) ($_POST['steps'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    $allowedActivities = [
        'walking',
        'strength',
        'balance',
        'stretching',
        'cardio',
        'rest',
    ];

    $submittedActivities = $_POST['activities'] ?? [];
    $activities = is_array($submittedActivities)
        ? array_values(array_intersect($allowedActivities, $submittedActivities))
        : [];

    $allowedMoods = ['great', 'good', 'okay', 'struggling'];
    $mood = trim((string) ($_POST['mood'] ?? ''));

    $allowedPeriodStatuses = ['none', 'started', 'ongoing', 'ended'];
    $periodStatus = trim((string) ($_POST['period_status'] ?? ''));

    $dateObject = DateTime::createFromFormat('Y-m-d', $checkInDate);

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $checkInDate
    ) {
        $errors[] = 'Please choose a valid check-in date.';
    }

    if ($weightInput !== '' && (!is_numeric($weightInput) || (float) $weightInput <= 0)) {
        $errors[] = 'Please enter a valid weight.';
    }

    if ($waistInput !== '' && (!is_numeric($waistInput) || (float) $waistInput <= 0)) {
        $errors[] = 'Please enter a valid waist measurement.';
    }

    if (
        $stepsInput !== '' &&
        filter_var(
            $stepsInput,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        ) === false
    ) {
        $errors[] = 'Steps must be a whole number of zero or more.';
    }

    if ($mood !== '' && !in_array($mood, $allowedMoods, true)) {
        $errors[] = 'Please choose a valid mood.';
    }

    if (
    $periodStatus !== '' &&
    !in_array($periodStatus, $allowedPeriodStatuses, true)
    ) {
    $errors[] = 'Please choose a valid cycle status.';
    }

    if (strlen($notes) > 2000) {
        $errors[] = 'Notes must be 2,000 characters or fewer.';
    }

    if ($errors === []) {
        try {
            $statement = db()->prepare(
                'INSERT INTO check_ins
                    (
                        check_in_date,
                        weight,
                        waist,
                        steps,
                        activities,
                        mood,
                        period_status,
                        notes
                    )
                 VALUES
                    (
                        :check_in_date,
                        :weight,
                        :waist,
                        :steps,
                        :activities,
                        :mood,
                        :period_status,
                        :notes
                    )
                 ON DUPLICATE KEY UPDATE
                    weight = VALUES(weight),
                    waist = VALUES(waist),
                    steps = VALUES(steps),
                    activities = VALUES(activities),
                    mood = VALUES(mood),
                    period_status = VALUES(period_status),
                    notes = VALUES(notes)'
            );

            $statement->execute([
                'check_in_date' => $checkInDate,
                'weight' => $weightInput !== '' ? (float) $weightInput : null,
                'waist' => $waistInput !== '' ? (float) $waistInput : null,
                'steps' => $stepsInput !== '' ? (int) $stepsInput : null,
                'activities' => $activities !== [] ? implode(', ', $activities) : null,
                'mood' => $mood !== '' ? $mood : null,
                'period_status' => $periodStatus !== '' ? $periodStatus : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $saveSuccess = true;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            $errors[] = 'Your check-in could not be saved. Please try again.';
        }
    }
}

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="page-heading">
    <p class="eyebrow">Daily progress</p>
    <h1>Check in with yourself.</h1>
    <p>Small updates give you the clearest picture of your progress.</p>
</section>

<?php if ($saveSuccess): ?>
    <div class="status-message status-success" role="status">
        Your check-in was saved successfully!
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="status-message status-error" role="alert">
        <strong>Your check-in was not saved:</strong>

        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="check-in-form" method="post">
    <section class="form-card">
        <div class="field-group">
            <label for="check_in_date">Date</label>
            <input
                id="check_in_date"
                name="check_in_date"
                type="date"
                value="<?= (new DateTimeImmutable('now', new DateTimeZone('America/New_York')))->format('Y-m-d') ?>"
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


        <div class="cycle-section">
    <div class="section-heading">
        <div>
            <p class="card-label">Cycle</p>
            <h2>Period status</h2>
        </div>
    </div>

    <div class="activity-options">
        <label class="choice-chip">
            <input
                type="radio"
                name="period_status"
                value="none"
                checked
            >
            <span>No Period</span>
        </label>

        <label class="choice-chip">
            <input
                type="radio"
                name="period_status"
                value="started"
            >
            <span>Started Today</span>
        </label>

        <label class="choice-chip">
            <input
                type="radio"
                name="period_status"
                value="ongoing"
            >
            <span>Period Ongoing</span>
        </label>

        <label class="choice-chip">
            <input
                type="radio"
                name="period_status"
                value="ended"
            >
            <span>Ended Today</span>
        </label>
    </div>
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
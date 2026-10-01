<?php

$pageTitle = 'Daily Check-In | PushMeToday45';

$activePage = 'check-in';

require_once dirname(__DIR__) . '/includes/challenges.php';

$timezone = new DateTimeZone('America/New_York');

$todayDate = new DateTimeImmutable('today', $timezone);

$errors = [];

$saveSuccess = false;

$activitySuccess = false;

$moodSuccess = false;

$noteSuccess = false;

$formAction = trim((string) ($_POST['form_action'] ?? ''));

if (

    $_SERVER['REQUEST_METHOD'] === 'POST' &&

    $formAction === 'save_measurements'

) {

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

            $measurementChallengeId = challenge_id_for_date($checkInDate);

$statement = db()->prepare(

    'INSERT INTO check_ins

        (

            check_in_date,

            challenge_id,

            weight,

            waist,

            steps,

            period_status

        )

     VALUES

        (

            :check_in_date,

            :challenge_id,

            :weight,

            :waist,

            :steps,

            :period_status

        )

     ON DUPLICATE KEY UPDATE

        challenge_id = COALESCE(

            check_ins.challenge_id,

            VALUES(challenge_id)

        ),

        weight = VALUES(weight),

        waist = VALUES(waist),

        steps = VALUES(steps),

        period_status = VALUES(period_status)'

);

$statement->execute([

    'check_in_date' => $checkInDate,

    'challenge_id' => $measurementChallengeId,

    'weight' => $weightInput !== ''

        ? (float) $weightInput

        : null,

    'waist' => $waistInput !== ''

        ? (float) $waistInput

        : null,

    'steps' => $stepsInput !== ''

        ? (int) $stepsInput

        : null,

    'period_status' => $periodStatus !== ''

        ? $periodStatus

        : null,

]);

            $saveSuccess = true;

        } catch (Throwable $exception) {

            error_log($exception->getMessage());

            $errors[] = 'Your check-in could not be saved. Please try again.';

        }

    }

}

if (

    $_SERVER['REQUEST_METHOD'] === 'POST' &&

    $formAction === 'log_activity'

) {

    $activityDate = trim(

        (string) ($_POST['entry_date'] ?? '')

    );

    $activityType = trim(

        (string) ($_POST['activity_type'] ?? '')

    );

    $durationInput = trim(

        (string) ($_POST['duration_minutes'] ?? '')

    );

    $activityNote = trim(

        (string) ($_POST['activity_note'] ?? '')

    );

    $allowedActivityTypes = [

        'walk',

        'run',

        'strength',

        'stretch_mobility',

        'cardio',

        'balance',

        'other',

    ];

    $activityDateObject = DateTimeImmutable::createFromFormat(

        '!Y-m-d',

        $activityDate,

        $timezone

    );

    if (

        !$activityDateObject ||

        $activityDateObject->format('Y-m-d') !== $activityDate

    ) {

        $errors[] = 'Please choose a valid activity date.';

    }

    if (!in_array($activityType, $allowedActivityTypes, true)) {

        $errors[] = 'Please choose an activity type.';

    }

    if (

        filter_var(

            $durationInput,

            FILTER_VALIDATE_INT,

            ['options' => ['min_range' => 1, 'max_range' => 1440]]

        ) === false

    ) {

        $errors[] = 'Activity duration must be between 1 and 1,440 minutes.';

    }

    if (strlen($activityNote) > 1000) {

        $errors[] = 'Activity notes must be 1,000 characters or fewer.';

    }

    if ($errors === []) {

        try {

            $connection = db();

            $activityChallengeId = challenge_id_for_date($activityDate);

            $dailyRecordStatement = $connection->prepare(
                'INSERT INTO check_ins (check_in_date, challenge_id)
                 VALUES (:check_in_date, :challenge_id)
                 ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    challenge_id = COALESCE(
                        check_ins.challenge_id,
                        VALUES(challenge_id)
                    )'
            );

            $dailyRecordStatement->execute([
                'check_in_date' => $activityDate,
                'challenge_id' => $activityChallengeId,
            ]);

            $checkInId = (int) $connection->lastInsertId();

            $activityStatement = $connection->prepare(

                'INSERT INTO check_in_activities

                    (

                        check_in_id,

                        activity_type,

                        duration_minutes,

                        note,

                        occurred_at

                    )

                 VALUES

                    (

                        :check_in_id,

                        :activity_type,

                        :duration_minutes,

                        :note,

                        :occurred_at

                    )'

            );

            $activityStatement->execute([

                'check_in_id' => $checkInId,

                'activity_type' => $activityType,

                'duration_minutes' => (int) $durationInput,

                'note' => $activityNote !== ''

                    ? $activityNote

                    : null,

                'occurred_at' => (

                    new DateTimeImmutable('now', $timezone)

                )->format('Y-m-d H:i:s'),

            ]);

            $activitySuccess = true;

        } catch (Throwable $exception) {

            error_log($exception->getMessage());

            $errors[] = 'Your activity could not be saved. Please try again.';

        }

    }

}

if (

    $_SERVER['REQUEST_METHOD'] === 'POST' &&

    $formAction === 'log_mood'

) {

    $moodDate = trim(

        (string) ($_POST['entry_date'] ?? '')

    );

    $moodValue = trim(

        (string) ($_POST['mood_value'] ?? '')

    );

    $moodNote = trim(

        (string) ($_POST['mood_note'] ?? '')

    );

    $allowedMoodValues = [

        'great',

        'good',

        'okay',

        'rough',

        'irritated',

        'anxious_stressed',

        'tired',

    ];

    $moodDateObject = DateTimeImmutable::createFromFormat(

        '!Y-m-d',

        $moodDate,

        $timezone

    );

    if (

        !$moodDateObject ||

        $moodDateObject->format('Y-m-d') !== $moodDate

    ) {

        $errors[] = 'Please choose a valid mood date.';

    }

    if (!in_array($moodValue, $allowedMoodValues, true)) {

        $errors[] = 'Please choose a mood.';

    }

    if (strlen($moodNote) > 1000) {

        $errors[] = 'Mood notes must be 1,000 characters or fewer.';

    }

    if ($errors === []) {

        try {

            $connection = db();


$moodChallengeId = challenge_id_for_date($moodDate);

$dailyRecordStatement = $connection->prepare(

    'INSERT INTO check_ins (

        check_in_date,

        challenge_id

     )

     VALUES (

        :check_in_date,

        :challenge_id

     )

     ON DUPLICATE KEY UPDATE

        id = LAST_INSERT_ID(id),

        challenge_id = COALESCE(

            check_ins.challenge_id,

            VALUES(challenge_id)

        )'

);

$dailyRecordStatement->execute([

    'check_in_date' => $moodDate,

    'challenge_id' => $moodChallengeId,

]);

$checkInId = (int) $connection->lastInsertId();

            $moodStatement = $connection->prepare(

                'INSERT INTO check_in_moods

                    (

                        check_in_id,

                        mood,

                        note,

                        occurred_at

                    )

                 VALUES

                    (

                        :check_in_id,

                        :mood,

                        :note,

                        :occurred_at

                    )'

            );

            $moodStatement->execute([

                'check_in_id' => $checkInId,

                'mood' => $moodValue,

                'note' => $moodNote !== ''

                    ? $moodNote

                    : null,

                'occurred_at' => (

                    new DateTimeImmutable('now', $timezone)

                )->format('Y-m-d H:i:s'),

            ]);

            $moodSuccess = true;

        } catch (Throwable $exception) {

            error_log($exception->getMessage());

            $errors[] = 'Your mood could not be saved. Please try again.';

        }

    }

}

if (

    $_SERVER['REQUEST_METHOD'] === 'POST' &&

    $formAction === 'add_note'

) {

    $noteDate = trim(

        (string) ($_POST['entry_date'] ?? '')

    );

    $noteText = trim(

        (string) ($_POST['note_text'] ?? '')

    );

    $noteDateObject = DateTimeImmutable::createFromFormat(

        '!Y-m-d',

        $noteDate,

        $timezone

    );

    if (

        !$noteDateObject ||

        $noteDateObject->format('Y-m-d') !== $noteDate

    ) {

        $errors[] = 'Please choose a valid note date.';

    }

    if ($noteText === '') {

        $errors[] = 'Please enter a note.';

    } elseif (strlen($noteText) > 2000) {

        $errors[] = 'Notes must be 2,000 characters or fewer.';

    }

    if ($errors === []) {

        try {

            $connection = db();

            $noteChallengeId = challenge_id_for_date($noteDate);

            $dailyRecordStatement = $connection->prepare(
                'INSERT INTO check_ins (check_in_date, challenge_id)
                 VALUES (:check_in_date, :challenge_id)
                 ON DUPLICATE KEY UPDATE
                    id = LAST_INSERT_ID(id),
                    challenge_id = COALESCE(
                        check_ins.challenge_id,
                        VALUES(challenge_id)
                    )'
            );

            $dailyRecordStatement->execute([
                'check_in_date' => $noteDate,
                'challenge_id' => $noteChallengeId,
            ]);

            $checkInId = (int) $connection->lastInsertId();

            $noteStatement = $connection->prepare(

                'INSERT INTO check_in_notes

                    (

                        check_in_id,

                        note,

                        occurred_at

                    )

                 VALUES

                    (

                        :check_in_id,

                        :note,

                        :occurred_at

                    )'

            );

            $noteStatement->execute([

                'check_in_id' => $checkInId,

                'note' => $noteText,

                'occurred_at' => (

                    new DateTimeImmutable('now', $timezone)

                )->format('Y-m-d H:i:s'),

            ]);

            $noteSuccess = true;

        } catch (Throwable $exception) {

            error_log($exception->getMessage());

            $errors[] = 'Your note could not be saved. Please try again.';

        }

    }

}

$selectedDate = trim(

    (string) (

        $_POST['check_in_date']
        ?? $_POST['entry_date']

        ?? $_GET['date']

        ?? $todayDate->format('Y-m-d')

    )

);

$selectedDateObject = DateTimeImmutable::createFromFormat(

    '!Y-m-d',

    $selectedDate,

    $timezone

);

if (

    !$selectedDateObject ||

    $selectedDateObject->format('Y-m-d') !== $selectedDate

) {

    $selectedDate = $todayDate->format('Y-m-d');

}

$dailyStatement = db()->prepare(

    'SELECT

        id,

        check_in_date,

        weight,

        waist,

        steps,

        period_status,

        created_at,

        updated_at

     FROM check_ins

     WHERE check_in_date = :check_in_date

     LIMIT 1'

);

$dailyStatement->execute([

    'check_in_date' => $selectedDate,

]);

$dailyCheckIn = $dailyStatement->fetch();

$savedPeriodStatus = $dailyCheckIn

    ? (string) ($dailyCheckIn['period_status'] ?? 'none')

    : 'none';

if ($savedPeriodStatus === '') {

    $savedPeriodStatus = 'none';

}

$activityListStatement = db()->prepare(

    'SELECT

        a.id,

        a.activity_type,

        a.duration_minutes,

        a.note,

        a.occurred_at

     FROM check_in_activities AS a

     INNER JOIN check_ins AS c

        ON c.id = a.check_in_id

     WHERE c.check_in_date = :check_in_date

     ORDER BY a.occurred_at ASC, a.id ASC'

);

$activityListStatement->execute([

    'check_in_date' => $selectedDate,

]);

$todayActivities = $activityListStatement->fetchAll();

$moodListStatement = db()->prepare(

    'SELECT

        m.id,

        m.mood,

        m.note,

        m.occurred_at

     FROM check_in_moods AS m

     INNER JOIN check_ins AS c

        ON c.id = m.check_in_id

     WHERE c.check_in_date = :check_in_date

     ORDER BY m.occurred_at ASC, m.id ASC'

);

$moodListStatement->execute([

    'check_in_date' => $selectedDate,

]);

$todayMoods = $moodListStatement->fetchAll();

$noteListStatement = db()->prepare(

    'SELECT

        n.id,

        n.note,

        n.occurred_at

     FROM check_in_notes AS n

     INNER JOIN check_ins AS c

        ON c.id = n.check_in_id

     WHERE c.check_in_date = :check_in_date

     ORDER BY n.occurred_at ASC, n.id ASC'

);

$noteListStatement->execute([

    'check_in_date' => $selectedDate,

]);

$todayNotes = $noteListStatement->fetchAll();

$totalMovementMinutes = 0;

foreach ($todayActivities as $activityEntry) {

    if ($activityEntry['duration_minutes'] !== null) {

        $totalMovementMinutes +=

            (int) $activityEntry['duration_minutes'];

    }

}

$activityCount = count($todayActivities);

$moodCount = count($todayMoods);

$noteCount = count($todayNotes);

$activityDisplay = [

    'walk' => ['🚶', 'Walk'],

    'run' => ['🏃', 'Run'],

    'strength' => ['🏋️', 'Strength'],

    'stretch_mobility' => ['🧘', 'Stretch / Mobility'],

    'cardio' => ['🚴', 'Cardio'],

    'balance' => ['⚖️', 'Balance'],

    'other' => ['✏️', 'Other'],

    'rest_day' => ['🛋️', 'Rest Day'],

];

$moodDisplay = [

    'great' => ['😄', 'Great'],

    'good' => ['🙂', 'Good'],

    'okay' => ['😐', 'Okay'],

    'rough' => ['😩', 'Rough'],

    'struggling' => ['😩', 'Rough'],

    'irritated' => ['😡', 'Irritated'],

    'anxious_stressed' => ['😰', 'Anxious / Stressed'],

    'tired' => ['😴', 'Tired'],

];

require dirname(__DIR__) . '/includes/header.php';

?>

<section class="page-heading">

    <p class="eyebrow">Daily progress</p>

    <h1>Check in with yourself.</h1>

    <p>Small updates give you the clearest picture of your progress.</p>

</section>

<?php if ($saveSuccess): ?>

    <div class="status-message status-success" role="status">

        Today’s measurements were saved!

    </div>

<?php endif; ?>

<?php if ($activitySuccess): ?>

    <div class="status-message status-success" role="status">

        Your activity was added!

    </div>

<?php endif; ?>

<?php if ($moodSuccess): ?>

    <div class="status-message status-success" role="status">

        Your mood was added!

    </div>

<?php endif; ?>

<?php if ($noteSuccess): ?>

    <div class="status-message status-success" role="status">

        Your note was added!

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

<section class="form-card">

    <div class="section-heading">

        <div>

            <p class="card-label">Today</p>

            <h2>Today’s Summary</h2>

        </div>

    </div>

    <div class="stats-grid" aria-label="Today’s summary">

        <article class="stat-card stat-mint">

            <span class="stat-label">Movement</span>

            <strong><?= $totalMovementMinutes ?> min</strong>

        </article>

        <article class="stat-card stat-purple">

            <span class="stat-label">Activities</span>

            <strong><?= $activityCount ?></strong>

        </article>

        <article class="stat-card stat-pink">

            <span class="stat-label">Mood Check-Ins</span>

            <strong><?= $moodCount ?></strong>

        </article>

        <article class="stat-card stat-blue">

            <span class="stat-label">Notes</span>

            <strong><?= $noteCount ?></strong>

        </article>

    </div>

</section>

<form class="check-in-form" method="post">

    <input

        type="hidden"

        name="form_action"

        value="save_measurements"

    >

    <section class="form-card">

        <div class="section-heading">

            <div>

                <p class="card-label">Once per day</p>

                <h2>⚖️ Today’s Measurements</h2>

            </div>

        </div>

        <div class="field-group">

            <label for="check_in_date">Date</label>

            <input

                id="check_in_date"

                name="check_in_date"

                type="date"

                value="<?= htmlspecialchars(

                    $selectedDate,

                    ENT_QUOTES,

                    'UTF-8'

                ) ?>"

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

                        value="<?= $dailyCheckIn &&

                            $dailyCheckIn['weight'] !== null

                            ? htmlspecialchars(

                                (string) $dailyCheckIn['weight'],

                                ENT_QUOTES,

                                'UTF-8'

                            )

                            : '' ?>"

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

                        value="<?= $dailyCheckIn &&

                            $dailyCheckIn['waist'] !== null

                            ? htmlspecialchars(

                                (string) $dailyCheckIn['waist'],

                                ENT_QUOTES,

                                'UTF-8'

                            )

                            : '' ?>"

                        placeholder="32.0"

                    >

                    <span>in</span>

                </div>

            </div>

        </div>

        <div class="field-group">

            <label for="steps">

                Steps <small>Optional</small>

            </label>

            <input

                id="steps"

                name="steps"

                type="number"

                min="0"

                step="1"

                value="<?= $dailyCheckIn &&

                    $dailyCheckIn['steps'] !== null

                    ? htmlspecialchars(

                        (string) $dailyCheckIn['steps'],

                        ENT_QUOTES,

                        'UTF-8'

                    )

                    : '' ?>"

                placeholder="Example: 7500"

            >

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

                        <?= $savedPeriodStatus === 'none'

                            ? 'checked'

                            : '' ?>

                    >

                    <span>No Period</span>

                </label>

                <label class="choice-chip">

                    <input

                        type="radio"

                        name="period_status"

                        value="started"

                        <?= $savedPeriodStatus === 'started'

                            ? 'checked'

                            : '' ?>

                    >

                    <span>Started Today</span>

                </label>

                <label class="choice-chip">

                    <input

                        type="radio"

                        name="period_status"

                        value="ongoing"

                        <?= $savedPeriodStatus === 'ongoing'

                            ? 'checked'

                            : '' ?>

                    >

                    <span>Period Ongoing</span>

                </label>

                <label class="choice-chip">

                    <input

                        type="radio"

                        name="period_status"

                        value="ended"

                        <?= $savedPeriodStatus === 'ended'

                            ? 'checked'

                            : '' ?>

                    >

                    <span>Ended Today</span>

                </label>

            </div>

        </div>

    </section>

    <button class="save-button" type="submit">

        Save Measurements

    </button>

</form>

<form class="check-in-form" method="post">

    <input

        type="hidden"

        name="form_action"

        value="log_activity"

    >

    <input

        type="hidden"

        name="entry_date"

        value="<?= htmlspecialchars(

            $selectedDate,

            ENT_QUOTES,

            'UTF-8'

        ) ?>"

    >

    <section class="form-card">

        <div class="section-heading">

            <div>

                <p class="card-label">Add to today</p>

                <h2>🏃 Log Activity</h2>

            </div>

        </div>

        <div class="field-group">

            <label for="activity_type">Activity Type</label>

            <select

                id="activity_type"

                name="activity_type"

                required

            >

                <option value="">Choose an activity</option>

                <option value="walk">🚶 Walk</option>

                <option value="run">🏃 Run</option>

                <option value="strength">🏋️ Strength</option>

                <option value="stretch_mobility">

                    🧘 Stretch / Mobility

                </option>

                <option value="cardio">🚴 Cardio</option>

                <option value="balance">⚖️ Balance</option>

                <option value="other">✏️ Other</option>

            </select>

        </div>

        <div class="field-group">

            <label for="duration_minutes">

                Duration in minutes

            </label>

            <input

                id="duration_minutes"

                name="duration_minutes"

                type="number"

                min="1"

                max="1440"

                step="1"

                placeholder="Example: 25"

                required

            >

        </div>

        <div class="field-group notes-field">

            <label for="activity_note">

                Optional Note

            </label>

            <textarea

                id="activity_note"

                name="activity_note"

                rows="3"

                maxlength="1000"

                placeholder="Anything you want to remember?"

            ></textarea>

        </div>

        <button class="save-button" type="submit">

            Save Activity

        </button>

    </section>

</form>

<form class="check-in-form" method="post">

    <input

        type="hidden"

        name="form_action"

        value="log_mood"

    >

    <input

        type="hidden"

        name="entry_date"

        value="<?= htmlspecialchars(

            $selectedDate,

            ENT_QUOTES,

            'UTF-8'

        ) ?>"

    >

    <section class="form-card">

        <div class="section-heading">

            <div>

                <p class="card-label">Add to today</p>

                <h2>😊 Log Mood</h2>

            </div>

        </div>

        <div class="mood-options">

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="great"

                    required

                >

                <span class="mood-face">😄</span>

                <span>Great</span>

            </label>

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="good"

                >

                <span class="mood-face">🙂</span>

                <span>Good</span>

            </label>

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="okay"

                >

                <span class="mood-face">😐</span>

                <span>Okay</span>

            </label>

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="rough"

                >

                <span class="mood-face">😩</span>

                <span>Rough</span>

            </label>

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="irritated"

                >

                <span class="mood-face">😡</span>

                <span>Irritated</span>

            </label>

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="anxious_stressed"

                >

                <span class="mood-face">😰</span>

                <span>Anxious / Stressed</span>

            </label>

            <label class="mood-choice">

                <input

                    type="radio"

                    name="mood_value"

                    value="tired"

                >

                <span class="mood-face">😴</span>

                <span>Tired</span>

            </label>

        </div>

        <div class="field-group notes-field">

            <label for="mood_note">

                Optional Note

            </label>

            <textarea

                id="mood_note"

                name="mood_note"

                rows="3"

                maxlength="1000"

                placeholder="What is affecting your mood?"

            ></textarea>

        </div>

        <button class="save-button" type="submit">

            Save Mood

        </button>

    </section>

</form>

<form class="check-in-form" method="post">

    <input

        type="hidden"

        name="form_action"

        value="add_note"

    >

    <input

        type="hidden"

        name="entry_date"

        value="<?= htmlspecialchars(

            $selectedDate,

            ENT_QUOTES,

            'UTF-8'

        ) ?>"

    >

    <section class="form-card">

        <div class="section-heading">

            <div>

                <p class="card-label">Add to today</p>

                <h2>📝 Add Note</h2>

            </div>

        </div>

        <div class="field-group notes-field">

            <label for="note_text">What do you want to remember?</label>

            <textarea

                id="note_text"

                name="note_text"

                rows="4"

                maxlength="2000"

                placeholder="Cravings, sleep, victories, observations..."

                required

            ></textarea>

        </div>

        <button class="save-button" type="submit">

            Save Note

        </button>

    </section>

</form>

<?php

$timelineItems = [];

if (

    $dailyCheckIn &&

    (

        $dailyCheckIn['weight'] !== null ||

        $dailyCheckIn['waist'] !== null ||

        $dailyCheckIn['steps'] !== null ||

        !empty($dailyCheckIn['period_status'])

    )

) {

    $measurementDetails = [];

    if ($dailyCheckIn['weight'] !== null) {

        $measurementDetails[] =

            number_format((float) $dailyCheckIn['weight'], 1) . ' lb';

    }

    if ($dailyCheckIn['waist'] !== null) {

        $measurementDetails[] =

            number_format((float) $dailyCheckIn['waist'], 1) . ' in';

    }

    if ($dailyCheckIn['steps'] !== null) {

        $measurementDetails[] =

            number_format((int) $dailyCheckIn['steps']) . ' steps';

    }

    $timelineItems[] = [

        'occurred_at' => $dailyCheckIn['updated_at'],

        'icon' => '⚖️',

        'title' => 'Daily measurements saved',

        'detail' => implode(' · ', $measurementDetails),

        'note' => null,

    ];

}

foreach ($todayActivities as $activityEntry) {

    $activityInfo =

        $activityDisplay[$activityEntry['activity_type']]

        ?? ['🏃', ucfirst($activityEntry['activity_type'])];

    $activityDuration =

        $activityEntry['duration_minutes'] !== null

        ? (int) $activityEntry['duration_minutes'] . ' min'

        : 'Duration not recorded';

    $timelineItems[] = [

        'occurred_at' => $activityEntry['occurred_at'],

        'icon' => $activityInfo[0],

        'title' => $activityInfo[1],

        'detail' => $activityDuration,

        'note' => $activityEntry['note'],

    ];

}

foreach ($todayMoods as $moodEntry) {

    $moodInfo =

        $moodDisplay[$moodEntry['mood']]

        ?? ['🙂', ucfirst($moodEntry['mood'])];

    $timelineItems[] = [

        'occurred_at' => $moodEntry['occurred_at'],

        'icon' => $moodInfo[0],

        'title' => 'Mood — ' . $moodInfo[1],

        'detail' => '',

        'note' => $moodEntry['note'],

    ];

}

foreach ($todayNotes as $noteEntry) {

    $timelineItems[] = [

        'occurred_at' => $noteEntry['occurred_at'],

        'icon' => '📝',

        'title' => 'Note',

        'detail' => '',

        'note' => $noteEntry['note'],

    ];

}

usort(

    $timelineItems,

    static function (array $firstItem, array $secondItem): int {

        return strcmp(

            $firstItem['occurred_at'],

            $secondItem['occurred_at']

        );

    }

);

?>

<section class="form-card timeline-card">

    <div class="section-heading">

        <div>

            <p class="card-label">Today</p>

            <h2>🕒 Today’s Timeline</h2>

        </div>

    </div>

    <?php if ($timelineItems === []): ?>

        <p class="timeline-empty">

            Nothing has been logged for this date yet.

        </p>

    <?php else: ?>

        <div class="today-timeline">

            <?php foreach ($timelineItems as $timelineItem): ?>

                <?php

                $timelineTime = new DateTimeImmutable(

                    $timelineItem['occurred_at'],

                    $timezone

                );

                ?>

                <article class="timeline-item">

                    <time

                        class="timeline-time"

                        datetime="<?= htmlspecialchars(

                            $timelineItem['occurred_at'],

                            ENT_QUOTES,

                            'UTF-8'

                        ) ?>"

                    >

                        <?= $timelineTime->format('g:i A') ?>

                    </time>

                    <div class="timeline-icon" aria-hidden="true">

                        <?= $timelineItem['icon'] ?>

                    </div>

                    <div class="timeline-content">

                        <strong>

                            <?= htmlspecialchars(

                                $timelineItem['title'],

                                ENT_QUOTES,

                                'UTF-8'

                            ) ?>

                        </strong>

                        <?php if ($timelineItem['detail'] !== ''): ?>

                            <span>

                                <?= htmlspecialchars(

                                    $timelineItem['detail'],

                                    ENT_QUOTES,

                                    'UTF-8'

                                ) ?>

                            </span>

                        <?php endif; ?>

                        <?php if (!empty($timelineItem['note'])): ?>

                            <p>

                                “<?= htmlspecialchars(

                                    $timelineItem['note'],

                                    ENT_QUOTES,

                                    'UTF-8'

                                ) ?>”

                            </p>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
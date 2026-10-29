<?php

declare(strict_types=1);

require_once __DIR__ . '/challenges.php';

/**
 * Read one challenge's recorded data.
 * Never changes its status or entries.
 */
function challenge_recap_data(int $challengeId): array
{
    $statement = db()->prepare(
        'SELECT id, name, start_date, end_date, status
         FROM challenges WHERE id = :id'
    );
    $statement->execute(['id' => $challengeId]);
    $challenge = $statement->fetch();

    if (!$challenge) {
        throw new InvalidArgumentException(
            'That challenge could not be found.'
        );
    }

    $statement = db()->prepare(
        'SELECT check_in_date, weight, waist
         FROM check_ins WHERE challenge_id = :id
         ORDER BY check_in_date ASC'
    );
    $statement->execute(['id' => $challengeId]);
    $dailyRecords = $statement->fetchAll();

    $statement = db()->prepare(
        'SELECT daily.check_in_date, activity.activity_type,
                activity.duration_minutes
         FROM check_in_activities AS activity
         INNER JOIN check_ins AS daily
            ON daily.id = activity.check_in_id
         WHERE daily.challenge_id = :id
           AND activity.activity_type <> :rest_type
         ORDER BY daily.check_in_date ASC, activity.id ASC'
    );
    $statement->execute([
        'id' => $challengeId,
        'rest_type' => 'rest_day',
    ]);
    $activities = $statement->fetchAll();

    $activityCounts = [];
    $movementByDate = [];
    $movementMinutes = 0;
    $durationEntryCount = 0;

    foreach ($activities as $activity) {
        $type = (string) $activity['activity_type'];
        $date = (string) $activity['check_in_date'];

        $activityCounts[$type] =
            ($activityCounts[$type] ?? 0) + 1;

        $movementByDate[$date] =
            $movementByDate[$date] ?? 0;

        if ($activity['duration_minutes'] !== null) {
            $minutes = (int) $activity['duration_minutes'];
            $movementMinutes += $minutes;
            $movementByDate[$date] += $minutes;
            $durationEntryCount++;
        }
    }

    // Consecutive dates with at least one non-rest activity.
    $longestStreak = 0;
    $currentStreak = 0;
    $previousDate = null;

    foreach (array_keys($movementByDate) as $date) {
        $calendarDate = challenge_calendar_date($date);

        $isConsecutive = $previousDate !== null &&
            $previousDate->modify('+1 day')->format('Y-m-d') === $date;

        $currentStreak = $isConsecutive
            ? $currentStreak + 1
            : 1;

        $longestStreak = max($longestStreak, $currentStreak);
        $previousDate = $calendarDate;
    }

    $statement = db()->prepare(
        'SELECT mood_entry.mood, COUNT(*) AS entry_count
         FROM check_in_moods AS mood_entry
         INNER JOIN check_ins AS daily
            ON daily.id = mood_entry.check_in_id
         WHERE daily.challenge_id = :id
         GROUP BY mood_entry.mood'
    );
    $statement->execute(['id' => $challengeId]);

    $moodCounts = [];

    foreach ($statement->fetchAll() as $mood) {
        $key = $mood['mood'] === 'struggling'
            ? 'rough'
            : (string) $mood['mood'];

        $moodCounts[$key] =
            ($moodCounts[$key] ?? 0) + (int) $mood['entry_count'];
    }

    $statement = db()->prepare(
        'SELECT COUNT(*)
         FROM check_in_notes AS note_entry
         INNER JOIN check_ins AS daily
            ON daily.id = note_entry.check_in_id
         WHERE daily.challenge_id = :id'
    );
    $statement->execute(['id' => $challengeId]);
    $noteCount = (int) $statement->fetchColumn();

    $measurements = [];

    foreach (['weight', 'waist'] as $field) {
        $entries = array_values(array_filter(
            $dailyRecords,
            static fn (array $record): bool =>
                $record[$field] !== null
        ));

        $count = count($entries);
        $first = $count > 0 ? $entries[0] : null;
        $last = $count > 0 ? $entries[$count - 1] : null;

        $measurements[$field] = [
            'count' => $count,
            'first' => $first !== null
                ? (float) $first[$field]
                : null,
            'last' => $last !== null
                ? (float) $last[$field]
                : null,
            'first_date' => $first['check_in_date'] ?? null,
            'last_date' => $last['check_in_date'] ?? null,
            'change' => $count >= 2
                ? round(
                    (float) $last[$field] -
                    (float) $first[$field],
                    1
                )
                : null,
        ];
    }

    // Return every tied leader in a consistent order.
    $leaders = static function (array $counts): array {
        if ($counts === []) {
            return [];
        }

        $maximum = max($counts);

        if ($maximum <= 0) {
            return [];
        }

        $keys = array_keys(array_filter(
            $counts,
            static fn (int $count): bool =>
                $count === $maximum
        ));

        sort($keys, SORT_STRING);

        return $keys;
    };

    return [
        'challenge' => $challenge,
        'logged_days' => count($dailyRecords),
        'activity_count' => count($activities),
        'active_days' => count($movementByDate),
        'movement_minutes' => $movementMinutes,
        'duration_entry_count' => $durationEntryCount,
        'top_activities' => $leaders($activityCounts),
        'top_activity_count' => $activityCounts !== []
            ? max($activityCounts)
            : 0,
        'busiest_dates' => $leaders($movementByDate),
        'busiest_minutes' => $movementByDate !== []
            ? max($movementByDate)
            : 0,
        'longest_activity_streak' => $longestStreak,
        'mood_count' => array_sum($moodCounts),
        'top_moods' => $leaders($moodCounts),
        'top_mood_count' => $moodCounts !== []
            ? max($moodCounts)
            : 0,
        'note_count' => $noteCount,
        'measurements' => $measurements,
    ];
}
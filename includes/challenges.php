<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Validate a calendar date using the application's timezone.
 */
function challenge_calendar_date(string $date): DateTimeImmutable
{
    $timezone = new DateTimeZone('America/New_York');

    $parsedDate = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $date,
        $timezone
    );

    if (
        $parsedDate === false ||
        $parsedDate->format('Y-m-d') !== $date
    ) {
        throw new InvalidArgumentException(
            'Please choose a valid calendar date.'
        );
    }

    return $parsedDate;
}

/**
 * Calculate Day 45 with the selected start date counted as Day 1.
 * This does not change any existing challenge.
 */
function challenge_end_date(string $startDate): string
{
    return challenge_calendar_date($startDate)
        ->modify('+44 days')
        ->format('Y-m-d');
}

/**
 * Find the challenge covering a daily record's date.
 *
 * Returns null for dates between challenges.
 * Includes completed challenges so historical dates retain
 * their correct challenge identity.
 * Rejects overlapping schedules rather than guessing.
 */
function challenge_id_for_date(string $date): ?int
{
    challenge_calendar_date($date);

    $statement = db()->prepare(
        "SELECT id
         FROM challenges
         WHERE start_date <= :range_start
           AND end_date >= :range_end
           AND status IN ('upcoming', 'active', 'completed', 'archived')
         ORDER BY start_date ASC, id ASC
         LIMIT 2"
    );

    $statement->execute([
        'range_start' => $date,
        'range_end' => $date,
    ]);

    $challengeIds = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (count($challengeIds) > 1) {
        throw new RuntimeException(
            'More than one challenge covers this date.'
        );
    }

    return $challengeIds === []
        ? null
        : (int) $challengeIds[0];
}
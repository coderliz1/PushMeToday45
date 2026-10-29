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


/**
 * Find the challenge that is still in progress or scheduled.
 * Completing a challenge will be a separate, explicit action.
 */
function challenge_current(): ?array
{
    $statement = db()->query(
        "SELECT *
         FROM challenges
         WHERE status IN ('active', 'upcoming')
         ORDER BY start_date ASC, id ASC
         LIMIT 2"
    );

    $challenges = $statement->fetchAll();

    if (count($challenges) > 1) {
        throw new RuntimeException(
            'More than one unfinished challenge exists.'
        );
    }

    return $challenges === []
        ? null
        : $challenges[0];
}


/**
 * Serialize challenge changes, including repeated form submissions.
 */
function challenge_write(callable $operation): int
{
    $connection = db();

    $lockStatement = $connection->query(
        "SELECT GET_LOCK('pmt45_challenge_write', 5)"
    );

    if ((int) $lockStatement->fetchColumn() !== 1) {
        throw new RuntimeException(
            'A challenge update is already running. Please try again.'
        );
    }

    try {
        $connection->beginTransaction();

        $result = $operation($connection);

        $connection->commit();

        return $result;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        throw $exception;
    } finally {
        $connection->query(
            "SELECT RELEASE_LOCK('pmt45_challenge_write')"
        );
    }
}

/**
 * Create a new challenge without changing existing challenges.
 */
function challenge_create(string $startDate): int
{
    $start = challenge_calendar_date($startDate);
    $today = new DateTimeImmutable(
        'today',
        new DateTimeZone('America/New_York')
    );

    if ($start < $today) {
        throw new InvalidArgumentException(
            'Choose today or a future date for your new challenge.'
        );
    }

    $endDate = challenge_end_date($startDate);
    $status = $start > $today ? 'upcoming' : 'active';

    return challenge_write(
        function (PDO $connection) use (
            $startDate,
            $endDate,
            $status
        ): int {
            $unfinished = $connection->query(
                "SELECT id
                 FROM challenges
                 WHERE status IN ('active', 'upcoming')
                 LIMIT 1"
            )->fetchColumn();

            if ($unfinished !== false) {
                throw new InvalidArgumentException(
                    'Finish your current challenge before starting another.'
                );
            }

            $overlap = $connection->prepare(
                'SELECT id
                 FROM challenges
                 WHERE start_date <= :new_end
                   AND end_date >= :new_start
                 LIMIT 1'
            );

            $overlap->execute([
                'new_end' => $endDate,
                'new_start' => $startDate,
            ]);

            if ($overlap->fetchColumn() !== false) {
                throw new InvalidArgumentException(
                    'These dates overlap an existing challenge.'
                );
            }

            $linkedRecords = $connection->prepare(
                'SELECT id
                 FROM check_ins
                 WHERE check_in_date BETWEEN :start_date AND :end_date
                   AND challenge_id IS NOT NULL
                 LIMIT 1'
            );

            $linkedRecords->execute([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            if ($linkedRecords->fetchColumn() !== false) {
                throw new InvalidArgumentException(
                    'An entry in these dates already belongs to another challenge.'
                );
            }

            $insert = $connection->prepare(
                'INSERT INTO challenges (
                    name,
                    target_days,
                    start_date,
                    end_date,
                    status
                 ) VALUES (
                    :name,
                    45,
                    :start_date,
                    :end_date,
                    :status
                 )'
            );

            $insert->execute([
                'name' => '45-Day Challenge',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
            ]);

            $challengeId = (int) $connection->lastInsertId();

            // Include entries saved before starting today's challenge.
            $link = $connection->prepare(
                'UPDATE check_ins
                 SET challenge_id = :challenge_id
                 WHERE challenge_id IS NULL
                   AND check_in_date BETWEEN :start_date AND :end_date'
            );

            $link->execute([
                'challenge_id' => $challengeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return $challengeId;
        }
    );
}

/**
 * Finish a challenge after its final calendar day has ended.
 * Its dates and recorded entries remain intact.
 */
function challenge_finish(int $challengeId): int
{
    return challenge_write(
        function (PDO $connection) use ($challengeId): int {
            $statement = $connection->prepare(
                'SELECT *
                 FROM challenges
                 WHERE id = :id
                 FOR UPDATE'
            );

            $statement->execute(['id' => $challengeId]);
            $challenge = $statement->fetch();

            if (!$challenge) {
                throw new InvalidArgumentException(
                    'That challenge could not be found.'
                );
            }

            // A repeated submission does not finish anything twice.
            if ($challenge['status'] === 'completed') {
                return $challengeId;
            }

            if (!in_array(
                $challenge['status'],
                ['active', 'upcoming'],
                true
            )) {
                throw new InvalidArgumentException(
                    'This challenge cannot be finished.'
                );
            }

            $now = new DateTimeImmutable(
                'now',
                new DateTimeZone('America/New_York')
            );

            $endDate = challenge_calendar_date(
                (string) $challenge['end_date']
            );

            if ($now->format('Y-m-d') <= $endDate->format('Y-m-d')) {
                throw new InvalidArgumentException(
                    'You can finish this challenge after ' .
                    $endDate->format('M j, Y') .
                    ' has ended.'
                );
            }

            $update = $connection->prepare(
                "UPDATE challenges
                 SET status = 'completed',
                     completed_at = :completed_at
                 WHERE id = :id"
            );

            $update->execute([
                'completed_at' => $now->format('Y-m-d H:i:s'),
                'id' => $challengeId,
            ]);

            return $challengeId;
        }
    );
}

/**
 * Determine today's application state from the stored challenge dates.
 *
 * An upcoming challenge becomes active by date automatically.
 * Existing dates and database statuses are never changed here.
 */
function challenge_app_state(): array
{
    $today = new DateTimeImmutable(
        'today',
        new DateTimeZone('America/New_York')
    );

    $challenge = challenge_current();

    if ($challenge === null) {
        return [
            'state' => 'between',
            'challenge' => null,
            'can_check_in' => false,
            'days_until_start' => null,
        ];
    }

    $startDate = challenge_calendar_date(
        (string) $challenge['start_date']
    );

    $endDate = challenge_calendar_date(
        (string) $challenge['end_date']
    );

    if ($endDate < $startDate) {
        throw new RuntimeException(
            'The challenge end date is before its start date.'
        );
    }

    if ($today < $startDate) {
        return [
            'state' => 'upcoming',
            'challenge' => $challenge,
            'can_check_in' => false,
            'days_until_start' =>
                (int) $today->diff($startDate)->format('%a'),
        ];
    }

    if ($today > $endDate) {
        return [
            'state' => 'awaiting_finish',
            'challenge' => $challenge,
            'can_check_in' => false,
            'days_until_start' => null,
        ];
    }

    return [
        'state' => 'active',
        'challenge' => $challenge,
        'can_check_in' => true,
        'days_until_start' => 0,
    ];
}

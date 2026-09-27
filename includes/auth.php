<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const AUTH_COOKIE_NAME = 'pmt45_auth';
const AUTH_STATE_SESSION_NAME = 'pmt45_state';


function auth_start_state_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(AUTH_STATE_SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


function auth_csrf_token(): string
{
    auth_start_state_session();

    if (
        !isset($_SESSION['auth_csrf']) ||
        !is_string($_SESSION['auth_csrf'])
    ) {
        $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['auth_csrf'];
}


function auth_csrf_is_valid(string $submittedToken): bool
{
    auth_start_state_session();

    $storedToken = $_SESSION['auth_csrf'] ?? '';

    return is_string($storedToken) &&
        $storedToken !== '' &&
        hash_equals($storedToken, $submittedToken);
}


function auth_requester_hash(): string
{
    $address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    return hash('sha256', 'pushmetoday45|' . $address);
}


function auth_record_rate_event(string $eventType): void
{
    $statement = db()->prepare(
        'INSERT INTO auth_rate_events (
            event_type,
            requester_hash
         ) VALUES (
            :event_type,
            :requester_hash
         )'
    );

    $statement->execute([
        'event_type' => $eventType,
        'requester_hash' => auth_requester_hash(),
    ]);
}


function auth_rate_limit_exceeded(
    string $eventType,
    int $maximumEvents,
    int $windowMinutes
): bool {
    $cutoff = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->modify('-' . $windowMinutes . ' minutes')
        ->format('Y-m-d H:i:s');

    $statement = db()->prepare(
        'SELECT COUNT(*)
         FROM auth_rate_events
         WHERE event_type = :event_type
           AND requester_hash = :requester_hash
           AND created_at >= :cutoff'
    );

    $statement->execute([
        'event_type' => $eventType,
        'requester_hash' => auth_requester_hash(),
        'cutoff' => $cutoff,
    ]);

    return (int) $statement->fetchColumn() >= $maximumEvents;
}


function auth_cleanup_expired_records(): void
{
    db()->exec(
        "DELETE FROM magic_link_tokens
         WHERE expires_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)
            OR (
                used_at IS NOT NULL
                AND used_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)
            )"
    );

    db()->exec(
        "DELETE FROM auth_sessions
         WHERE expires_at < UTC_TIMESTAMP()
            OR (
                revoked_at IS NOT NULL
                AND revoked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY)
            )"
    );

    db()->exec(
        "DELETE FROM auth_rate_events
         WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)"
    );
}

function auth_normalize_email(string $email): string
{
    return strtolower(trim($email));
}


function auth_request_magic_link(string $submittedEmail): void
{
    auth_cleanup_expired_records();

    $limited = auth_rate_limit_exceeded(
        'magic_link_request',
        5,
        15
    );

    auth_record_rate_event('magic_link_request');

    if ($limited) {
        return;
    }

    $config = app_config();

    $authorizedEmail = auth_normalize_email(
        (string) ($config['authorized_email'] ?? '')
    );

    $submittedEmail = auth_normalize_email($submittedEmail);

    if (
        $authorizedEmail === '' ||
        !filter_var($submittedEmail, FILTER_VALIDATE_EMAIL) ||
        !hash_equals($authorizedEmail, $submittedEmail)
    ) {
        return;
    }

    $plainToken = rtrim(
        strtr(
            base64_encode(random_bytes(32)),
            '+/',
            '-_'
        ),
        '='
    );

    $tokenHash = hash('sha256', $plainToken);

    $lifetimeMinutes = (int) (
        $config['magic_link_lifetime_minutes'] ?? 15
    );

    $lifetimeMinutes = max(10, min(30, $lifetimeMinutes));

    $expiresAt = (
        new DateTimeImmutable('now', new DateTimeZone('UTC'))
    )
        ->modify('+' . $lifetimeMinutes . ' minutes')
        ->format('Y-m-d H:i:s');

    db()->exec(
        'DELETE FROM magic_link_tokens
         WHERE used_at IS NULL'
    );

    $statement = db()->prepare(
        'INSERT INTO magic_link_tokens (
            token_hash,
            requester_hash,
            expires_at
         ) VALUES (
            :token_hash,
            :requester_hash,
            :expires_at
         )'
    );

    $statement->execute([
        'token_hash' => $tokenHash,
        'requester_hash' => auth_requester_hash(),
        'expires_at' => $expiresAt,
    ]);

    $tokenId = (int) db()->lastInsertId();

    $magicLink =
        rtrim((string) $config['app_url'], '/') .
        '/?page=verify-login&token=' .
        rawurlencode($plainToken);

    try {
        send_magic_link_email(
            $authorizedEmail,
            $magicLink
        );
    } catch (Throwable $exception) {
        $deleteStatement = db()->prepare(
            'DELETE FROM magic_link_tokens
             WHERE id = :id'
        );

        $deleteStatement->execute([
            'id' => $tokenId,
        ]);

        error_log(
            'Magic-link email failed: ' .
            $exception->getMessage()
        );
    }
}

function auth_token_format_is_valid(string $token): bool
{
    return preg_match(
        '/^[A-Za-z0-9_-]{43,128}$/',
        $token
    ) === 1;
}


function auth_set_login_cookie(
    string $plainSessionToken,
    int $expiresTimestamp
): void {
    setcookie(
        AUTH_COOKIE_NAME,
        $plainSessionToken,
        [
            'expires' => $expiresTimestamp,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}


function auth_complete_magic_login(string $plainToken): bool
{
    auth_cleanup_expired_records();

    if (
        auth_rate_limit_exceeded(
            'invalid_token',
            10,
            15
        )
    ) {
        return false;
    }

    if (!auth_token_format_is_valid($plainToken)) {
        auth_record_rate_event('invalid_token');

        return false;
    }

    $tokenHash = hash('sha256', $plainToken);
    $connection = db();

    try {
        $connection->beginTransaction();

        $statement = $connection->prepare(
            'SELECT
                id,
                expires_at,
                used_at
             FROM magic_link_tokens
             WHERE token_hash = :token_hash
             LIMIT 1
             FOR UPDATE'
        );

        $statement->execute([
            'token_hash' => $tokenHash,
        ]);

        $tokenRecord = $statement->fetch();

        $now = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        if (
            !$tokenRecord ||
            $tokenRecord['used_at'] !== null ||
            new DateTimeImmutable(
                $tokenRecord['expires_at'],
                new DateTimeZone('UTC')
            ) <= $now
        ) {
            $connection->rollBack();
            auth_record_rate_event('invalid_token');

            return false;
        }

        $useStatement = $connection->prepare(
            'UPDATE magic_link_tokens
             SET used_at = UTC_TIMESTAMP()
             WHERE id = :id
               AND used_at IS NULL'
        );

        $useStatement->execute([
            'id' => $tokenRecord['id'],
        ]);

        if ($useStatement->rowCount() !== 1) {
            $connection->rollBack();
            auth_record_rate_event('invalid_token');

            return false;
        }

        $plainSessionToken = rtrim(
            strtr(
                base64_encode(random_bytes(32)),
                '+/',
                '-_'
            ),
            '='
        );

        $sessionTokenHash = hash(
            'sha256',
            $plainSessionToken
        );

        $config = app_config();

        $persistentDays = (int) (
            $config['persistent_login_days'] ?? 30
        );

        $persistentDays = max(
            1,
            min(90, $persistentDays)
        );

        $sessionExpires = $now->modify(
            '+' . $persistentDays . ' days'
        );

        $sessionStatement = $connection->prepare(
            'INSERT INTO auth_sessions (
                token_hash,
                expires_at,
                last_used_at
             ) VALUES (
                :token_hash,
                :expires_at,
                UTC_TIMESTAMP()
             )'
        );

        $sessionStatement->execute([
            'token_hash' => $sessionTokenHash,
            'expires_at' => $sessionExpires->format(
                'Y-m-d H:i:s'
            ),
        ]);

        $connection->commit();

        auth_start_state_session();
        session_regenerate_id(true);

        auth_set_login_cookie(
            $plainSessionToken,
            $sessionExpires->getTimestamp()
        );

        return true;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        error_log(
            'Magic-link verification failed: ' .
            $exception->getMessage()
        );

        return false;
    }
}

function auth_clear_login_cookie(): void
{
    setcookie(
        AUTH_COOKIE_NAME,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    unset($_COOKIE[AUTH_COOKIE_NAME]);
}


function auth_is_authenticated(): bool
{
    $plainSessionToken = trim(
        (string) ($_COOKIE[AUTH_COOKIE_NAME] ?? '')
    );

    if (
        $plainSessionToken === '' ||
        !auth_token_format_is_valid($plainSessionToken)
    ) {
        return false;
    }

    $sessionTokenHash = hash(
        'sha256',
        $plainSessionToken
    );

    $statement = db()->prepare(
        'SELECT id
         FROM auth_sessions
         WHERE token_hash = :token_hash
           AND revoked_at IS NULL
           AND expires_at > UTC_TIMESTAMP()
         LIMIT 1'
    );

    $statement->execute([
        'token_hash' => $sessionTokenHash,
    ]);

    $sessionId = $statement->fetchColumn();

    if ($sessionId === false) {
        auth_clear_login_cookie();

        return false;
    }

    $updateStatement = db()->prepare(
        'UPDATE auth_sessions
         SET last_used_at = UTC_TIMESTAMP()
         WHERE id = :id'
    );

    $updateStatement->execute([
        'id' => $sessionId,
    ]);

    return true;
}


function auth_require_login(): void
{
    if (auth_is_authenticated()) {
        return;
    }

    header('Location: /?page=login');
    exit;
}


function auth_logout(): void
{
    $plainSessionToken = trim(
        (string) ($_COOKIE[AUTH_COOKIE_NAME] ?? '')
    );

    if (auth_token_format_is_valid($plainSessionToken)) {
        $statement = db()->prepare(
            'UPDATE auth_sessions
             SET revoked_at = UTC_TIMESTAMP()
             WHERE token_hash = :token_hash
               AND revoked_at IS NULL'
        );

        $statement->execute([
            'token_hash' => hash(
                'sha256',
                $plainSessionToken
            ),
        ]);
    }

    auth_clear_login_cookie();

    auth_start_state_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $parameters['path'],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    session_destroy();
}
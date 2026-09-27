<?php

declare(strict_types=1);

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

function app_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $configPath = dirname(__DIR__, 2) . '/pmt45-config.php';

    if (!is_file($configPath)) {
        throw new RuntimeException(
            'Application configuration file was not found.'
        );
    }

    $loadedConfig = require $configPath;

    if (!is_array($loadedConfig)) {
        throw new RuntimeException(
            'Application configuration file is invalid.'
        );
    }

    $config = $loadedConfig;

    return $config;
}

function openai_api_key(): string
{
    $config = app_config();
    $apiKey = trim((string) ($config['openai_api_key'] ?? ''));

    if ($apiKey === '') {
        throw new RuntimeException(
            'OpenAI API key is not configured.'
        );
    }

    return $apiKey;
}

function openai_generate_text(
    string $instructions,
    string $input,
    int $maxOutputTokens = 350
): string {
    if (!function_exists('curl_init')) {
        throw new RuntimeException(
            'The PHP cURL extension is not available.'
        );
    }

    $payload = [
        'model' => 'gpt-5.4-mini',
        'instructions' => $instructions,
        'input' => $input,
        'max_output_tokens' => $maxOutputTokens,
        'reasoning' => [
            'effort' => 'none',
        ],
        'store' => false,
    ];

    $curl = curl_init('https://api.openai.com/v1/responses');

    if ($curl === false) {
        throw new RuntimeException(
            'The OpenAI request could not be initialized.'
        );
    }

    curl_setopt_array(
        $curl,
        [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . openai_api_key(),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            ),
        ]
    );

    $rawResponse = curl_exec($curl);

    if ($rawResponse === false) {
        $curlError = curl_error($curl);
        curl_close($curl);

        throw new RuntimeException(
            'The OpenAI request failed: ' . $curlError
        );
    }

    $statusCode = (int) curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    curl_close($curl);

    $responseData = json_decode(
        $rawResponse,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException(
            'OpenAI returned HTTP status ' . $statusCode . '.'
        );
    }

    foreach ($responseData['output'] ?? [] as $outputItem) {
        foreach ($outputItem['content'] ?? [] as $contentItem) {
            if (
                ($contentItem['type'] ?? '') === 'output_text' &&
                isset($contentItem['text'])
            ) {
                $generatedText = trim(
                    (string) $contentItem['text']
                );

                if ($generatedText !== '') {
                    return $generatedText;
                }
            }
        }
    }

    throw new RuntimeException(
        'OpenAI returned no usable text.'
    );
}


function send_magic_link_email(
    string $recipientEmail,
    string $magicLink
): void {
    $config = app_config();

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = (string) $config['smtp_host'];
    $mail->Port = (int) $config['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['smtp_username'];
    $mail->Password = (string) $config['smtp_password'];

    if (($config['smtp_encryption'] ?? '') === 'tls') {
        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    }

    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        (string) $config['mail_from_email'],
        (string) $config['mail_from_name']
    );

    $mail->addAddress($recipientEmail);

    $safeMagicLink = htmlspecialchars(
        $magicLink,
        ENT_QUOTES,
        'UTF-8'
    );

    $mail->isHTML(true);
    $mail->Subject = 'Your PushMeToday45 login link';

    $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#fff8fb;font-family:Arial,sans-serif;color:#24202a;">
    <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:24px;padding:32px;box-shadow:0 12px 32px rgba(79,48,88,0.12);">
        <h1 style="margin:0 0 16px;font-size:26px;">
            PushMeToday45 🔥
        </h1>

        <p style="font-size:17px;line-height:1.6;">
            Your secure login link is ready.
        </p>

        <p style="margin:28px 0;">
            <a
                href="{$safeMagicLink}"
                style="display:inline-block;background:#8f3f84;color:#ffffff;text-decoration:none;font-weight:bold;padding:15px 24px;border-radius:999px;"
            >
                OPEN PUSHMETODAY45
            </a>
        </p>

        <p style="font-size:14px;line-height:1.6;color:#655d69;">
            This link expires in 15 minutes and can only be used once.
        </p>

        <p style="font-size:14px;line-height:1.6;color:#655d69;">
            If you did not request this link, you can ignore this email.
        </p>
    </div>
</body>
</html>
HTML;

    $mail->AltBody =
        "PushMeToday45\n\n" .
        "Your secure login link is ready:\n" .
        $magicLink . "\n\n" .
        "This link expires in 15 minutes and can only be used once.\n\n" .
        "If you did not request this link, you can ignore this email.";

    $mail->send();
}

function db(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = app_config();

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['database']
    );

    $connection = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}
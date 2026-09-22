<?php

declare(strict_types=1);

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
<?php
declare(strict_types=1);

function loadEnv(): void {
    $paths = [
        __DIR__ . '/.env',
        __DIR__ . '/../.env',
        '/home/ubuntu/game panel/.env'
    ];
    foreach ($paths as $path) {
        if (!file_exists($path)) continue;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) continue;
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim(trim($parts[1]), '"\'');
                if (getenv($key) === false) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                }
            }
        }
    }
}
loadEnv();

function isInstalled(): bool {
    return file_exists(__DIR__ . '/installed.lock') || 
           file_exists(__DIR__ . '/../installed.lock') ||
           file_exists('/home/ubuntu/game panel/installed.lock');
}

class ApiClient {
    private string $baseUrl;

    public function __construct(?string $baseUrl = null) {
        if ($baseUrl === null) {
            $envUrl = getenv('PANEL_API_URL') ?: getenv('API_URL');
            $this->baseUrl = rtrim($envUrl ?: 'http://127.0.0.1:8080', '/');
        } else {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
    }

    public function get(string $endpoint): array {
        $ch = curl_init("{$this->baseUrl}{$endpoint}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300 && $response) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : [];
        }
        return [];
    }

    public function post(string $endpoint, array $payload): array {
        $ch = curl_init("{$this->baseUrl}{$endpoint}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json']
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$response, true);
        return is_array($data) ? $data : [];
    }
}

function e(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

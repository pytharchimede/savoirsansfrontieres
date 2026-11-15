<?php
declare(strict_types=1);

function ssf_env(string $name, string $default = ''): string {
    static $loaded = null;
    if ($loaded === null) {
        $loaded = [];
        $candidates = [
            dirname(__DIR__) . '/.env',
            '/home/ifmapci/ssf-app/.env',
        ];
        foreach ($candidates as $file) {
            if (!is_readable($file)) continue;
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $decoded = json_decode($value, true);
                    if (is_string($decoded)) $value = $decoded;
                } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    $value = substr($value, 1, -1);
                }
                $loaded[$key] = $value;
            }
            break;
        }
    }
    $environment = getenv($name);
    return $environment !== false ? $environment : ($loaded[$name] ?? $default);
}

function ssf_db(): PDO {
    return new PDO(
        'mysql:host=' . ssf_env('DB_HOST', 'localhost') .
        ';port=' . ssf_env('DB_PORT', '3306') .
        ';dbname=' . ssf_env('DB_DATABASE') . ';charset=utf8mb4',
        ssf_env('DB_USERNAME'),
        ssf_env('DB_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

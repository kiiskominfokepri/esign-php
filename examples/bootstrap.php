<?php

require_once __DIR__ . '/../vendor/autoload.php';

function esign_env(string $key, ?string $default = null): ?string
{
    static $loaded = false;

    if (!$loaded) {
        $envFile = __DIR__ . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\"'");
                if (getenv($name) === false) {
                    putenv($name . '=' . $value);
                    $_ENV[$name] = $value;
                }
            }
        }
        $loaded = true;
    }

    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function esign_require_env(string ...$keys): array
{
    $values = [];
    $missing = [];

    foreach ($keys as $key) {
        $value = esign_env($key);
        if ($value === null) {
            $missing[] = $key;
        }
        $values[$key] = $value;
    }

    if ($missing !== []) {
        fwrite(STDERR, "Missing environment variables: " . implode(', ', $missing) . PHP_EOL);
        fwrite(STDERR, "Copy examples/.env.example to examples/.env and fill credentials." . PHP_EOL);
        exit(1);
    }

    return $values;
}

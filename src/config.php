<?php

declare(strict_types=1);

function loadEnv(string $file): void
{
    if (!is_file($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function envOrFail(string $key): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        throw new RuntimeException('Missing required env var: ' . $key);
    }

    return $value;
}

function envOrDefault(string $key, string $default): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

function createPdo(string $host, string $port, string $db, string $user, string $pass): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function masterPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = createPdo(
        envOrFail('DB_MASTER_HOST'),
        getenv('DB_MASTER_PORT') ?: '3306',
        envOrFail('DB_MASTER_NAME'),
        envOrFail('DB_MASTER_USER'),
        envOrFail('DB_MASTER_PASS')
    );

    return $pdo;
}

function replicaPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // If replica variables are absent, fallback to master connection.
    $pdo = createPdo(
        envOrDefault('DB_REPLICA_HOST', envOrFail('DB_MASTER_HOST')),
        envOrDefault('DB_REPLICA_PORT', envOrDefault('DB_MASTER_PORT', '3306')),
        envOrDefault('DB_REPLICA_NAME', envOrFail('DB_MASTER_NAME')),
        envOrDefault('DB_REPLICA_USER', envOrFail('DB_MASTER_USER')),
        envOrDefault('DB_REPLICA_PASS', envOrFail('DB_MASTER_PASS'))
    );

    return $pdo;
}

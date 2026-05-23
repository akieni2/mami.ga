<?php

/**
 * PHPUnit bootstrap: tests always run on MySQL (same stack as production).
 */
require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/.env')) {
    Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

$setEnv = static function (string $key, string $value): void {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv("{$key}={$value}");
};

$setEnv('DB_CONNECTION', $_ENV['DB_TEST_CONNECTION'] ?? 'mysql');
$setEnv('DB_HOST', $_ENV['DB_TEST_HOST'] ?? $_ENV['DB_HOST'] ?? '127.0.0.1');
$setEnv('DB_PORT', $_ENV['DB_TEST_PORT'] ?? $_ENV['DB_PORT'] ?? '3306');
$setEnv('DB_DATABASE', $_ENV['DB_TEST_DATABASE'] ?? 'mami_ga_testing');
$setEnv('DB_USERNAME', $_ENV['DB_TEST_USERNAME'] ?? $_ENV['DB_USERNAME'] ?? 'root');
$setEnv('DB_PASSWORD', $_ENV['DB_TEST_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '');
$setEnv('DB_URL', '');

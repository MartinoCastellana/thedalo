<?php
declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/RiskEngine.php';
require_once __DIR__ . '/src/Dalo.php';
require_once __DIR__ . '/src/ConfigManager.php';

$env = static function (string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

$configManager = new Dalo\ConfigManager(__DIR__ . '/config');
$storedConfig = $configManager->isConfigured() ? $configManager->load() : [];
$database = new Dalo\Database([
    'host' => $env('DB_HOST', $storedConfig['db_host'] ?? '127.0.0.1'),
    'port' => $env('DB_PORT', (string)($storedConfig['db_port'] ?? '5432')),
    'name' => $env('DB_NAME', $storedConfig['db_name'] ?? 'dalo'),
    'user' => $env('DB_USER', $storedConfig['db_user'] ?? 'dalo'),
    'password' => $env('DB_PASSWORD', $storedConfig['db_password'] ?? 'dalo_dev_password'),
]);
$dalo = new Dalo\Dalo($database, new Dalo\RiskEngine());

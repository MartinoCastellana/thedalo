<?php
declare(strict_types=1);

namespace Dalo;

final class WordPressConfig
{
    public static function find(string $pluginDirectory): ?string
    {
        $candidates = [
            dirname($pluginDirectory, 3) . '/wp-config.php',
            dirname($pluginDirectory, 4) . '/wp-config.php',
            dirname($pluginDirectory, 3) . '/../wp-config.php',
        ];
        foreach (array_unique($candidates) as $candidate) {
            $real = realpath($candidate);
            if ($real !== false && is_readable($real)) return $real;
        }
        return null;
    }

    public static function parse(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) throw new \RuntimeException('Impossibile leggere wp-config.php.');
        $read = static function (string $name) use ($contents): ?string {
            $pattern = '/define\\s*\\(\\s*[\'\"]' . preg_quote($name, '/') . '[\'\"]\\s*,\\s*[\'\"]([^\'\"]*)[\'\"]\\s*\\)/i';
            return preg_match($pattern, $contents, $matches) ? stripcslashes($matches[1]) : null;
        };
        $host = $read('DB_HOST');
        return ['db_name' => $read('DB_NAME'), 'db_user' => $read('DB_USER'), 'db_password' => $read('DB_PASSWORD'), 'db_host' => $host ?: '127.0.0.1', 'db_port' => str_contains((string)$host, ':') ? substr(strrchr((string)$host, ':'), 1) : '5432'];
    }
}

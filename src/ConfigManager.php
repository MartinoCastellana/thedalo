<?php
declare(strict_types=1);

namespace Dalo;

final class ConfigManager
{
    public function __construct(private string $directory)
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0700, true);
        }
    }

    public function isConfigured(): bool
    {
        return is_file($this->directory . '/.key') && is_file($this->directory . '/settings.enc');
    }

    public function save(array $settings): void
    {
        if (!extension_loaded('sodium')) {
            throw new \RuntimeException('L’estensione PHP Sodium è necessaria per cifrare la configurazione.');
        }
        $keyPath = $this->directory . '/.key';
        $key = is_file($keyPath) ? file_get_contents($keyPath) : sodium_crypto_secretbox_keygen();
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException('Chiave applicativa non valida.');
        }
        if (!is_file($keyPath)) {
            $this->atomicWrite($keyPath, $key, 0600);
        }
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox(json_encode($settings, JSON_THROW_ON_ERROR), $nonce, $key);
        $this->atomicWrite($this->directory . '/settings.enc', base64_encode($nonce . $ciphertext), 0600);
    }

    public function load(): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('The DALO non è ancora configurato.');
        }
        $key = file_get_contents($this->directory . '/.key');
        $encoded = file_get_contents($this->directory . '/settings.enc');
        if ($key === false || $encoded === false) throw new \RuntimeException('Configurazione incompleta.');
        $raw = base64_decode($encoded, true);
        $nonce = substr($raw ?: '', 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($raw ?: '', SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($plain === false) throw new \RuntimeException('Configurazione cifrata non leggibile.');
        return json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
    }

    private function atomicWrite(string $path, string $contents, int $mode): void
    {
        $temporary = $path . '.tmp.' . bin2hex(random_bytes(5));
        file_put_contents($temporary, $contents, LOCK_EX);
        chmod($temporary, $mode);
        rename($temporary, $path);
    }
}

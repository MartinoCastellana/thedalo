<?php
declare(strict_types=1);

namespace Dalo;

final class RiskEngine
{
    public function score(array $input): array
    {
        $score = 0;
        $reasons = [];
        $add = static function (int $points, string $reason) use (&$score, &$reasons): void {
            $score += $points;
            $reasons[] = ['points' => $points, 'reason' => $reason];
        };

        if (($input['failed_auth_count'] ?? 0) >= 3) $add(min(50, (int)$input['failed_auth_count'] * 10), 'ripetuti fallimenti di autenticazione');
        if (($input['requests_last_minute'] ?? 0) >= 20) $add(25, 'frequenza elevata nell’ultimo minuto');
        if (($input['requests_last_minute'] ?? 0) >= 60) $add(25, 'frequenza automatizzata molto elevata');
        if (($input['csrf_valid'] ?? true) === false) $add(20, 'token CSRF assente o non valido');
        if (($input['token_reused'] ?? false) === true) $add(30, 'riutilizzo di un token monouso');
        if (($input['honeypot_hit'] ?? false) === true) $add(40, 'accesso a endpoint esca');
        if ($this->looksAutomated((string)($input['user_agent'] ?? ''))) $add(15, 'user-agent associato a client automatizzati');
        if (($input['path_entropy'] ?? 0) >= 3) $add(10, 'scansione di percorsi non coerenti');

        $score = min(200, $score);
        $level = $score >= 100 ? 'critical' : ($score >= 60 ? 'suspicious' : ($score >= 30 ? 'guarded' : 'normal'));
        $labyrinth = match ($level) {
            'critical' => 'minotauro',
            'suspicious' => 'arianna',
            'guarded' => 'specchio',
            default => 'none',
        };

        return compact('score', 'level', 'labyrinth', 'reasons');
    }

    private function looksAutomated(string $userAgent): bool
    {
        return (bool)preg_match('/\b(curl|wget|python-requests|go-http-client|scrapy|bot|scanner|masscan|nikto)\b/i', $userAgent);
    }
}

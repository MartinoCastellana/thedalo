<?php
declare(strict_types=1);

namespace Dalo;

final class Dalo
{
    public function __construct(private Database $database, private RiskEngine $engine)
    {
    }

    public function inspect(array $request): array
    {
        $pdo = $this->database->pdo();
        $requestId = $request['request_id'] ?? bin2hex(random_bytes(16));
        $sessionHash = $request['session_hash'] ?? null;
        $decision = $this->engine->score($request);

        $stmt = $pdo->prepare('INSERT INTO request_events
            (request_id, ip, method, path, user_agent, session_hash, account_hint, status_code, latency_ms, failed_auth, csrf_valid, token_reused, honeypot_hit, features)
            VALUES (:request_id, :ip, :method, :path, :user_agent, :session_hash, :account_hint, :status_code, :latency_ms, :failed_auth, :csrf_valid, :token_reused, :honeypot_hit, :features)
            RETURNING id');
        $stmt->execute([
            'request_id' => $this->uuidFromHex($requestId),
            'ip' => $request['ip'], 'method' => $request['method'], 'path' => $request['path'],
            'user_agent' => $request['user_agent'] ?? '', 'session_hash' => $sessionHash,
            'account_hint' => $request['account_hint'] ?? null, 'status_code' => $request['status_code'] ?? 200,
            'latency_ms' => $request['latency_ms'] ?? 0, 'failed_auth' => $request['failed_auth'] ?? false,
            'csrf_valid' => $request['csrf_valid'] ?? null, 'token_reused' => $request['token_reused'] ?? false,
            'honeypot_hit' => $request['honeypot_hit'] ?? false, 'features' => json_encode($request['features'] ?? []),
        ]);
        $eventId = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('INSERT INTO risk_decisions (request_event_id, score, level, labyrinth, reasons) VALUES (:event, :score, :level, :labyrinth, :reasons)');
        $stmt->execute(['event' => $eventId, 'score' => $decision['score'], 'level' => $decision['level'], 'labyrinth' => $decision['labyrinth'], 'reasons' => json_encode($decision['reasons'])]);

        if ($sessionHash !== null && $decision['labyrinth'] !== 'none') {
            $stmt = $pdo->prepare('INSERT INTO labyrinth_sessions (session_hash, labyrinth, metadata) VALUES (:session_hash, :labyrinth, :metadata)');
            $stmt->execute(['session_hash' => $sessionHash, 'labyrinth' => $decision['labyrinth'], 'metadata' => json_encode(['request_id' => $requestId])]);
        }
        return ['event_id' => $eventId, 'request_id' => $requestId] + $decision;
    }

    private function uuidFromHex(string $value): string
    {
        $hex = preg_replace('/[^a-f0-9]/i', '', $value) ?? '';
        $hex = str_pad(substr($hex, 0, 32), 32, '0');
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
}

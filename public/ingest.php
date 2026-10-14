<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?: [];
$request = [
    'request_id' => $body['request_id'] ?? bin2hex(random_bytes(16)),
    'ip' => filter_var($body['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), FILTER_VALIDATE_IP) ?: '127.0.0.1',
    'method' => strtoupper(substr((string)($body['method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET'), 0, 10)),
    'path' => substr((string)($body['path'] ?? $_SERVER['REQUEST_URI'] ?? '/'), 0, 2000),
    'user_agent' => substr((string)($body['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000),
] + $body;
try { echo json_encode($dalo->inspect($request), JSON_THROW_ON_ERROR); }
catch (Throwable $error) { http_response_code(500); echo json_encode(['error' => 'DALO storage unavailable']); }

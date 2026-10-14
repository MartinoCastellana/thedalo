<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/RiskEngine.php';

$iterations = (int)($argv[1] ?? 100000);
$engine = new Dalo\RiskEngine();
$input = ['failed_auth_count' => 8, 'requests_last_minute' => 80, 'csrf_valid' => false, 'token_reused' => true, 'honeypot_hit' => true, 'user_agent' => 'curl/8.0', 'path_entropy' => 4];
$start = hrtime(true);
for ($i = 0; $i < $iterations; $i++) $result = $engine->score($input);
$elapsedNs = hrtime(true) - $start;
printf("RiskEngine benchmark\niterations: %d\nscore: %d\nlabyrinth: %s\nelapsed_ms: %.3f\noperations_per_second: %.0f\n", $iterations, $result['score'], $result['labyrinth'], $elapsedNs / 1_000_000, $iterations / ($elapsedNs / 1_000_000_000));

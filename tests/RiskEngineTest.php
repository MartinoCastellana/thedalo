<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/RiskEngine.php';

use Dalo\RiskEngine;

$engine = new RiskEngine();

$cases = [
    ['input' => [], 'score' => 0, 'labyrinth' => 'none'],
    ['input' => ['requests_last_minute' => 20], 'score' => 25, 'labyrinth' => 'none'],
    ['input' => ['failed_auth_count' => 3, 'csrf_valid' => false], 'score' => 50, 'labyrinth' => 'specchio'],
    ['input' => ['failed_auth_count' => 4, 'requests_last_minute' => 20, 'token_reused' => true], 'score' => 95, 'labyrinth' => 'arianna'],
    ['input' => ['failed_auth_count' => 8, 'requests_last_minute' => 80, 'honeypot_hit' => true], 'score' => 140, 'labyrinth' => 'minotauro'],
];

foreach ($cases as $number => $case) {
    $first = $engine->score($case['input']);
    $second = $engine->score($case['input']);
    if ($first['score'] !== $case['score'] || $first['labyrinth'] !== $case['labyrinth'] || $first !== $second) {
        fwrite(STDERR, "Case " . ($number + 1) . " failed: " . json_encode($first) . PHP_EOL);
        exit(1);
    }
}

echo "RiskEngine tests: OK (" . count($cases) . " cases)" . PHP_EOL;

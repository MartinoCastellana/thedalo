<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/ConfigManager.php';
$earlyConfig = new Dalo\ConfigManager(dirname(__DIR__) . '/config');
if (!$earlyConfig->isConfigured() && !getenv('DB_USER')) {
    header('Location: setup.php');
    exit;
}
require_once dirname(__DIR__) . '/bootstrap.php';

$pdo = $database->pdo();
$metrics = $pdo->query("SELECT
  (SELECT count(*) FROM request_events WHERE occurred_at > now() - interval '24 hours') AS requests,
  (SELECT count(*) FROM risk_decisions WHERE created_at > now() - interval '24 hours' AND level IN ('suspicious','critical')) AS suspicious,
  (SELECT count(*) FROM risk_decisions WHERE created_at > now() - interval '24 hours' AND labyrinth = 'minotauro') AS minotauro,
  (SELECT coalesce(round(avg(score), 1), 0) FROM risk_decisions WHERE created_at > now() - interval '24 hours') AS avg_score")->fetch();
$events = $pdo->query("SELECT e.occurred_at, e.ip, e.method, e.path, e.status_code, r.score, r.level, r.labyrinth
  FROM request_events e JOIN risk_decisions r ON r.request_event_id = e.id ORDER BY e.occurred_at DESC LIMIT 50")->fetchAll();
function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>The DALO — Dashboard</title>
<style>
:root{color-scheme:dark;--bg:#0b1020;--panel:#121a2f;--line:#263451;--ink:#edf3ff;--muted:#94a3bf;--gold:#eab464;--red:#f07878;--green:#7dd3a5}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top right,#17294b,transparent 42%),var(--bg);font:14px system-ui,sans-serif;color:var(--ink)}main{max-width:1250px;margin:auto;padding:34px 22px}.brand{display:flex;justify-content:space-between;align-items:end;margin-bottom:28px}.brand h1{margin:0;font-size:32px;letter-spacing:.04em}.brand p{color:var(--muted);margin:7px 0 0}.badge{border:1px solid #4f638a;padding:7px 11px;border-radius:999px;color:var(--gold)}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}.card,.table-wrap{background:rgba(18,26,47,.9);border:1px solid var(--line);border-radius:14px}.card{padding:18px}.label{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.08em}.value{font-size:30px;margin-top:8px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:800px}th,td{text-align:left;padding:13px 15px;border-bottom:1px solid var(--line)}th{color:var(--muted);font-size:12px;text-transform:uppercase}td{font-family:ui-monospace,monospace;font-size:12px}.level-critical,.lab-minotauro{color:var(--red)}.level-suspicious,.lab-arianna{color:var(--gold)}.level-guarded,.lab-specchio{color:#a9b8ff}.level-normal{color:var(--green)}.empty{padding:30px;color:var(--muted)}@media(max-width:800px){.cards{grid-template-columns:repeat(2,1fr)}.brand{display:block}.badge{display:inline-block;margin-top:15px}}
</style></head><body><main><div class="brand"><div><h1>THE DALO</h1><p>Deceptive Authentication Layer Orchestrator · Sistema deterministico</p></div><div class="badge">● PostgreSQL / live</div></div>
<section class="cards"><div class="card"><div class="label">Richieste · 24h</div><div class="value"><?=h($metrics['requests'])?></div></div><div class="card"><div class="label">Sospette · 24h</div><div class="value"><?=h($metrics['suspicious'])?></div></div><div class="card"><div class="label">Nel Minotauro · 24h</div><div class="value"><?=h($metrics['minotauro'])?></div></div><div class="card"><div class="label">Risk score medio</div><div class="value"><?=h($metrics['avg_score'])?></div></div></section>
<div class="table-wrap"><table><thead><tr><th>Ora</th><th>IP</th><th>Metodo</th><th>Percorso</th><th>Status</th><th>Score</th><th>Livello</th><th>Labirinto</th></tr></thead><tbody><?php foreach ($events as $event): ?><tr><td><?=h($event['occurred_at'])?></td><td><?=h($event['ip'])?></td><td><?=h($event['method'])?></td><td><?=h($event['path'])?></td><td><?=h($event['status_code'])?></td><td><?=h($event['score'])?></td><td class="level-<?=h($event['level'])?>"><?=h($event['level'])?></td><td class="lab-<?=h($event['labyrinth'])?>"><?=h($event['labyrinth'])?></td></tr><?php endforeach; ?><?php if (!$events): ?><tr><td colspan="8" class="empty">Nessun evento registrato. Usa l’endpoint di ingest per generare il primo evento.</td></tr><?php endif; ?></tbody></table></div></main></body></html>

<?php

declare(strict_types=1);
require_once dirname(__DIR__) . '/src/ConfigManager.php';
require_once dirname(__DIR__) . '/src/WordPressConfig.php';
require_once dirname(__DIR__) . '/src/Database.php';

use Dalo\ConfigManager;
use Dalo\WordPressConfig;
use Dalo\Database;

$manager = new ConfigManager(dirname(__DIR__) . '/config');
$alreadyConfigured = $manager->isConfigured();
if ($alreadyConfigured) {
    http_response_code(403);
    exit('The DALO è già configurato. Rimuovi manualmente config/settings.enc e config/.key per eseguire nuovamente il setup.');
}
$message = null;
$error = null;
$wpPath = WordPressConfig::find(dirname(__DIR__));
$wpConfig = null;
if (isset($_GET['wordpress']) && $wpPath !== null) {
    try {
        $wpConfig = WordPressConfig::parse($wpPath);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'db_host' => trim((string)($_POST['db_host'] ?? '127.0.0.1')),
            'db_port' => (int)($_POST['db_port'] ?? 5432),
            'db_name' => trim((string)($_POST['db_name'] ?? 'dalo')),
            'db_user' => trim((string)($_POST['db_user'] ?? '')),
            'db_password' => (string)($_POST['db_password'] ?? ''),
        ];
        if ($settings['db_user'] === '' || $settings['db_name'] === '') throw new RuntimeException('Database e utente sono obbligatori.');
        $connection = new Database([
            'host' => $settings['db_host'],
            'port' => (string)$settings['db_port'],
            'name' => $settings['db_name'],
            'user' => $settings['db_user'],
            'password' => $settings['db_password'],
        ]);
        $schema = file_get_contents(dirname(__DIR__) . '/db/schema.sql');
        if ($schema === false) throw new RuntimeException('Schema PostgreSQL non trovato.');
        $connection->pdo()->exec($schema);
        $manager->save($settings);
        $message = 'Configurazione salvata cifrata. Ora puoi tornare alla dashboard.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$values = $wpConfig ?: ['db_host' => '127.0.0.1', 'db_port' => '5432', 'db_name' => 'dalo', 'db_user' => '', 'db_password' => ''];
$values['db_password'] = '';
?>
<!doctype html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Configura The DALO</title>
    <style>
        body {
            font: 16px system-ui;
            background: #0b1020;
            color: #edf3ff;
            margin: 0
        }

        main {
            max-width: 620px;
            margin: 50px auto;
            padding: 28px;
            background: #121a2f;
            border: 1px solid #263451;
            border-radius: 14px
        }

        h1 {
            margin-top: 0
        }

        label {
            display: block;
            margin: 15px 0 6px;
            color: #aebbd4
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #405277;
            border-radius: 7px;
            background: #0a1020;
            color: white;
            box-sizing: border-box
        }

        button {
            margin-top: 22px;
            padding: 12px 18px;
            border: 0;
            border-radius: 7px;
            background: #eab464;
            color: #17130d;
            font-weight: bold
        }

        .ok {
            color: #7dd3a5
        }

        .error {
            color: #f07878
        }

        .hint {
            color: #aebbd4;
            line-height: 1.5
        }

        .wp {
            display: inline-block;
            margin: 10px 0;
            color: #eab464
        }
    </style>
</head>

<body>
    <main>
        <h1>Configura The DALO</h1>
        <p class="hint">Le credenziali verranno cifrate con Sodium e salvate nella cartella config. Non vengono hashate perché devono essere recuperabili per collegarsi al database.</p><?php if ($message): ?><p class="ok"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><?php if ($wpPath): ?><a class="wp" href="?wordpress=1">Usa il wp-config.php trovato automaticamente</a><?php endif; ?><form method="post"><label>Host database</label><input name="db_host" value="<?= e($values['db_host']) ?>" required><label>Porta</label><input name="db_port" type="number" value="<?= e($values['db_port']) ?>" required><label>Nome database</label><input name="db_name" value="<?= e($values['db_name']) ?>" required><label>Utente database</label><input name="db_user" value="<?= e($values['db_user']) ?>" required><label>Password database</label><input name="db_password" type="password" value="<?= e($values['db_password']) ?>"><button type="submit">Salva configurazione</button></form>
    </main>
</body>

</html>
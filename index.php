<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$dbConfigExists = file_exists(__DIR__ . '/config/database.php');
$dbConnected = false;
$tableCount = 0;

if ($dbConfigExists) {
    require_once __DIR__ . '/config/database.php';
    try {
        $pdo = getDB();
        $dbConnected = true;
        $tableCount = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    } catch (Throwable $e) {
        $dbConnected = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(APP_NAME) ?> — Setup Status</title>
<style>
    body { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background: #F3F5F1; color: #1B2422; margin: 0; padding: 40px 20px; line-height: 1.6; }
    .wrap { max-width: 640px; margin: 0 auto; }
    h1 { font-size: 26px; margin-bottom: 4px; }
    p.sub { color: #55645F; margin-top: 0; }
    .card { background: #fff; border: 1px solid #DCE1DA; border-radius: 10px; padding: 20px 22px; margin-bottom: 14px; }
    .row { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
    .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .ok { background: #2F6F6B; }
    .no { background: #AA4A3D; }
    .portals { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 10px; margin-top: 14px; }
    .portal { border: 1px solid #DCE1DA; border-radius: 8px; padding: 14px; text-align: center; color: #7C8983; font-size: 13px; }
    code { background: #E4EEEC; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
    <h1><?= e(APP_NAME) ?></h1>
    <p class="sub">Phase 0 — repository &amp; database scaffold. Login portals below land in Phase 1.</p>

    <div class="card">
        <div class="row">
            <span class="dot <?= $dbConfigExists ? 'ok' : 'no' ?>"></span>
            config/database.php present
            <?php if (!$dbConfigExists): ?> — copy <code>config/database.example.php</code> to <code>config/database.php</code><?php endif; ?>
        </div>
        <div class="row">
            <span class="dot <?= $dbConnected ? 'ok' : 'no' ?>"></span>
            Database connection
            <?php if ($dbConfigExists && !$dbConnected): ?> — check credentials and that MySQL is running<?php endif; ?>
        </div>
        <?php if ($dbConnected): ?>
        <div class="row">
            <span class="dot ok"></span>
            <?= $tableCount ?> tables found — import <code>database/schema.sql</code> if this reads 0
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <strong>Portals</strong>
        <div class="portals">
            <div class="portal">Client<br>Phase 2</div>
            <div class="portal">Agency<br>Phase 1</div>
            <div class="portal">Admin<br>Phase 1</div>
        </div>
    </div>
</div>
</body>
</html>

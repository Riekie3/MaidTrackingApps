<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(dashboard_url_for(current_role()));
}

$tableCount = 0;
$dbOk = false;
try {
    $tableCount = (int) getDB()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
    $dbOk = true;
} catch (Throwable $e) {
    $dbOk = false;
}

$pageTitle = 'Welcome';
require __DIR__ . '/includes/header.php';
?>
<div class="container narrow" style="padding-top:60px;">
    <h1>MaidTrack</h1>
    <p class="muted">A housemaid agency records &amp; trust platform. Agencies manage verified rosters; Admin approves every registration and every housemaid before she's ever visible to a client.</p>

    <div class="btn-row" style="margin:24px 0 40px;">
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/register.php">I'm looking to hire</a>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/register.php">Register your agency</a>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/freelancer/register.php">Register as a freelancer</a>
    </div>

    <?php if (!$dbOk || $tableCount === 0): ?>
    <div class="card">
        <strong>Setup check</strong>
        <p class="muted" style="margin:8px 0 0;">
            <?= $dbOk ? "$tableCount tables found — import database/schema.sql if this reads 0." : 'Database connection failed — check config/database.php.' ?>
        </p>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
// Expects $pageTitle to be set by the including page.
$role = current_role();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? APP_NAME) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(rtrim(APP_URL, '/')) ?>/assets/css/app.css">
</head>
<body>
<nav class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="<?= e($role ? dashboard_url_for($role) : rtrim(APP_URL, '/') . '/index.php') ?>">
            🧹 <?= e(APP_NAME) ?>
            <?php if ($role): ?><span class="role-tag"><?= e(ucfirst($role)) ?></span><?php endif; ?>
        </a>
        <?php if ($role === 'admin'): ?>
        <div class="nav-links">
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/index.php">Dashboard</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/agencies_pending.php">Agencies</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/housemaids_pending.php">Housemaids</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/master_data.php">Master Data</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/audit_log.php">Audit Log</a>
            <a class="signout" href="<?= e(rtrim(APP_URL, '/')) ?>/logout.php">Sign out</a>
        </div>
        <?php elseif ($role === 'agency'): ?>
        <div class="nav-links">
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/index.php">Dashboard</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaids.php">Roster</a>
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/profile.php">Agency Profile</a>
            <a class="signout" href="<?= e(rtrim(APP_URL, '/')) ?>/logout.php">Sign out</a>
        </div>
        <?php endif; ?>
    </div>
</nav>
<?php $flash = get_flash(); if ($flash): ?>
<div class="container" style="padding-bottom:0;">
    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
</div>
<?php endif; ?>

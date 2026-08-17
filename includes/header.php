<?php
// Expects $pageTitle to be set by the including page.
$role = current_role();
$base = rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle ?? APP_NAME) ?> · <?= e(APP_NAME) ?></title>

<script>
// Applied before the stylesheet paints, so returning visitors never see
// a flash of the wrong theme. The toggle button (app.js) writes the
// same localStorage key.
(function () {
    try {
        var saved = localStorage.getItem('maidtrack-theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
    } catch (e) {}
})();
</script>

<link rel="stylesheet" href="<?= e($base) ?>/assets/css/app.css">

<!-- PWA / installability — see manifest.json and sw.js -->
<link rel="manifest" href="<?= e($base) ?>/manifest.json">
<meta name="theme-color" content="#4A6D9C" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1E2430" media="(prefers-color-scheme: dark)">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<link rel="apple-touch-icon" href="<?= e($base) ?>/assets/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= e($base) ?>/assets/icons/favicon-32.png">
</head>
<body data-sw-url="<?= e($base) ?>/sw.js">
<nav class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="<?= e($role ? dashboard_url_for($role) : $base . '/index.php') ?>">
            🧹 <?= e(APP_NAME) ?>
            <?php if ($role): ?><span class="role-tag"><?= e(ucfirst($role)) ?></span><?php endif; ?>
        </a>
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($role === 'admin'): ?>
            <div class="nav-links">
                <a href="<?= e($base) ?>/admin/index.php">Dashboard</a>
                <a href="<?= e($base) ?>/admin/agencies_pending.php">Agencies</a>
                <a href="<?= e($base) ?>/admin/housemaids_pending.php">Housemaids</a>
                <a href="<?= e($base) ?>/admin/master_data.php">Master Data</a>
                <a href="<?= e($base) ?>/admin/report_overview.php">Reports</a>
                <a href="<?= e($base) ?>/admin/audit_log.php">Audit Log</a>
                <a class="signout" href="<?= e($base) ?>/logout.php">Sign out</a>
            </div>
            <?php elseif ($role === 'agency'): ?>
            <div class="nav-links">
                <a href="<?= e($base) ?>/agency/index.php">Dashboard</a>
                <a href="<?= e($base) ?>/agency/housemaids.php">Roster</a>
                <a href="<?= e($base) ?>/agency/bookings.php">Bookings</a>
                <a href="<?= e($base) ?>/agency/reports.php">Reports</a>
                <a href="<?= e($base) ?>/agency/profile.php">Agency Profile</a>
                <a class="signout" href="<?= e($base) ?>/logout.php">Sign out</a>
            </div>
            <?php elseif ($role === 'client'): ?>
            <div class="nav-links">
                <a href="<?= e($base) ?>/client/browse.php">Browse</a>
                <a href="<?= e($base) ?>/client/bookings.php">My Bookings</a>
                <a class="signout" href="<?= e($base) ?>/logout.php">Sign out</a>
            </div>
            <?php endif; ?>
            <button type="button" class="theme-toggle" data-theme-toggle aria-label="Switch between light and dark mode">
                <span class="icon-light" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg></span>
                <span class="icon-dark" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg></span>
            </button>
        </div>
    </div>
</nav>
<?php $flash = get_flash(); if ($flash): ?>
<div class="container" style="padding-bottom:0;">
    <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
</div>
<?php endif; ?>

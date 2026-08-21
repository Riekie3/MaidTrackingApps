<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$agencyCounts = Agency::countsByStatus();
$hmCounts = Housemaid::globalCounts();
$flCounts = Freelancer::globalCounts();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Everything waiting on your review, in one place.</p>
        </div>
    </div>

    <h2>Agencies</h2>
    <div class="stat-grid">
        <div class="stat-card"><a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/agencies_pending.php"><div class="num"><?= $agencyCounts['pending'] ?></div><div class="label">Pending approval</div></a></div>
        <div class="stat-card"><div class="num"><?= $agencyCounts['approved'] ?></div><div class="label">Approved</div></div>
        <div class="stat-card"><div class="num"><?= $agencyCounts['rejected'] ?></div><div class="label">Rejected</div></div>
    </div>

    <h2>Housemaids</h2>
    <div class="stat-grid">
        <div class="stat-card"><a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/housemaids_pending.php"><div class="num"><?= $hmCounts['pending'] ?></div><div class="label">Pending approval</div></a></div>
        <div class="stat-card"><div class="num"><?= $hmCounts['approved'] ?></div><div class="label">Approved</div></div>
        <div class="stat-card"><div class="num"><?= $hmCounts['rejected'] ?></div><div class="label">Rejected</div></div>
        <div class="stat-card"><div class="num"><?= $hmCounts['total'] ?></div><div class="label">Total submitted</div></div>
    </div>

    <h2>Freelancers</h2>
    <div class="stat-grid">
        <div class="stat-card"><a href="<?= e(rtrim(APP_URL, '/')) ?>/admin/freelancers_pending.php"><div class="num"><?= $flCounts['pending'] ?></div><div class="label">Pending approval</div></a></div>
        <div class="stat-card"><div class="num"><?= $flCounts['approved'] ?></div><div class="label">Approved</div></div>
        <div class="stat-card"><div class="num"><?= $flCounts['rejected'] ?></div><div class="label">Rejected</div></div>
        <div class="stat-card"><div class="num"><?= $flCounts['total'] ?></div><div class="label">Total submitted</div></div>
    </div>

    <h2>Quick actions</h2>
    <div class="quick-links">
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/agencies_pending.php">
            <div class="ql-title">Agency Registrations</div>
            <div class="ql-desc">Review licenses and approve or reject new agencies</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/housemaids_pending.php">
            <div class="ql-title">Housemaid Submissions</div>
            <div class="ql-desc">Review profiles and documents, agency by agency</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/freelancers_pending.php">
            <div class="ql-title">Freelancer Applications</div>
            <div class="ql-desc">Review self-registered freelancers — no agency vets these first</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/master_data.php">
            <div class="ql-title">Master Data</div>
            <div class="ql-desc">Skills, languages, countries, services, and locations</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/audit_log.php">
            <div class="ql-title">Audit Log</div>
            <div class="ql-desc">Every approval, rejection, and edit, timestamped</div>
        </a>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

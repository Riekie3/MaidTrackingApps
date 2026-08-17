<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$counts = Housemaid::countsByAgency($agencyId);
$agency = Agency::findById($agencyId);

$pageTitle = 'Agency Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1><?= e($agency['company_name']) ?></h1>
            <p>Your roster and submission status at a glance.</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaids.php?status=pending">
                <div class="num"><?= $counts['pending'] ?></div>
                <div class="label">Pending Admin review</div>
            </a>
        </div>
        <div class="stat-card">
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaids.php?status=approved">
                <div class="num"><?= $counts['approved'] ?></div>
                <div class="label">Approved</div>
            </a>
        </div>
        <div class="stat-card">
            <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaids.php?status=rejected">
                <div class="num"><?= $counts['rejected'] ?></div>
                <div class="label">Rejected</div>
            </a>
        </div>
    </div>

    <h2>Quick actions</h2>
    <div class="quick-links">
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_add.php">
            <div class="ql-title">+ Add a housemaid</div>
            <div class="ql-desc">Submit a new profile for Admin review</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaids.php">
            <div class="ql-title">View full roster</div>
            <div class="ql-desc">Search and manage every housemaid you've submitted</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/profile.php">
            <div class="ql-title">Edit agency profile</div>
            <div class="ql-desc">Bio, logo, and contact details clients will see</div>
        </a>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

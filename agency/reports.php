<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$pageTitle = 'Reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Reports</h1>
            <p>Printable, exportable, and always current.</p>
        </div>
    </div>
    <div class="quick-links">
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/report_roster.php">
            <div class="ql-title">Roster &amp; Compliance</div>
            <div class="ql-desc">Full roster with passport/work-permit expiry alerts</div>
        </a>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/report_performance.php">
            <div class="ql-title">Agency Performance</div>
            <div class="ql-desc">Placements, ratings, repeat-client rate</div>
        </a>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

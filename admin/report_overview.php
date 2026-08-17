<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$agencyCounts = Agency::countsByStatus();
$hmCounts = Housemaid::globalCounts();
$bookingCounts = Booking::globalCountsByStatus();
$agencySignups = Agency::monthlySignups(6);
$hmSubmissions = Housemaid::monthlySubmissions(6);

$incidentStmt = getDB()->query(
    "SELECT status, COUNT(*) AS c FROM incidents GROUP BY status"
);
$incidentCounts = ['reported' => 0, 'under_review' => 0, 'verified' => 0, 'dismissed' => 0];
foreach ($incidentStmt->fetchAll() as $row) {
    $incidentCounts[$row['status']] = (int) $row['c'];
}

$pageTitle = 'Platform Overview Report';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="btn-row no-print" style="margin-bottom:18px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/index.php">← Back to dashboard</a>
    </div>

    <div class="report-header">
        <div class="brand">🧹 MaidTrack — Platform Overview</div>
        <div class="meta">Generated <?= e(date(DATE_FORMAT_DISPLAY)) ?></div>
    </div>

    <h2>Agencies</h2>
    <div class="stat-row">
        <div class="stat-card"><div class="num"><?= $agencyCounts['pending'] ?></div><div class="label">Pending</div></div>
        <div class="stat-card"><div class="num"><?= $agencyCounts['approved'] ?></div><div class="label">Approved</div></div>
        <div class="stat-card"><div class="num"><?= $agencyCounts['rejected'] ?></div><div class="label">Rejected</div></div>
    </div>

    <h2>Housemaids</h2>
    <div class="stat-row">
        <div class="stat-card"><div class="num"><?= $hmCounts['pending'] ?></div><div class="label">Pending</div></div>
        <div class="stat-card"><div class="num"><?= $hmCounts['approved'] ?></div><div class="label">Approved</div></div>
        <div class="stat-card"><div class="num"><?= $hmCounts['rejected'] ?></div><div class="label">Rejected</div></div>
        <div class="stat-card"><div class="num"><?= $hmCounts['total'] ?></div><div class="label">Total</div></div>
    </div>

    <h2>Bookings</h2>
    <div class="stat-row">
        <?php foreach ($bookingCounts as $status => $count): ?>
        <div class="stat-card"><div class="num"><?= $count ?></div><div class="label"><?= e(ucfirst($status)) ?></div></div>
        <?php endforeach; ?>
    </div>

    <h2>Incidents <span class="muted" style="font-weight:400;font-size:13px;">(Reported → Under Review → Verified workflow lands in Phase 4)</span></h2>
    <div class="stat-row">
        <div class="stat-card"><div class="num"><?= $incidentCounts['reported'] ?></div><div class="label">Reported</div></div>
        <div class="stat-card"><div class="num"><?= $incidentCounts['under_review'] ?></div><div class="label">Under review</div></div>
        <div class="stat-card"><div class="num"><?= $incidentCounts['verified'] ?></div><div class="label">Verified</div></div>
        <div class="stat-card"><div class="num"><?= $incidentCounts['dismissed'] ?></div><div class="label">Dismissed</div></div>
    </div>

    <div class="card">
        <h2>Growth — last 6 months</h2>
        <canvas id="growthChart" height="90"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($agencySignups)) ?>,
        datasets: [
            { label: 'Agencies', data: <?= json_encode(array_values($agencySignups)) ?>, borderColor: '#4A6D9C', backgroundColor: '#4A6D9C', tension: 0.3 },
            { label: 'Housemaids', data: <?= json_encode(array_values($hmSubmissions)) ?>, borderColor: '#A85A3F', backgroundColor: '#A85A3F', tension: 0.3 }
        ]
    },
    options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

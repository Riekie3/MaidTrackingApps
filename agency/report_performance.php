<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$agency = Agency::findById($agencyId);
$stats = Agency::rosterStats($agencyId);
$bookingCounts = Booking::countsByAgency($agencyId);
$repeatRate = Booking::repeatClientRate($agencyId);
$placements = Booking::monthlyPlacements($agencyId, 6);

$incidentStmt = getDB()->prepare(
    "SELECT COUNT(*) FROM incidents i JOIN housemaids h ON h.id = i.provider_id AND i.provider_type = 'housemaid'
     WHERE h.agency_id = ? AND i.status = 'verified'"
);
$incidentStmt->execute([$agencyId]);
$verifiedIncidents = (int) $incidentStmt->fetchColumn();

$pageTitle = 'Agency Performance Report';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="btn-row no-print" style="margin-bottom:18px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/reports.php">← Back to reports</a>
    </div>

    <div class="report-header">
        <div class="brand">🧹 MaidTrack — Agency Performance</div>
        <div class="meta"><?= e($agency['company_name']) ?><br>Generated <?= e(date(DATE_FORMAT_DISPLAY)) ?></div>
    </div>

    <div class="stat-row">
        <div class="stat-card"><div class="num"><?= $stats['roster_count'] ?></div><div class="label">Approved housemaids</div></div>
        <div class="stat-card"><div class="num"><?= $stats['agency_rating'] ?? '—' ?></div><div class="label">Average rating</div></div>
        <div class="stat-card"><div class="num"><?= $repeatRate !== null ? $repeatRate . '%' : '—' ?></div><div class="label">Repeat-client rate</div></div>
        <div class="stat-card"><div class="num"><?= $verifiedIncidents ?></div><div class="label">Verified incidents</div></div>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2>Bookings by status</h2>
        <div class="stat-row" style="margin-bottom:0;">
            <?php foreach ($bookingCounts as $status => $count): ?>
            <div class="stat-card"><div class="num"><?= $count ?></div><div class="label"><?= e(ucfirst($status)) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Placements — last 6 months</h2>
        <canvas id="placementsChart" height="90"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
new Chart(document.getElementById('placementsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($placements)) ?>,
        datasets: [{
            label: 'Placements',
            data: <?= json_encode(array_values($placements)) ?>,
            backgroundColor: '#4A6D9C',
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

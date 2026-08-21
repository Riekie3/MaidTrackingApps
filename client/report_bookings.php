<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$bookings = Booking::listByClient($clientId);

if (($_GET['format'] ?? '') === 'csv') {
    csv_download('maidtrack-bookings.csv', [
        'provider_name' => 'Housemaid / Freelancer',
        'agency_name' => 'Agency',
        'status' => 'Status',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'created_at' => 'Requested',
    ], $bookings);
}

$pageTitle = 'Booking History Report';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="btn-row no-print" style="margin-bottom:18px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-outline" href="?format=csv">Download CSV</a>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/bookings.php">← Back to bookings</a>
    </div>

    <div class="report-header">
        <div class="brand">🧹 MaidTrack — Booking History</div>
        <div class="meta">Generated <?= e(date(DATE_FORMAT_DISPLAY)) ?><br>for <?= e(current_name()) ?></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Housemaid / Freelancer</th><th>Agency</th><th>Dates</th><th>Status</th><th>Requested</th></tr></thead>
            <tbody>
                <?php if (!$bookings): ?><tr><td colspan="5" class="muted">No bookings.</td></tr><?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= e($b['provider_name']) ?></td>
                    <td><?= e($b['agency_name'] ?? 'Freelancer') ?></td>
                    <td><?= fmt_date($b['start_date']) ?><?= $b['end_date'] ? ' – ' . fmt_date($b['end_date']) : '' ?></td>
                    <td><?= e(ucfirst($b['status'])) ?></td>
                    <td><?= fmt_date($b['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

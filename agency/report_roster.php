<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$agency = Agency::findById($agencyId);
$roster = Housemaid::listByAgency($agencyId);

function expiry_class(?string $date): string
{
    $d = days_until($date);
    if ($d === null) return '';
    if ($d < 0) return 'expired';
    if ($d <= 60) return 'soon';
    return 'ok';
}
function expiry_label(?string $date): string
{
    $d = days_until($date);
    if ($d === null) return '—';
    if ($d < 0) return 'Expired ' . abs($d) . 'd ago';
    return "$d days left";
}

if (($_GET['format'] ?? '') === 'csv') {
    $rows = array_map(fn($h) => [
        'full_name' => $h['full_name'],
        'nationality_name' => $h['nationality_name'] ?? '',
        'approval_status' => $h['approval_status'],
        'availability_status' => $h['availability_status'],
        'passport_expiry' => $h['passport_expiry'],
        'work_permit_expiry' => $h['work_permit_expiry'],
    ], $roster);
    csv_download('maidtrack-roster.csv', [
        'full_name' => 'Name', 'nationality_name' => 'Nationality', 'approval_status' => 'Approval',
        'availability_status' => 'Availability', 'passport_expiry' => 'Passport expiry', 'work_permit_expiry' => 'Work permit expiry',
    ], $rows);
}

$pageTitle = 'Roster & Compliance Report';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="btn-row no-print" style="margin-bottom:18px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-outline" href="?format=csv">Download CSV</a>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/reports.php">← Back to reports</a>
    </div>

    <div class="report-header">
        <div class="brand">🧹 MaidTrack — Roster &amp; Compliance</div>
        <div class="meta"><?= e($agency['company_name']) ?><br>Generated <?= e(date(DATE_FORMAT_DISPLAY)) ?></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Status</th><th>Availability</th><th>Passport expiry</th><th>Work permit expiry</th></tr></thead>
            <tbody>
                <?php if (!$roster): ?><tr><td colspan="5" class="muted">No housemaids yet.</td></tr><?php endif; ?>
                <?php foreach ($roster as $h): ?>
                <tr>
                    <td><strong><?= e($h['full_name']) ?></strong></td>
                    <td><span class="pill <?= e($h['approval_status']) ?>"><?= e(ucfirst($h['approval_status'])) ?></span></td>
                    <td><?= $h['approval_status'] === 'approved' ? '<span class="pill ' . e($h['availability_status']) . '">' . e(ucfirst(str_replace('_', ' ', $h['availability_status']))) . '</span>' : '—' ?></td>
                    <td class="report-alert-row <?= expiry_class($h['passport_expiry']) ?>"><span class="dot"></span> <?= fmt_date($h['passport_expiry']) ?> <span class="muted">(<?= expiry_label($h['passport_expiry']) ?>)</span></td>
                    <td class="report-alert-row <?= expiry_class($h['work_permit_expiry']) ?>"><span class="dot"></span> <?= fmt_date($h['work_permit_expiry']) ?> <span class="muted">(<?= expiry_label($h['work_permit_expiry']) ?>)</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted no-print" style="margin-top:14px;font-size:12.5px;">
        <span class="report-alert-row expired" style="display:inline-flex;"><span class="dot"></span></span> Expired ·
        <span class="report-alert-row soon" style="display:inline-flex;"><span class="dot"></span></span> Expiring within 60 days ·
        <span class="report-alert-row ok" style="display:inline-flex;"><span class="dot"></span></span> OK
    </p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

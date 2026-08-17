<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$summaries = Housemaid::pendingAgencySummaries();
$pageTitle = 'Housemaid Submissions';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Housemaid Submissions</h1>
            <p>Agencies with housemaids awaiting your review. Open one to see her full profile before deciding.</p>
        </div>
    </div>

    <?php if (!$summaries): ?>
        <div class="card"><p class="muted" style="margin:0;">Nothing pending right now.</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Agency</th><th>Pending housemaids</th><th>Oldest submission</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($summaries as $s): ?>
            <tr class="row-link" onclick="location.href='<?= e(rtrim(APP_URL, '/')) ?>/admin/agency_housemaids.php?agency_id=<?= (int) $s['agency_id'] ?>'">
                <td><strong><?= e($s['company_name']) ?></strong></td>
                <td><span class="pill pending"><?= (int) $s['pending_count'] ?> pending</span></td>
                <td class="muted"><?= fmt_date($s['oldest_submitted_at']) ?></td>
                <td><a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/agency_housemaids.php?agency_id=<?= (int) $s['agency_id'] ?>">Review →</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

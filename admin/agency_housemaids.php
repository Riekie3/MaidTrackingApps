<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminId = current_id();
$agencyId = (int) ($_GET['agency_id'] ?? 0);
$agency = Agency::findById($agencyId);
if (!$agency) {
    flash('error', 'Agency not found.');
    redirect(rtrim(APP_URL, '/') . '/admin/housemaids_pending.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ids = array_map('intval', $_POST['housemaid_ids'] ?? []);
    $action = $_POST['bulk_action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (!$ids) {
        flash('error', 'Select at least one housemaid first.');
    } elseif ($action === 'approve') {
        Housemaid::bulkApprove($ids, $adminId);
        flash('success', count($ids) . ' housemaid(s) approved.');
    } elseif ($action === 'reject') {
        if ($reason === '') {
            flash('error', 'A rejection reason is required for a bulk reject.');
        } else {
            Housemaid::bulkReject($ids, $adminId, $reason);
            flash('success', count($ids) . ' housemaid(s) rejected.');
        }
    }
    redirect(rtrim(APP_URL, '/') . '/admin/agency_housemaids.php?agency_id=' . $agencyId);
}

$pending = Housemaid::listPendingByAgency($agencyId);
$pageTitle = $agency['company_name'] . ' — Pending Housemaids';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1><?= e($agency['company_name']) ?></h1>
            <p><?= count($pending) ?> housemaid(s) pending review.</p>
        </div>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/housemaids_pending.php">← Back to agencies</a>
    </div>

    <?php if (!$pending): ?>
        <div class="card"><p class="muted" style="margin:0;">Nothing pending for this agency right now.</p></div>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:36px;"><input type="checkbox" data-select-all="housemaids"></th>
                    <th>Name</th><th>Nationality</th><th>Submitted</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($pending as $h): ?>
                <tr>
                    <td><input type="checkbox" name="housemaid_ids[]" value="<?= (int) $h['id'] ?>" data-group="housemaids"></td>
                    <td><strong><?= e($h['full_name']) ?></strong></td>
                    <td><?= e($h['nationality_name'] ?? '—') ?></td>
                    <td class="muted"><?= fmt_date($h['submitted_at']) ?></td>
                    <td><a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/housemaid_review.php?id=<?= (int) $h['id'] ?>">Review →</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top:18px;">
            <strong>Bulk action</strong>
            <div class="field-row" style="margin-top:10px;align-items:end;">
                <div class="field" style="margin-bottom:0;">
                    <label for="bulk_action">Apply to selected</label>
                    <select id="bulk_action" name="bulk_action" data-bulk-action="housemaid">
                        <option value="">Choose…</option>
                        <option value="approve">Approve</option>
                        <option value="reject">Reject</option>
                    </select>
                </div>
                <div class="field" data-reason-for="housemaid" style="margin-bottom:0;display:none;">
                    <label for="reason">Rejection reason <span class="muted" style="font-weight:400;">(required for reject)</span></label>
                    <input type="text" id="reason" name="reason">
                </div>
                <button type="submit" class="btn btn-primary" data-confirm="Apply this action to the selected housemaids?">Apply</button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

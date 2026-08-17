<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminId = current_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ids = array_map('intval', $_POST['agency_ids'] ?? []);
    $action = $_POST['bulk_action'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (!$ids) {
        flash('error', 'Select at least one agency first.');
    } elseif ($action === 'approve') {
        foreach ($ids as $id) Agency::approve($id, $adminId);
        flash('success', count($ids) . ' agency/agencies approved.');
    } elseif ($action === 'reject') {
        if ($reason === '') {
            flash('error', 'A rejection reason is required for a bulk reject.');
        } else {
            foreach ($ids as $id) Agency::reject($id, $adminId, $reason);
            flash('success', count($ids) . ' agency/agencies rejected.');
        }
    }
    redirect(rtrim(APP_URL, '/') . '/admin/agencies_pending.php');
}

$pending = Agency::listByStatus('pending');
$pageTitle = 'Agency Registrations';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Agency Registrations</h1>
            <p><?= count($pending) ?> pending review.</p>
        </div>
    </div>

    <?php if (!$pending): ?>
        <div class="card"><p class="muted" style="margin:0;">Nothing pending. New registrations will show up here.</p></div>
    <?php else: ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:36px;"><input type="checkbox" data-select-all="agencies"></th>
                    <th>Company</th><th>Registration No.</th><th>Contact</th><th>Submitted</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($pending as $a): ?>
                <tr>
                    <td><input type="checkbox" name="agency_ids[]" value="<?= (int) $a['id'] ?>" data-group="agencies"></td>
                    <td><strong><?= e($a['company_name']) ?></strong></td>
                    <td class="mono"><?= e($a['registration_number']) ?></td>
                    <td><?= e($a['contact_person']) ?><br><span class="muted"><?= e($a['email']) ?></span></td>
                    <td class="muted"><?= fmt_date($a['created_at']) ?></td>
                    <td><a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/agency_review.php?id=<?= (int) $a['id'] ?>">Review →</a></td>
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
                    <select id="bulk_action" name="bulk_action" data-bulk-action="agency">
                        <option value="">Choose…</option>
                        <option value="approve">Approve</option>
                        <option value="reject">Reject</option>
                    </select>
                </div>
                <div class="field" data-reason-for="agency" style="margin-bottom:0;display:none;">
                    <label for="reason">Rejection reason <span class="muted" style="font-weight:400;">(required for reject)</span></label>
                    <input type="text" id="reason" name="reason">
                </div>
                <button type="submit" class="btn btn-primary" data-confirm="Apply this action to the selected agencies?">Apply</button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

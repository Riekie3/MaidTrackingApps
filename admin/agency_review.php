<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminId = current_id();
$id = (int) ($_GET['id'] ?? 0);
$agency = Agency::findById($id);
if (!$agency) {
    flash('error', 'Agency not found.');
    redirect(rtrim(APP_URL, '/') . '/admin/agencies_pending.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        Agency::approve($id, $adminId);
        flash('success', $agency['company_name'] . ' approved.');
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash('error', 'A rejection reason is required.');
            redirect(rtrim(APP_URL, '/') . '/admin/agency_review.php?id=' . $id);
        }
        Agency::reject($id, $adminId, $reason);
        flash('success', $agency['company_name'] . ' rejected.');
    }
    redirect(rtrim(APP_URL, '/') . '/admin/agencies_pending.php');
}

$pageTitle = $agency['company_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1><?= e($agency['company_name']) ?></h1>
            <p><span class="pill <?= e($agency['approval_status']) ?>"><?= e(ucfirst($agency['approval_status'])) ?></span></p>
        </div>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/agencies_pending.php">← Back to queue</a>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2>Details</h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Registration No.</div><div class="dv mono"><?= e($agency['registration_number']) ?></div></div>
            <div class="detail-item"><div class="dl">Contact person</div><div class="dv"><?= e($agency['contact_person']) ?></div></div>
            <div class="detail-item"><div class="dl">Email</div><div class="dv"><?= e($agency['email']) ?></div></div>
            <div class="detail-item"><div class="dl">Phone</div><div class="dv"><?= e($agency['phone']) ?></div></div>
            <div class="detail-item"><div class="dl">Address</div><div class="dv"><?= nl2br(e($agency['address'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Submitted</div><div class="dv"><?= fmt_date($agency['created_at']) ?></div></div>
        </div>
        <?php if ($agency['bio']): ?>
            <div class="detail-item" style="margin-top:12px;"><div class="dl">Public description</div><div class="dv"><?= nl2br(e($agency['bio'])) ?></div></div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2>License document</h2>
        <?php if ($agency['license_document_path']): ?>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=agency_license&id=<?= (int) $agency['id'] ?>" target="_blank" rel="noopener">View document</a>
        <?php else: ?>
            <p class="muted">No document on file.</p>
        <?php endif; ?>
    </div>

    <?php if ($agency['approval_status'] === 'rejected' && $agency['rejection_reason']): ?>
        <div class="alert error"><strong>Previously rejected:</strong> <?= e($agency['rejection_reason']) ?></div>
    <?php endif; ?>

    <?php if ($agency['approval_status'] === 'pending'): ?>
    <div class="card">
        <h2>Decision</h2>
        <div class="btn-row" style="margin-bottom:16px;">
            <form method="post" data-confirm="Approve <?= e($agency['company_name']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary">Approve</button>
            </form>
        </div>
        <form method="post" data-confirm="Reject <?= e($agency['company_name']) ?>?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <div class="field">
                <label for="reason">Rejection reason</label>
                <input type="text" id="reason" name="reason" required placeholder="e.g. License document is expired">
            </div>
            <button type="submit" class="btn btn-danger">Reject</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

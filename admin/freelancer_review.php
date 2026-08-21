<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminId = current_id();
$id = (int) ($_GET['id'] ?? 0);
$f = Freelancer::findById($id);
if (!$f) {
    flash('error', 'Freelancer not found.');
    redirect(rtrim(APP_URL, '/') . '/admin/freelancers_pending.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        Freelancer::approve($id, $adminId);
        flash('success', $f['full_name'] . ' approved.');
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash('error', 'A rejection reason is required.');
            redirect(rtrim(APP_URL, '/') . '/admin/freelancer_review.php?id=' . $id);
        }
        Freelancer::reject($id, $adminId, $reason);
        flash('success', $f['full_name'] . ' rejected.');
    }
    redirect(rtrim(APP_URL, '/') . '/admin/freelancers_pending.php');
}

$documents = FreelancerDocument::listForFreelancer($id);
$services = Freelancer::getServices($id);
$locations = Freelancer::getLocationNames($id);

$pageTitle = $f['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1><?= e($f['full_name']) ?></h1>
            <p>
                <span class="pill <?= e($f['approval_status']) ?>"><?= e(ucfirst($f['approval_status'])) ?></span>
                · Email/phone <?= ($f['email_verified_at'] && $f['phone_verified_at']) ? 'verified' : 'not yet verified' ?>
            </p>
        </div>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/freelancers_pending.php">← Back to applications</a>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2>Profile</h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Email</div><div class="dv"><?= e($f['email']) ?></div></div>
            <div class="detail-item"><div class="dl">Phone</div><div class="dv"><?= e($f['phone']) ?></div></div>
            <div class="detail-item"><div class="dl">Date of birth</div><div class="dv"><?= fmt_date($f['date_of_birth']) ?></div></div>
            <div class="detail-item"><div class="dl">Gender</div><div class="dv"><?= e(ucfirst($f['gender'])) ?></div></div>
            <div class="detail-item"><div class="dl">Nationality</div><div class="dv"><?= e($f['nationality_name'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Years experience</div><div class="dv"><?= e((string) ($f['years_experience'] ?? '—')) ?></div></div>
        </div>
        <div class="detail-grid" style="margin-top:8px;">
            <div class="detail-item"><div class="dl">Services offered</div><div class="dv"><?= $services ? e(implode(', ', array_map(fn($s) => $s['service_name'] . ' (RM' . number_format((float) $s['price'], 2) . '/' . $s['price_unit'] . ')', $services))) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Service areas</div><div class="dv"><?= $locations ? e(implode(', ', $locations)) : '—' ?></div></div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2>Identity &amp; documents <span class="muted" style="font-weight:400;font-size:13px;">— full detail visible to Admin</span></h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Passport number</div><div class="dv mono"><?= e($f['passport_number'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Passport expiry</div><div class="dv"><?= fmt_date($f['passport_expiry']) ?></div></div>
            <div class="detail-item"><div class="dl">Work permit number</div><div class="dv mono"><?= e($f['work_permit_number'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Work permit expiry</div><div class="dv"><?= fmt_date($f['work_permit_expiry']) ?></div></div>
            <div class="detail-item"><div class="dl">National ID</div><div class="dv mono"><?= e($f['national_id_number'] ?? '—') ?></div></div>
        </div>
        <div class="doc-list" style="margin-top:14px;">
            <?php foreach ($documents as $doc): ?>
            <div class="doc-item">
                <div>
                    <div class="doc-type"><?= e(ucwords(str_replace('_', ' ', $doc['doc_type']))) ?></div>
                    <div class="doc-meta">Expiry: <?= fmt_date($doc['expiry_date']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=freelancer_doc&id=<?= (int) $doc['id'] ?>" target="_blank" rel="noopener">View file</a>
            </div>
            <?php endforeach; ?>
            <?php if (!$documents): ?><p class="muted">No documents uploaded.</p><?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2>Banking <span class="muted" style="font-weight:400;font-size:13px;">— restricted, Admin/self only</span></h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Bank</div><div class="dv"><?= e($f['bank_name'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Account holder</div><div class="dv"><?= e($f['bank_account_holder'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Account number</div><div class="dv mono"><?= e($f['bank_account_number'] ?? '—') ?></div></div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2>Address</h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Home address</div><div class="dv"><?= nl2br(e($f['home_address'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Current staying address</div><div class="dv"><?= nl2br(e($f['current_staying_address'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Emergency contact</div><div class="dv"><?= e($f['emergency_contact_name'] ?? '—') ?> <?= $f['emergency_contact_phone'] ? '(' . e($f['emergency_contact_phone']) . ')' : '' ?></div></div>
            <div class="detail-item"><div class="dl">PDPA consent</div><div class="dv"><?= $f['consent_given_at'] ? 'Given ' . fmt_date($f['consent_given_at']) : '—' ?></div></div>
        </div>
    </div>

    <?php if ($f['approval_status'] === 'pending'): ?>
    <div class="card">
        <h2>Decision</h2>
        <div class="btn-row" style="margin-bottom:16px;">
            <form method="post" data-confirm="Approve <?= e($f['full_name']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary">Approve</button>
            </form>
        </div>
        <form method="post" data-confirm="Reject <?= e($f['full_name']) ?>?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <div class="field">
                <label for="reason">Rejection reason</label>
                <input type="text" id="reason" name="reason" required placeholder="e.g. Passport scan is unreadable">
            </div>
            <button type="submit" class="btn btn-danger">Reject</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

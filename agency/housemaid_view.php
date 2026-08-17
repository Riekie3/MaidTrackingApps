<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$id = (int) ($_GET['id'] ?? 0);
$hm = Housemaid::findByIdForAgency($id, $agencyId);
if (!$hm) {
    flash('error', 'Housemaid not found.');
    redirect(rtrim(APP_URL, '/') . '/agency/housemaids.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['availability_status'])) {
    verify_csrf();
    $newStatus = $_POST['availability_status'];
    if (in_array($newStatus, ['available', 'placed', 'on_leave'], true)) {
        Housemaid::updateAvailability($id, $agencyId, $newStatus);
        AuditLog::record('agency', $agencyId, 'housemaid.availability_update', 'housemaid', $id, ['status' => $newStatus]);
        flash('success', 'Availability updated.');
        redirect(rtrim(APP_URL, '/') . '/agency/housemaid_view.php?id=' . $id);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    verify_csrf();
    $response = trim($_POST['agency_response'] ?? '');
    if ($response !== '') {
        Review::addAgencyResponse((int) $_POST['review_id'], $agencyId, $response);
        flash('success', 'Response posted.');
    }
    redirect(rtrim(APP_URL, '/') . '/agency/housemaid_view.php?id=' . $id);
}

$skills = Housemaid::getSkillNames($id);
$languages = Housemaid::getLanguageNames($id);
$documents = HousemaidDocument::listForHousemaid($id);
$reviews = $hm['approval_status'] === 'approved' ? Review::listForHousemaid($id) : [];

$pageTitle = $hm['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1><?= e($hm['full_name']) ?></h1>
            <p>
                <span class="pill <?= e($hm['approval_status']) ?>"><?= e(ucfirst($hm['approval_status'])) ?></span>
                <?php if ($hm['approval_status'] === 'approved'): ?>
                    <span class="pill <?= e($hm['availability_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $hm['availability_status']))) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="btn-row">
            <?php if ($hm['approval_status'] === 'approved'): ?>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/incident_report.php?housemaid_id=<?= (int) $id ?>">Report an incident</a>
            <?php endif; ?>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaids.php">← Back to roster</a>
        </div>
    </div>

    <?php if ($hm['approval_status'] === 'rejected' && $hm['rejection_reason']): ?>
    <div class="alert error"><strong>Rejected:</strong> <?= e($hm['rejection_reason']) ?></div>
    <?php elseif ($hm['approval_status'] === 'pending'): ?>
    <div class="alert error" style="border-color:var(--pending);background:var(--pending-soft);">This profile is awaiting Admin review and isn't visible to clients yet.</div>
    <?php endif; ?>

    <?php if ($hm['approval_status'] === 'approved'): ?>
    <div class="card" style="margin-bottom:24px;">
        <h2>Availability</h2>
        <form method="post" class="btn-row">
            <?= csrf_field() ?>
            <select name="availability_status" onchange="this.form.submit()">
                <option value="available" <?= $hm['availability_status'] === 'available' ? 'selected' : '' ?>>Available</option>
                <option value="placed" <?= $hm['availability_status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                <option value="on_leave" <?= $hm['availability_status'] === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                <option value="blacklisted" disabled <?= $hm['availability_status'] === 'blacklisted' ? 'selected' : '' ?>>Blacklisted (Admin only)</option>
            </select>
        </form>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:24px;">
        <h2>Profile</h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Date of birth</div><div class="dv"><?= fmt_date($hm['date_of_birth']) ?></div></div>
            <div class="detail-item"><div class="dl">Gender</div><div class="dv"><?= e(ucfirst($hm['gender'])) ?></div></div>
            <div class="detail-item"><div class="dl">Nationality</div><div class="dv"><?= e($hm['nationality_name'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Marital status</div><div class="dv"><?= e($hm['marital_status'] ? ucfirst($hm['marital_status']) : '—') ?></div></div>
            <div class="detail-item"><div class="dl">Years experience</div><div class="dv"><?= e((string) ($hm['years_experience'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Passport no.</div><div class="dv mono"><?= e($hm['passport_number'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Passport expiry</div><div class="dv"><?= fmt_date($hm['passport_expiry']) ?></div></div>
            <div class="detail-item"><div class="dl">Work permit expiry</div><div class="dv"><?= fmt_date($hm['work_permit_expiry']) ?></div></div>
        </div>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Skills</div><div class="dv"><?= $skills ? e(implode(', ', $skills)) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Languages</div><div class="dv"><?= $languages ? e(implode(', ', $languages)) : '—' ?></div></div>
        </div>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Home address</div><div class="dv"><?= nl2br(e($hm['home_address'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Current staying address</div><div class="dv"><?= nl2br(e($hm['current_staying_address'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Emergency contact</div><div class="dv"><?= e($hm['emergency_contact_name'] ?? '—') ?> <?= $hm['emergency_contact_phone'] ? '(' . e($hm['emergency_contact_phone']) . ')' : '' ?></div></div>
        </div>
    </div>

    <div class="card">
        <h2>Documents</h2>
        <div class="doc-list">
            <?php foreach ($documents as $doc): ?>
            <div class="doc-item">
                <div>
                    <div class="doc-type"><?= e(ucwords(str_replace('_', ' ', $doc['doc_type']))) ?></div>
                    <div class="doc-meta">Expiry: <?= fmt_date($doc['expiry_date']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=housemaid_doc&id=<?= (int) $doc['id'] ?>" target="_blank" rel="noopener">View file</a>
            </div>
            <?php endforeach; ?>
            <?php if (!$documents): ?><p class="muted">No documents uploaded.</p><?php endif; ?>
        </div>
    </div>

    <?php if ($hm['approval_status'] === 'approved'): ?>
    <div class="card" style="margin-top:24px;">
        <h2>Reviews <span class="muted" style="font-weight:400;font-size:13px;"><?= $hm['avg_rating'] ? '— ★ ' . e(number_format((float) $hm['avg_rating'], 1)) . " average across {$hm['ratings_count']}" : '— none yet' ?></span></h2>
        <?php if (!$reviews): ?><p class="muted">No reviews yet.</p><?php endif; ?>
        <?php foreach ($reviews as $r): ?>
            <?php $avg = ($r['rating_reliability'] + $r['rating_skill'] + $r['rating_hygiene'] + $r['rating_communication']) / 4; ?>
            <div style="border-top:1px solid var(--line);padding:14px 0;">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <strong><?= e($r['client_name']) ?></strong>
                    <span class="mono">★ <?= number_format($avg, 1) ?></span>
                </div>
                <?php if ($r['comment']): ?><p style="margin:6px 0;"><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
                <?php if ($r['agency_response']): ?>
                    <div style="margin-top:8px;padding:10px 12px;background:var(--primary-soft);border-radius:8px;font-size:13.5px;">
                        <strong>Your response:</strong> <?= nl2br(e($r['agency_response'])) ?>
                    </div>
                <?php else: ?>
                    <form method="post" style="margin-top:8px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                        <div class="field" style="margin-bottom:8px;">
                            <textarea name="agency_response" placeholder="Reply to this review…" style="min-height:60px;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline">Post response</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

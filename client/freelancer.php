<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$id = (int) ($_GET['id'] ?? 0);
$f = Freelancer::publicFindById($id);
if (!$f) {
    flash('error', 'That profile is not available.');
    redirect(rtrim(APP_URL, '/') . '/client/browse.php');
}

$services = Freelancer::getServices($id);
$locations = Freelancer::getLocationNames($id);
$reviews = Review::listForProvider('freelancer', $id);
$qualifies = Booking::hasQualifyingBooking($clientId, 'freelancer', $id);
$verifiedIncidents = Incident::listVerifiedForProvider('freelancer', $id);

$age = calculate_age($f['date_of_birth']);
$pageTitle = $f['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div style="display:flex;gap:16px;align-items:center;">
            <?php if ($f['photo_path']): ?>
                <img src="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=freelancer_photo&id=<?= (int) $f['id'] ?>" alt="<?= e($f['full_name']) ?>" style="width:72px;height:72px;border-radius:50%;object-fit:cover;flex-shrink:0;">
            <?php endif; ?>
            <div>
                <h1 style="margin-bottom:2px;"><?= e($f['full_name']) ?> <span class="pill pending" style="font-size:11px;vertical-align:middle;">Freelancer</span></h1>
                <p style="margin:0;">
                    <?= e($f['nationality_name'] ?? '—') ?><?php if ($age !== null): ?> · <?= $age ?> yrs old<?php endif; ?>
                    <?php if ($f['avg_rating']): ?> · ★ <?= e(number_format((float) $f['avg_rating'], 1)) ?><?php endif; ?>
                </p>
            </div>
        </div>
        <div class="btn-row">
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php">← Back to browse</a>
        </div>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Years experience</div><div class="dv"><?= e((string) ($f['years_experience'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Rating</div><div class="dv"><?= $f['avg_rating'] ? '★ ' . e(number_format((float) $f['avg_rating'], 1)) . " ({$f['ratings_count']} review" . ($f['ratings_count'] == 1 ? '' : 's') . ')' : 'No reviews yet' ?></div></div>
            <div class="detail-item"><div class="dl">Service areas</div><div class="dv"><?= $locations ? e(implode(', ', $locations)) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Passport no.</div><div class="dv mono"><?= $qualifies ? e($f['passport_number'] ?? '—') : e(mask_document_number($f['passport_number'])) ?></div></div>
        </div>
        <?php if ($qualifies): ?>
            <p class="hint" style="margin-top:10px;">Full document number shown because you have a confirmed booking with her.</p>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2>Services &amp; pricing</h2>
        <?php if (!$services): ?>
            <p class="muted">No services listed yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Service</th><th>Price</th></tr></thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                <tr><td><?= e($s['service_name']) ?></td><td>RM<?= e(number_format((float) $s['price'], 2)) ?> / <?= $s['price_unit'] === 'per_job' ? 'job' : ($s['price_unit'] === 'hourly' ? 'hour' : 'day') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($verifiedIncidents): ?>
    <div class="card" style="margin-bottom:22px;border-color:var(--danger);background:var(--danger-soft);">
        <h2 style="color:var(--danger);">Verified incidents</h2>
        <?php foreach ($verifiedIncidents as $inc): ?>
        <div style="border-top:1px solid var(--line-strong);padding:10px 0;">
            <strong><?= e(ucwords(str_replace('_', ' ', $inc['incident_type']))) ?></strong>
            <span class="muted"> — <?= fmt_date($inc['created_at']) ?></span>
            <p style="margin:4px 0 0;"><?= nl2br(e($inc['description'])) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:22px;">
        <h2>Book her</h2>
        <?php if (!$services): ?>
            <p class="muted">She hasn't listed any services to book yet.</p>
        <?php elseif ($f['availability_status'] !== 'available'): ?>
            <p class="muted">Not currently accepting new bookings.</p>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/client/freelancer_booking_request.php?freelancer_id=<?= (int) $f['id'] ?>">Request a booking</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Reviews</h2>
        <?php if (!$reviews): ?>
            <p class="muted">No reviews yet.</p>
        <?php endif; ?>
        <?php foreach ($reviews as $r): ?>
            <?php $avg = ($r['rating_reliability'] + $r['rating_skill'] + $r['rating_hygiene'] + $r['rating_communication']) / 4; ?>
            <div style="border-top:1px solid var(--line);padding:14px 0;">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                    <strong><?= e($r['client_name']) ?></strong>
                    <span class="mono">★ <?= number_format($avg, 1) ?></span>
                </div>
                <p class="muted" style="font-size:12.5px;margin:2px 0 8px;">
                    Reliability <?= (int) $r['rating_reliability'] ?>/5 · Skill <?= (int) $r['rating_skill'] ?>/5 ·
                    Hygiene <?= (int) $r['rating_hygiene'] ?>/5 · Communication <?= (int) $r['rating_communication'] ?>/5
                </p>
                <?php if ($r['comment']): ?><p style="margin:0;"><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($qualifies): ?>
    <p class="no-print" style="margin-top:18px;">
        <a class="btn btn-sm btn-ghost" href="<?= e(rtrim(APP_URL, '/')) ?>/client/incident_report.php?provider_type=freelancer&provider_id=<?= (int) $id ?>">Report an incident</a>
    </p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

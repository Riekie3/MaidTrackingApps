<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$id = (int) ($_GET['id'] ?? 0);
$hm = Housemaid::publicFindById($id);
if (!$hm) {
    flash('error', 'That profile is not available.');
    redirect(rtrim(APP_URL, '/') . '/client/browse.php');
}

$skills = Housemaid::getSkillNames($id);
$languages = Housemaid::getLanguageNames($id);
$reviews = Review::listForHousemaid($id);
$qualifies = Booking::hasQualifyingBooking($clientId, $id);

// Any existing non-terminal request from this client for this housemaid?
$existing = null;
foreach (Booking::listByClient($clientId) as $b) {
    if ((int) $b['housemaid_id'] === $id && in_array($b['status'], ['requested', 'accepted'], true)) {
        $existing = $b;
        break;
    }
}

$pageTitle = $hm['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1><?= e($hm['full_name']) ?></h1>
            <p>
                <?= e($hm['nationality_name'] ?? '—') ?> ·
                <a href="<?= e(rtrim(APP_URL, '/')) ?>/client/agency_profile.php?id=<?= (int) $hm['agency_id'] ?>"><?= e($hm['agency_name']) ?></a>
                · <span class="pill <?= e($hm['availability_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $hm['availability_status']))) ?></span>
            </p>
        </div>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php">← Back to browse</a>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Years experience</div><div class="dv"><?= e((string) ($hm['years_experience'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Rating</div><div class="dv"><?= $hm['avg_rating'] ? '★ ' . e(number_format((float) $hm['avg_rating'], 1)) . " ({$hm['ratings_count']} review" . ($hm['ratings_count'] == 1 ? '' : 's') . ')' : 'No reviews yet' ?></div></div>
            <div class="detail-item"><div class="dl">Skills</div><div class="dv"><?= $skills ? e(implode(', ', $skills)) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Languages</div><div class="dv"><?= $languages ? e(implode(', ', $languages)) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Passport no.</div><div class="dv mono"><?= $qualifies ? e($hm['passport_number'] ?? '—') : e(mask_document_number($hm['passport_number'])) ?></div></div>
        </div>
        <?php if ($qualifies): ?>
            <p class="hint" style="margin-top:10px;">Full document number shown because you have a confirmed booking with her.</p>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:22px;">
        <h2>Book her</h2>
        <?php if ($existing): ?>
            <p>You already have a <strong><?= e($existing['status']) ?></strong> request for her. <a href="<?= e(rtrim(APP_URL, '/')) ?>/client/bookings.php">View your bookings →</a></p>
        <?php elseif ($hm['availability_status'] !== 'available'): ?>
            <p class="muted">Not currently available for a new booking.</p>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/client/booking_request.php?housemaid_id=<?= (int) $hm['id'] ?>">Request a booking</a>
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
                <?php if ($r['agency_response']): ?>
                    <div style="margin-top:8px;padding:10px 12px;background:var(--primary-soft);border-radius:8px;font-size:13.5px;">
                        <strong>Agency response:</strong> <?= nl2br(e($r['agency_response'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

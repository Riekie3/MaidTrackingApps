<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$id = (int) ($_GET['id'] ?? 0);
$hm = Housemaid::publicFindById($id);
if (!$hm) {
    flash('error', 'That profile is not available.');
    redirect(rtrim(APP_URL, '/') . '/client/browse.php');
}

$skills = Housemaid::getSkillNames($id);
$languages = Housemaid::getLanguageNames($id);
$breakdown = Review::categoryAverages('housemaid', $id);

// Verified incidents only — Reported/Under Review claims never appear
// here, or anywhere client-facing. See the proposal's "Handle with care" note.
$incidents = Incident::listVerifiedForProvider('housemaid', $id);

$pageTitle = 'Due-Diligence Report — ' . $hm['full_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="btn-row no-print" style="margin-bottom:18px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/candidate.php?id=<?= (int) $id ?>">← Back to profile</a>
    </div>

    <div class="report-header">
        <div class="brand">🧹 MaidTrack — Due-Diligence Report</div>
        <div class="meta">Generated <?= e(date(DATE_FORMAT_DISPLAY)) ?><br>for <?= e(current_name()) ?></div>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <h2><?= e($hm['full_name']) ?></h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Agency</div><div class="dv"><?= e($hm['agency_name']) ?></div></div>
            <div class="detail-item"><div class="dl">Nationality</div><div class="dv"><?= e($hm['nationality_name'] ?? '—') ?></div></div>
            <div class="detail-item"><div class="dl">Years experience</div><div class="dv"><?= e((string) ($hm['years_experience'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Skills</div><div class="dv"><?= $skills ? e(implode(', ', $skills)) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Languages</div><div class="dv"><?= $languages ? e(implode(', ', $languages)) : '—' ?></div></div>
            <div class="detail-item"><div class="dl">Passport / address</div><div class="dv muted">Redacted for this report — visible only after a confirmed booking</div></div>
        </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <h2>Rating breakdown</h2>
        <?php if (!$breakdown): ?>
            <p class="muted">No reviews yet.</p>
        <?php else: ?>
        <div class="stat-row">
            <div class="stat-card"><div class="num">★ <?= $breakdown['reliability'] ?></div><div class="label">Reliability</div></div>
            <div class="stat-card"><div class="num">★ <?= $breakdown['skill'] ?></div><div class="label">Skill</div></div>
            <div class="stat-card"><div class="num">★ <?= $breakdown['hygiene'] ?></div><div class="label">Hygiene</div></div>
            <div class="stat-card"><div class="num">★ <?= $breakdown['communication'] ?></div><div class="label">Communication</div></div>
        </div>
        <p class="muted" style="margin:0;">Based on <?= $breakdown['n'] ?> completed placement<?= $breakdown['n'] === 1 ? '' : 's' ?>.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Verified incidents</h2>
        <?php if (!$incidents): ?>
            <p class="muted">No verified incidents on record.</p>
        <?php else: ?>
            <?php foreach ($incidents as $inc): ?>
            <div style="border-top:1px solid var(--line);padding:10px 0;">
                <strong><?= e(ucwords(str_replace('_', ' ', $inc['incident_type']))) ?></strong>
                <span class="muted"> — <?= fmt_date($inc['created_at']) ?></span>
                <p style="margin:4px 0 0;"><?= nl2br(e($inc['description'])) ?></p>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

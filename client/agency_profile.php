<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$id = (int) ($_GET['id'] ?? 0);
$agency = Agency::publicFindById($id);
if (!$agency) {
    flash('error', 'That agency is not available.');
    redirect(rtrim(APP_URL, '/') . '/client/browse.php');
}

$stats = Agency::rosterStats($id);
$housemaids = Agency::publicHousemaids($id);

$pageTitle = $agency['company_name'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1><?= e($agency['company_name']) ?> <span class="pill approved">Verified agency</span></h1>
            <p><?= $stats['roster_count'] ?> housemaid<?= $stats['roster_count'] === 1 ? '' : 's' ?> listed<?= $stats['agency_rating'] ? ' · ★ ' . e(number_format($stats['agency_rating'], 1)) . ' average' : '' ?></p>
        </div>
        <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php">← Back to browse</a>
    </div>

    <?php if ($agency['bio']): ?>
    <div class="card" style="margin-bottom:22px;">
        <p style="margin:0;"><?= nl2br(e($agency['bio'])) ?></p>
    </div>
    <?php endif; ?>

    <h2>Housemaids from this agency</h2>
    <div class="quick-links">
        <?php if (!$housemaids): ?><p class="muted">No approved housemaids listed yet.</p><?php endif; ?>
        <?php foreach ($housemaids as $h): ?>
        <a class="quick-link" href="<?= e(rtrim(APP_URL, '/')) ?>/client/candidate.php?id=<?= (int) $h['id'] ?>">
            <div class="ql-title"><?= e($h['full_name']) ?></div>
            <div class="ql-desc"><?= e($h['nationality_name'] ?? '—') ?><?php if ($h['avg_rating']): ?> · ★ <?= e(number_format((float) $h['avg_rating'], 1)) ?><?php endif; ?></div>
            <div style="margin-top:8px;"><span class="pill <?= e($h['availability_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $h['availability_status']))) ?></span></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$adminId = current_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['incident_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'under_review') {
        Incident::markUnderReview($id, $adminId);
        flash('success', 'Marked under review.');
    } elseif ($action === 'verify') {
        Incident::verify($id, $adminId);
        flash('success', 'Incident verified — now visible on the housemaid\'s public profile and due-diligence report.');
    } elseif ($action === 'dismiss') {
        Incident::dismiss($id, $adminId);
        flash('success', 'Incident dismissed. Kept in the audit trail, not shown publicly.');
    }
    redirect(rtrim(APP_URL, '/') . '/admin/incidents.php');
}

$incidents = Incident::listOpen();
$pageTitle = 'Incidents';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Incidents</h1>
            <p><?= count($incidents) ?> open (reported or under review).</p>
        </div>
    </div>

    <?php if (!$incidents): ?>
        <div class="card"><p class="muted" style="margin:0;">Nothing open right now.</p></div>
    <?php endif; ?>

    <?php foreach ($incidents as $inc): ?>
    <div class="card" style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <strong><?= e($inc['provider_name']) ?></strong> · <?= e($inc['agency_name']) ?>
                <div class="muted" style="font-size:12.5px;">Reported by <?= e(ucfirst($inc['reported_by_type'])) ?> · <?= fmt_date($inc['created_at']) ?></div>
            </div>
            <span class="pill <?= $inc['status'] === 'under_review' ? 'pending' : 'rejected' ?>"><?= e(ucwords(str_replace('_', ' ', $inc['status']))) ?></span>
        </div>
        <p style="margin:10px 0;"><strong><?= e(ucwords(str_replace('_', ' ', $inc['incident_type']))) ?>:</strong> <?= nl2br(e($inc['description'])) ?></p>
        <?php if ($inc['evidence_path']): ?>
        <a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=incident_evidence&id=<?= (int) $inc['id'] ?>" target="_blank" rel="noopener">View evidence</a>
        <?php endif; ?>
        <div class="btn-row" style="margin-top:12px;">
            <?php if ($inc['status'] === 'reported'): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="incident_id" value="<?= (int) $inc['id'] ?>"><input type="hidden" name="action" value="under_review"><button class="btn btn-sm btn-outline" type="submit">Start review</button></form>
            <?php endif; ?>
            <form method="post" data-confirm="Verify this incident? It will become visible on <?= e($inc['provider_name']) ?>'s public profile."><?= csrf_field() ?><input type="hidden" name="incident_id" value="<?= (int) $inc['id'] ?>"><input type="hidden" name="action" value="verify"><button class="btn btn-sm btn-primary" type="submit">Verify</button></form>
            <form method="post" data-confirm="Dismiss this incident?"><?= csrf_field() ?><input type="hidden" name="incident_id" value="<?= (int) $inc['id'] ?>"><input type="hidden" name="action" value="dismiss"><button class="btn btn-sm btn-ghost" type="submit">Dismiss</button></form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

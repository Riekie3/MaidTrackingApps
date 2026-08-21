<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pending = Freelancer::listPending();
$pageTitle = 'Freelancer Applications';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Freelancer Applications</h1>
            <p>Independent housemaids awaiting your review — no agency has vetted these first, so review carefully.</p>
        </div>
    </div>

    <?php if (!$pending): ?>
        <div class="card"><p class="muted" style="margin:0;">Nothing pending right now.</p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Nationality</th><th>Verified</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($pending as $f): ?>
            <tr class="row-link" onclick="location.href='<?= e(rtrim(APP_URL, '/')) ?>/admin/freelancer_review.php?id=<?= (int) $f['id'] ?>'">
                <td><strong><?= e($f['full_name']) ?></strong></td>
                <td class="muted"><?= e($f['nationality_name'] ?? '—') ?></td>
                <td><?= $f['email_verified_at'] && $f['phone_verified_at'] ? '<span class="pill approved">Verified</span>' : '<span class="pill pending">Not yet</span>' ?></td>
                <td class="muted"><?= fmt_date($f['submitted_at']) ?></td>
                <td><a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/admin/freelancer_review.php?id=<?= (int) $f['id'] ?>">Review →</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

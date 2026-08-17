<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$rows = Housemaid::listByAgency($agencyId, $status ?: null);
if ($search !== '') {
    $rows = array_values(array_filter($rows, fn($r) => stripos($r['full_name'], $search) !== false));
}

$pageTitle = 'Roster';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Roster</h1>
            <p><?= count($rows) ?> housemaid<?= count($rows) === 1 ? '' : 's' ?><?= $status ? ' — ' . e(ucfirst($status)) : '' ?></p>
        </div>
        <a class="btn btn-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_add.php">+ Add housemaid</a>
    </div>

    <form method="get" class="btn-row" style="margin-bottom:18px;">
        <input type="text" name="q" placeholder="Search by name…" value="<?= e($search) ?>" style="max-width:260px;">
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Nationality</th><th>Approval</th><th>Availability</th><th>Submitted</th></tr></thead>
            <tbody>
                <?php if (!$rows): ?>
                <tr><td colspan="5" class="muted">No housemaids yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                <tr class="row-link" onclick="location.href='<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_view.php?id=<?= (int) $r['id'] ?>'">
                    <td><strong><?= e($r['full_name']) ?></strong></td>
                    <td><?= e($r['nationality_name'] ?? '—') ?></td>
                    <td><span class="pill <?= e($r['approval_status']) ?>"><?= e(ucfirst($r['approval_status'])) ?></span></td>
                    <td><?php if ($r['approval_status'] === 'approved'): ?><span class="pill <?= e($r['availability_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $r['availability_status']))) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                    <td class="muted"><?= fmt_date($r['submitted_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

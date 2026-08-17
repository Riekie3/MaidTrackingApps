<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$logs = AuditLog::recent(150);
$pageTitle = 'Audit Log';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Audit Log</h1>
            <p>Most recent 150 actions.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>Detail</th></tr></thead>
            <tbody>
            <?php if (!$logs): ?><tr><td colspan="5" class="muted">Nothing logged yet.</td></tr><?php endif; ?>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="muted mono" style="white-space:nowrap;"><?= e($log['created_at']) ?></td>
                <td><?= e(ucfirst($log['actor_type'])) ?> #<?= (int) $log['actor_id'] ?></td>
                <td class="mono"><?= e($log['action']) ?></td>
                <td><?= e($log['entity_type']) ?> #<?= (int) $log['entity_id'] ?></td>
                <td class="muted"><?= e($log['meta'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

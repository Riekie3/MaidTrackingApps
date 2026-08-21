<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('freelancer');

$freelancerId = current_id();
$f = Freelancer::findById($freelancerId);
$bookings = Booking::listByFreelancer($freelancerId);
$services = Freelancer::getServices($freelancerId);
$locations = Freelancer::getLocationNames($freelancerId);

$pageTitle = 'My Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Welcome, <?= e($f['full_name']) ?></h1>
            <p>Manage your services, pricing, and incoming booking requests.</p>
        </div>
    </div>

    <?php if ($f['approval_status'] === 'pending'): ?>
    <div class="card" style="margin-bottom:24px;border-color:var(--pending);background:var(--pending-soft);">
        <strong>Your application is pending Admin review.</strong>
        <p style="margin:6px 0 0;">You can set up your services and pricing now — clients just won't be able to find or book you until Admin approves your documents.</p>
    </div>
    <?php elseif ($f['approval_status'] === 'rejected'): ?>
    <div class="card" style="margin-bottom:24px;border-color:var(--danger);background:var(--danger-soft);">
        <strong>Your application was not approved.</strong>
        <?php if ($f['rejection_reason']): ?><p style="margin:6px 0 0;"><?= e($f['rejection_reason']) ?></p><?php endif; ?>
    </div>
    <?php elseif (!$services): ?>
    <div class="card" style="margin-bottom:24px;border-color:var(--primary);background:var(--primary-soft);">
        <strong>You haven't listed any services yet.</strong>
        <p style="margin:6px 0 0;">Clients can't find you until you add at least one service and price. <a href="<?= e(rtrim(APP_URL, '/')) ?>/freelancer/services.php">Set up services →</a></p>
    </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-card"><div class="num"><?= count($services) ?></div><div class="label">Services listed</div></div>
        <div class="stat-card"><div class="num"><?= count($locations) ?></div><div class="label">Areas covered</div></div>
        <div class="stat-card"><div class="num"><?= $f['avg_rating'] ? number_format((float) $f['avg_rating'], 1) : '—' ?></div><div class="label">Average rating</div></div>
        <div class="stat-card"><div class="num"><?= count(array_filter($bookings, fn($b) => $b['status'] === 'requested')) ?></div><div class="label">Pending requests</div></div>
    </div>

    <h2>Recent bookings</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Client</th><th>Service</th><th>Dates</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (!$bookings): ?>
                <tr><td colspan="4" class="muted">No booking requests yet.</td></tr>
                <?php endif; ?>
                <?php foreach (array_slice($bookings, 0, 5) as $b): ?>
                <tr class="row-link" onclick="location.href='<?= e(rtrim(APP_URL, '/')) ?>/freelancer/bookings.php'">
                    <td><?= e($b['client_name']) ?></td>
                    <td><?= e($b['service_name']) ?></td>
                    <td class="muted"><?= fmt_date($b['start_date']) ?><?= $b['end_date'] ? ' – ' . fmt_date($b['end_date']) : '' ?></td>
                    <td><span class="pill <?= $b['status'] === 'requested' ? 'pending' : ($b['status'] === 'completed' || $b['status'] === 'accepted' ? 'approved' : 'rejected') ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

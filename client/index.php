<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$bookings = Booking::listByClient($clientId);
$awaitingReview = Booking::completedBookingsAwaitingReview($clientId);

$pageTitle = 'My Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Welcome, <?= e(current_name()) ?></h1>
            <p>Browse verified housemaids and manage your bookings.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(rtrim(APP_URL, '/')) ?>/client/browse.php">Browse housemaids</a>
    </div>

    <?php if ($awaitingReview): ?>
    <div class="card" style="margin-bottom:24px;border-color:var(--pending);background:var(--pending-soft);">
        <strong>You have <?= count($awaitingReview) ?> completed booking<?= count($awaitingReview) === 1 ? '' : 's' ?> waiting for a review.</strong>
        <div class="btn-row" style="margin-top:10px;">
            <?php foreach ($awaitingReview as $b): ?>
            <a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/review.php?booking_id=<?= (int) $b['id'] ?>">Leave a review →</a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <h2>Recent bookings</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Housemaid</th><th>Agency</th><th>Status</th><th>Requested</th></tr></thead>
            <tbody>
                <?php if (!$bookings): ?>
                <tr><td colspan="4" class="muted">No bookings yet — browse housemaids to get started.</td></tr>
                <?php endif; ?>
                <?php foreach (array_slice($bookings, 0, 5) as $b): ?>
                <tr class="row-link" onclick="location.href='<?= e(rtrim(APP_URL, '/')) ?>/client/bookings.php'">
                    <td><?= e($b['housemaid_name']) ?></td>
                    <td><?= e($b['agency_name']) ?></td>
                    <td><span class="pill <?= $b['status'] === 'requested' ? 'pending' : ($b['status'] === 'completed' || $b['status'] === 'accepted' ? 'approved' : 'rejected') ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                    <td class="muted"><?= fmt_date($b['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

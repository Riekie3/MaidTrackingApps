<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    verify_csrf();
    $id = (int) $_POST['cancel_id'];
    $booking = Booking::findByIdForClient($id, $clientId);
    if ($booking && in_array($booking['status'], ['requested', 'accepted'], true)) {
        Booking::cancel($id, 'client', $clientId);
        flash('success', 'Booking cancelled.');
    }
    redirect(rtrim(APP_URL, '/') . '/client/bookings.php');
}

$bookings = Booking::listByClient($clientId);
$reviewed = [];
foreach ($bookings as $b) {
    if ($b['status'] === 'completed') {
        $reviewed[$b['id']] = Review::findByBooking((int) $b['id']) !== null;
    }
}

$pageTitle = 'My Bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>My bookings</h1>
            <p><?= count($bookings) ?> total</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Housemaid</th><th>Agency</th><th>Dates</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (!$bookings): ?>
                <tr><td colspan="5" class="muted">No bookings yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><a href="<?= e(rtrim(APP_URL, '/')) ?>/client/candidate.php?id=<?= (int) $b['housemaid_id'] ?>"><?= e($b['housemaid_name']) ?></a></td>
                    <td><?= e($b['agency_name']) ?></td>
                    <td class="muted"><?= fmt_date($b['start_date']) ?><?= $b['end_date'] ? ' – ' . fmt_date($b['end_date']) : '' ?></td>
                    <td><span class="pill <?= $b['status'] === 'requested' ? 'pending' : ($b['status'] === 'completed' || $b['status'] === 'accepted' ? 'approved' : 'rejected') ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                    <td class="actions">
                        <?php if (in_array($b['status'], ['requested', 'accepted'], true)): ?>
                        <form method="post" data-confirm="Cancel this booking request?" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="cancel_id" value="<?= (int) $b['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-ghost">Cancel</button>
                        </form>
                        <?php elseif ($b['status'] === 'completed' && empty($reviewed[$b['id']])): ?>
                        <a class="btn btn-sm btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/review.php?booking_id=<?= (int) $b['id'] ?>">Review</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

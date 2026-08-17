<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['booking_id'] ?? 0);
    $booking = Booking::findByIdForAgency($id, $agencyId);
    if ($booking) {
        $action = $_POST['action'] ?? '';
        if ($action === 'accept' && $booking['status'] === 'requested') {
            Booking::accept($id);
            flash('success', 'Booking accepted.');
        } elseif ($action === 'decline' && $booking['status'] === 'requested') {
            Booking::decline($id);
            flash('success', 'Booking declined.');
        } elseif ($action === 'complete' && $booking['status'] === 'accepted') {
            Booking::complete($id);
            flash('success', 'Booking marked completed — the client can now leave a review.');
        } elseif ($action === 'cancel' && in_array($booking['status'], ['requested', 'accepted'], true)) {
            Booking::cancel($id, 'agency', $agencyId);
            flash('success', 'Booking cancelled.');
        }
    }
    redirect(rtrim(APP_URL, '/') . '/agency/bookings.php');
}

$status = $_GET['status'] ?? '';
$bookings = Booking::listByAgency($agencyId, $status ?: null);

$pageTitle = 'Bookings';
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1>Bookings</h1>
            <p><?= count($bookings) ?> total<?= $status ? ' — ' . e(ucfirst($status)) : '' ?></p>
        </div>
    </div>

    <form method="get" class="btn-row" style="margin-bottom:18px;">
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="requested" <?= $status === 'requested' ? 'selected' : '' ?>>Requested</option>
            <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Accepted</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="declined" <?= $status === 'declined' ? 'selected' : '' ?>>Declined</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Housemaid</th><th>Client</th><th>Dates</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (!$bookings): ?>
                <tr><td colspan="5" class="muted">No bookings yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_view.php?id=<?= (int) $b['housemaid_id'] ?>"><?= e($b['housemaid_name']) ?></a></td>
                    <td><?= e($b['client_name']) ?><br><span class="muted"><?= e($b['client_phone']) ?></span></td>
                    <td class="muted"><?= fmt_date($b['start_date']) ?><?= $b['end_date'] ? ' – ' . fmt_date($b['end_date']) : '' ?></td>
                    <td><span class="pill <?= $b['status'] === 'requested' ? 'pending' : ($b['status'] === 'completed' || $b['status'] === 'accepted' ? 'approved' : 'rejected') ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                    <td class="actions">
                        <?php if ($b['status'] === 'requested'): ?>
                        <form method="post" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="accept"><button class="btn btn-sm btn-primary" type="submit">Accept</button></form>
                        <form method="post" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="decline"><button class="btn btn-sm btn-ghost" type="submit">Decline</button></form>
                        <?php elseif ($b['status'] === 'accepted'): ?>
                        <form method="post" style="display:inline;"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="complete"><button class="btn btn-sm btn-primary" type="submit">Mark completed</button></form>
                        <form method="post" style="display:inline;" data-confirm="Cancel this booking?"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="cancel"><button class="btn btn-sm btn-ghost" type="submit">Cancel</button></form>
                        <?php endif; ?>
                        <?php if ($b['notes']): ?><div class="hint" style="margin-top:6px;max-width:220px;"><?= e($b['notes']) ?></div><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

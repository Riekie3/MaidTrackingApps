<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$bookingId = (int) ($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$booking = Booking::findByIdForClient($bookingId, $clientId);

if (!$booking || $booking['status'] !== 'completed') {
    flash('error', 'That booking is not eligible for a review.');
    redirect(rtrim(APP_URL, '/') . '/client/bookings.php');
}
if (Review::findByBooking($bookingId)) {
    flash('error', 'You already reviewed this booking.');
    redirect(rtrim(APP_URL, '/') . '/client/bookings.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ratings = [];
    foreach (['reliability', 'skill', 'hygiene', 'communication'] as $cat) {
        $val = (int) ($_POST["rating_$cat"] ?? 0);
        if ($val < 1 || $val > 5) $errors[] = 'All four ratings are required (1–5).';
        $ratings[$cat] = $val;
    }
    $comment = trim($_POST['comment'] ?? '');

    if (!$errors) {
        Review::create([
            'booking_id' => $bookingId,
            'client_id' => $clientId,
            'housemaid_id' => (int) $booking['housemaid_id'],
            'rating_reliability' => $ratings['reliability'],
            'rating_skill' => $ratings['skill'],
            'rating_hygiene' => $ratings['hygiene'],
            'rating_communication' => $ratings['communication'],
            'comment' => $comment ?: null,
        ]);
        flash('success', 'Thanks — your review is live on ' . $booking['housemaid_name'] . "'s profile.");
        redirect(rtrim(APP_URL, '/') . '/client/bookings.php');
    }
}

$pageTitle = 'Leave a review';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Review <?= e($booking['housemaid_name']) ?></h1>
            <p>Based on your completed booking with <?= e($booking['agency_name']) ?></p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="booking_id" value="<?= (int) $bookingId ?>">
        <?php foreach (['reliability' => 'Reliability', 'skill' => 'Skill', 'hygiene' => 'Hygiene', 'communication' => 'Communication'] as $key => $label): ?>
        <div class="field">
            <label for="rating_<?= $key ?>"><?= e($label) ?></label>
            <select id="rating_<?= $key ?>" name="rating_<?= $key ?>" required>
                <option value="">Rate 1–5</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= $i ?> — <?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <?php endforeach; ?>
        <div class="field">
            <label for="comment">Comment <span class="muted" style="font-weight:400;">(optional)</span></label>
            <textarea id="comment" name="comment" placeholder="What was your experience?"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit review</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

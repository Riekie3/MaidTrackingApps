<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$housemaidId = (int) ($_GET['housemaid_id'] ?? $_POST['housemaid_id'] ?? 0);
$hm = Housemaid::publicFindById($housemaidId);
if (!$hm || $hm['availability_status'] !== 'available') {
    flash('error', 'That housemaid is not available for booking right now.');
    redirect(rtrim(APP_URL, '/') . '/client/browse.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$startDate) $errors[] = 'Start date is required.';
    if ($endDate && $startDate && $endDate < $startDate) $errors[] = 'End date can\'t be before the start date.';

    if (!$errors) {
        $id = Booking::create([
            'client_id' => $clientId,
            'provider_type' => 'housemaid',
            'provider_id' => $housemaidId,
            'agency_id' => $hm['agency_id'],
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'notes' => $notes ?: null,
        ]);
        flash('success', 'Booking request sent to ' . $hm['agency_name'] . '.');
        redirect(rtrim(APP_URL, '/') . '/client/bookings.php');
    }
}

$pageTitle = 'Request a booking';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Request a booking</h1>
            <p>For <strong><?= e($hm['full_name']) ?></strong>, via <?= e($hm['agency_name']) ?></p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="housemaid_id" value="<?= (int) $housemaidId ?>">
        <div class="field-row">
            <div class="field">
                <label for="start_date">Start date</label>
                <input type="date" id="start_date" name="start_date" required value="<?= e($_POST['start_date'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="end_date">End date <span class="muted" style="font-weight:400;">(optional)</span></label>
                <input type="date" id="end_date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>">
            </div>
        </div>
        <div class="field">
            <label for="notes">Notes for the agency <span class="muted" style="font-weight:400;">(optional)</span></label>
            <textarea id="notes" name="notes" placeholder="Household size, live-in or live-out, specific needs…"><?= e($_POST['notes'] ?? '') ?></textarea>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Send request</button>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/candidate.php?id=<?= (int) $housemaidId ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

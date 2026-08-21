<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
$freelancerId = (int) ($_GET['freelancer_id'] ?? $_POST['freelancer_id'] ?? 0);
$f = Freelancer::publicFindById($freelancerId);
if (!$f || $f['availability_status'] !== 'available') {
    flash('error', 'That freelancer is not available for booking right now.');
    redirect(rtrim(APP_URL, '/') . '/client/browse.php');
}

$services = Freelancer::getServices($freelancerId);
if (!$services) {
    flash('error', 'This freelancer has not listed any services to book.');
    redirect(rtrim(APP_URL, '/') . '/client/freelancer.php?id=' . $freelancerId);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    $serviceIds = array_column($services, 'service_id');
    if (!in_array($serviceId, $serviceIds, true)) $errors[] = 'Choose a service she actually offers.';
    if (!$startDate) $errors[] = 'Start date is required.';
    if ($endDate && $startDate && $endDate < $startDate) $errors[] = 'End date can\'t be before the start date.';

    if (!$errors) {
        $id = Booking::create([
            'client_id' => $clientId,
            'provider_type' => 'freelancer',
            'provider_id' => $freelancerId,
            'service_id' => $serviceId,
            'start_date' => $startDate,
            'end_date' => $endDate ?: null,
            'notes' => $notes ?: null,
        ]);
        flash('success', 'Booking request sent to ' . $f['full_name'] . '.');
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
            <p>For <strong><?= e($f['full_name']) ?></strong>, an independent freelancer</p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="freelancer_id" value="<?= (int) $freelancerId ?>">
        <div class="field">
            <label for="service_id">Service</label>
            <select id="service_id" name="service_id" required>
                <option value="">Select…</option>
                <?php foreach ($services as $s): ?>
                <option value="<?= (int) $s['service_id'] ?>" <?= (string) ($_POST['service_id'] ?? '') === (string) $s['service_id'] ? 'selected' : '' ?>>
                    <?= e($s['service_name']) ?> — RM<?= e(number_format((float) $s['price'], 2)) ?> / <?= $s['price_unit'] === 'per_job' ? 'job' : ($s['price_unit'] === 'hourly' ? 'hour' : 'day') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="start_date">Start date</label>
                <input type="date" id="start_date" name="start_date" required value="<?= e($_POST['start_date'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="end_date">End date <span class="muted" style="font-weight:400;">(optional — leave blank for a single day)</span></label>
                <input type="date" id="end_date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>">
            </div>
        </div>
        <div class="field">
            <label for="notes">Notes <span class="muted" style="font-weight:400;">(optional)</span></label>
            <textarea id="notes" name="notes" placeholder="Anything she should know before accepting…"><?= e($_POST['notes'] ?? '') ?></textarea>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Send request</button>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/client/freelancer.php?id=<?= (int) $freelancerId ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

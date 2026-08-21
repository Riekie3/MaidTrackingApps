<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('client');

$clientId = current_id();
// housemaid_id kept for backward compatibility with existing links —
// provider_type/provider_id is the general form, used for freelancers too.
$providerType = $_GET['provider_type'] ?? $_POST['provider_type'] ?? 'housemaid';
$providerType = in_array($providerType, ['housemaid', 'freelancer'], true) ? $providerType : 'housemaid';
$providerId = (int) ($_GET['provider_id'] ?? $_POST['provider_id'] ?? $_GET['housemaid_id'] ?? $_POST['housemaid_id'] ?? 0);

$provider = $providerType === 'freelancer'
    ? Freelancer::publicFindById($providerId)
    : Housemaid::publicFindById($providerId);
$providerName = $provider['full_name'] ?? null;
$profileUrl = $providerType === 'freelancer'
    ? rtrim(APP_URL, '/') . '/client/freelancer.php?id=' . $providerId
    : rtrim(APP_URL, '/') . '/client/candidate.php?id=' . $providerId;

// Only a client who actually had a booking with her can file a report
// against her — prevents drive-by defamation from someone who never hired her.
if (!$provider || !Booking::hasQualifyingBooking($clientId, $providerType, $providerId)) {
    flash('error', 'You can only report an incident for someone you\'ve had a booking with.');
    redirect(rtrim(APP_URL, '/') . '/client/bookings.php');
}

$errors = [];
$types = ['ran_away', 'theft', 'abuse_allegation', 'contract_breach', 'other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = $_POST['incident_type'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!in_array($type, $types, true)) $errors[] = 'Choose an incident type.';
    if ($description === '') $errors[] = 'Describe what happened.';

    if (!$errors) {
        $evidence = handle_upload('evidence', UPLOAD_INCIDENT_DIR);
        Incident::create([
            'provider_type' => $providerType,
            'provider_id' => $providerId,
            'reported_by_type' => 'client',
            'reported_by_id' => $clientId,
            'incident_type' => $type,
            'description' => $description,
            'evidence_path' => $evidence,
        ]);
        flash('success', 'Report filed. Admin will review it before it appears anywhere public.');
        redirect($profileUrl);
    }
}

$pageTitle = 'Report an incident';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Report an incident</h1>
            <p>For <strong><?= e($providerName) ?></strong>. Admin reviews every report before it's ever shown publicly.</p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="provider_type" value="<?= e($providerType) ?>">
        <input type="hidden" name="provider_id" value="<?= (int) $providerId ?>">
        <div class="field">
            <label for="incident_type">Type</label>
            <select id="incident_type" name="incident_type" required>
                <option value="">Select…</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= $t ?>"><?= e(ucwords(str_replace('_', ' ', $t))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="description">What happened</label>
            <textarea id="description" name="description" required></textarea>
        </div>
        <div class="field">
            <label for="evidence">Evidence <span class="muted" style="font-weight:400;">(optional)</span></label>
            <input type="file" id="evidence" name="evidence" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Submit report</button>
            <a class="btn btn-outline" href="<?= e($profileUrl) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

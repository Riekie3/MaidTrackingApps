<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$housemaidId = (int) ($_GET['housemaid_id'] ?? $_POST['housemaid_id'] ?? 0);
$hm = Housemaid::findByIdForAgency($housemaidId, $agencyId);
if (!$hm) {
    flash('error', 'Housemaid not found.');
    redirect(rtrim(APP_URL, '/') . '/agency/housemaids.php');
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
        $id = Incident::create([
            'housemaid_id' => $housemaidId,
            'reported_by_type' => 'agency',
            'reported_by_id' => $agencyId,
            'incident_type' => $type,
            'description' => $description,
            'evidence_path' => $evidence,
        ]);
        flash('success', 'Report filed. Admin will review it before it appears anywhere public.');
        redirect(rtrim(APP_URL, '/') . '/agency/housemaid_view.php?id=' . $housemaidId);
    }
}

$pageTitle = 'Report an incident';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Report an incident</h1>
            <p>For <strong><?= e($hm['full_name']) ?></strong>. Admin reviews every report before it's ever shown publicly.</p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="housemaid_id" value="<?= (int) $housemaidId ?>">
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
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_view.php?id=<?= (int) $housemaidId ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

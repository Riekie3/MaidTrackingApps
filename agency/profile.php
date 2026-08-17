<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$agency = Agency::findById($agencyId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    Agency::updateProfile($agencyId, [
        'company_name'   => trim($_POST['company_name'] ?? $agency['company_name']),
        'contact_person' => trim($_POST['contact_person'] ?? $agency['contact_person']),
        'phone'          => trim($_POST['phone'] ?? $agency['phone']),
        'address'        => trim($_POST['address'] ?? ''),
        'bio'            => trim($_POST['bio'] ?? ''),
    ]);

    if (!empty($_FILES['logo']['name'])) {
        $logoPath = handle_upload('logo', UPLOAD_AGENCY_DIR);
        if ($logoPath) {
            Agency::updateLogo($agencyId, $logoPath);
        }
    }

    flash('success', 'Profile updated.');
    redirect(rtrim(APP_URL, '/') . '/agency/profile.php');
}

$agency = Agency::findById($agencyId);
$pageTitle = 'Agency Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Agency profile</h1>
            <p>What clients see once your account is approved.</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field">
            <label for="company_name">Company name</label>
            <input type="text" id="company_name" name="company_name" required value="<?= e($agency['company_name']) ?>">
        </div>
        <div class="field-row">
            <div class="field">
                <label for="contact_person">Contact person</label>
                <input type="text" id="contact_person" name="contact_person" required value="<?= e($agency['contact_person']) ?>">
            </div>
            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" required value="<?= e($agency['phone']) ?>">
            </div>
        </div>
        <div class="field">
            <label for="address">Business address</label>
            <textarea id="address" name="address"><?= e($agency['address'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label for="bio">Public description</label>
            <textarea id="bio" name="bio"><?= e($agency['bio'] ?? '') ?></textarea>
        </div>
        <div class="field">
            <label for="logo">Logo</label>
            <?php if ($agency['logo_path']): ?>
                <p class="hint">Current: <?= e($agency['logo_path']) ?></p>
            <?php endif; ?>
            <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png">
        </div>
        <button type="submit" class="btn btn-primary">Save changes</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

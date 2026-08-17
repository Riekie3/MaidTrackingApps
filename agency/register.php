<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(dashboard_url_for(current_role()));
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = $_POST;

    $companyName = trim($_POST['company_name'] ?? '');
    $regNumber   = trim($_POST['registration_number'] ?? '');
    $contact     = trim($_POST['contact_person'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['password_confirm'] ?? '';

    if ($companyName === '') $errors[] = 'Company name is required.';
    if ($regNumber === '') $errors[] = 'Registration / license number is required.';
    if ($contact === '') $errors[] = 'Contact person is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($_FILES['license_document']['name'])) $errors[] = 'Upload your business license / SSM document.';

    if (!$errors && Agency::findByEmail($email)) {
        $errors[] = 'An agency account with this email already exists.';
    }

    if (!$errors) {
        $licensePath = handle_upload('license_document', UPLOAD_AGENCY_DIR);
        if (!$licensePath) {
            $errors[] = 'License document upload failed — use a PDF, JPG, or PNG under 10MB.';
        } else {
            $id = Agency::create([
                'company_name'          => $companyName,
                'registration_number'   => $regNumber,
                'license_document_path' => $licensePath,
                'contact_person'        => $contact,
                'email'                 => $email,
                'phone'                 => $phone,
                'password_hash'         => password_hash($password, PASSWORD_DEFAULT),
                'address'               => $address ?: null,
                'bio'                   => $bio ?: null,
            ]);
            AuditLog::record('agency', $id, 'agency.register', 'agency', $id);
            flash('success', 'Registration submitted. An admin will review your license and documents before your account is activated.');
            redirect(rtrim(APP_URL, '/') . '/login.php');
        }
    }
}

$pageTitle = 'Register your agency';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Register your agency</h1>
            <p>Your account stays inactive until Admin reviews your license and approves it.</p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <fieldset>
            <legend>Company details</legend>
            <div class="field">
                <label for="company_name">Company name</label>
                <input type="text" id="company_name" name="company_name" required value="<?= e($old['company_name'] ?? '') ?>">
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="registration_number">Registration / SSM number</label>
                    <input type="text" id="registration_number" name="registration_number" required value="<?= e($old['registration_number'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="contact_person">Contact person</label>
                    <input type="text" id="contact_person" name="contact_person" required value="<?= e($old['contact_person'] ?? '') ?>">
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="email">Email (used to log in)</label>
                    <input type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required value="<?= e($old['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="field">
                <label for="address">Business address</label>
                <textarea id="address" name="address"><?= e($old['address'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="bio">Public description <span class="muted" style="font-weight:400;">(shown on your public agency profile once approved)</span></label>
                <textarea id="bio" name="bio"><?= e($old['bio'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="license_document">Business license / SSM document</label>
                <input type="file" id="license_document" name="license_document" accept=".pdf,.jpg,.jpeg,.png" required>
                <p class="hint">PDF, JPG, or PNG, up to 10MB. Admin reviews this before approving your account.</p>
            </div>
        </fieldset>

        <fieldset>
            <legend>Login</legend>
            <div class="field-row">
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div class="field">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                </div>
            </div>
        </fieldset>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Submit for approval</button>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/login.php">Already have an account? Log in</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

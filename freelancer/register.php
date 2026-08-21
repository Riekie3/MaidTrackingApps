<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(dashboard_url_for(current_role()));
}

// Single-page form, not the agency's multi-step housemaid wizard — a
// freelancer is filling this in for herself, not an agency staffer
// keying in someone else's paperwork across several sittings. Services
// and pricing are deliberately NOT part of registration; they're an
// ongoing thing she sets up after approval (freelancer/services.php).

$errors = [];
$old = [];
$countries = MasterData::countries();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = $_POST;

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if (!($_POST['nationality_country_id'] ?? '')) $errors[] = 'Nationality is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!isset($_POST['consent'])) $errors[] = 'PDPA consent is required to create an account.';

    if (!$errors && Freelancer::findByEmail($email)) {
        $errors[] = 'An account with this email already exists.';
    }

    if (!$errors) {
        $photoPath = handle_upload('photo', UPLOAD_FREELANCER_DIR);

        $id = Freelancer::create([
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName,
            'photo_path' => $photoPath,
            'date_of_birth' => ($_POST['date_of_birth'] ?? '') ?: null,
            'gender' => $_POST['gender'] ?? 'female',
            'nationality_country_id' => (int) $_POST['nationality_country_id'],
            'marital_status' => ($_POST['marital_status'] ?? '') ?: null,
            'religion' => trim($_POST['religion'] ?? '') ?: null,
            'passport_number' => trim($_POST['passport_number'] ?? '') ?: null,
            'passport_expiry' => ($_POST['passport_expiry'] ?? '') ?: null,
            'work_permit_number' => trim($_POST['work_permit_number'] ?? '') ?: null,
            'work_permit_expiry' => ($_POST['work_permit_expiry'] ?? '') ?: null,
            'national_id_number' => trim($_POST['national_id_number'] ?? '') ?: null,
            'home_address' => trim($_POST['home_address'] ?? '') ?: null,
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? '') ?: null,
            'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? '') ?: null,
            'current_staying_address' => trim($_POST['current_staying_address'] ?? '') ?: null,
            'years_experience' => ($_POST['years_experience'] ?? '') !== '' ? (int) $_POST['years_experience'] : null,
            'bank_name' => trim($_POST['bank_name'] ?? '') ?: null,
            'bank_account_holder' => trim($_POST['bank_account_holder'] ?? '') ?: null,
            'bank_account_number' => trim($_POST['bank_account_number'] ?? '') ?: null,
        ]);

        getDB()->prepare('UPDATE freelancers SET consent_given_at = NOW() WHERE id = ?')->execute([$id]);

        $docFields = [
            'passport' => 'passport_scan',
            'work_permit' => 'work_permit_scan',
            'national_id' => 'national_id_scan',
            'police_clearance' => 'police_clearance_scan',
            'medical_report' => 'medical_report_scan',
        ];
        foreach ($docFields as $docType => $fieldName) {
            $file = handle_upload($fieldName, UPLOAD_FREELANCER_DIR);
            if ($file) {
                $expiry = $docType === 'passport' ? ($_POST['passport_expiry'] ?? null)
                    : ($docType === 'work_permit' ? ($_POST['work_permit_expiry'] ?? null) : null);
                FreelancerDocument::create($id, $docType, $file, null, null, $expiry ?: null);
            }
        }

        AuditLog::record('freelancer', $id, 'freelancer.register', 'freelancer', $id);
        $_SESSION['pending_freelancer_id'] = $id;
        redirect(rtrim(APP_URL, '/') . '/freelancer/verify.php');
    }
}

$pageTitle = 'Register as a freelancer';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>Register as an independent housemaid</h1>
            <p>You'll verify your email and phone next, then wait on Admin to review your documents before clients can book you.</p>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <fieldset>
            <legend>Identity</legend>
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" required value="<?= e($old['full_name'] ?? '') ?>">
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="date_of_birth">Date of birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($old['date_of_birth'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="female" <?= ($old['gender'] ?? 'female') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="male" <?= ($old['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="nationality_country_id">Nationality</label>
                    <select id="nationality_country_id" name="nationality_country_id" required>
                        <option value="">Select…</option>
                        <?php foreach ($countries as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string) ($old['nationality_country_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="marital_status">Marital status</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">—</option>
                        <?php foreach (['single', 'married', 'divorced', 'widowed'] as $ms): ?>
                        <option value="<?= $ms ?>" <?= ($old['marital_status'] ?? '') === $ms ? 'selected' : '' ?>><?= ucfirst($ms) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="years_experience">Years of experience</label>
                <input type="number" id="years_experience" name="years_experience" min="0" max="60" value="<?= e((string) ($old['years_experience'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="photo">Photo</label>
                <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png">
            </div>
        </fieldset>

        <fieldset>
            <legend>Documents <span class="muted" style="font-weight:400;">— same set an agency housemaid provides</span></legend>
            <div class="field-row">
                <div class="field"><label>Passport number</label><input type="text" name="passport_number" value="<?= e($old['passport_number'] ?? '') ?>"></div>
                <div class="field"><label>Passport expiry</label><input type="date" name="passport_expiry" value="<?= e($old['passport_expiry'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Passport scan</label><input type="file" name="passport_scan" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field-row">
                <div class="field"><label>Work permit number</label><input type="text" name="work_permit_number" value="<?= e($old['work_permit_number'] ?? '') ?>"></div>
                <div class="field"><label>Work permit expiry</label><input type="date" name="work_permit_expiry" value="<?= e($old['work_permit_expiry'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Work permit scan</label><input type="file" name="work_permit_scan" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>National ID number</label><input type="text" name="national_id_number" value="<?= e($old['national_id_number'] ?? '') ?>"></div>
            <div class="field"><label>National ID scan</label><input type="file" name="national_id_scan" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>Police clearance <span class="muted" style="font-weight:400;">(optional)</span></label><input type="file" name="police_clearance_scan" accept=".pdf,.jpg,.jpeg,.png"></div>
            <div class="field"><label>Medical report <span class="muted" style="font-weight:400;">(optional)</span></label><input type="file" name="medical_report_scan" accept=".pdf,.jpg,.jpeg,.png"></div>
        </fieldset>

        <fieldset>
            <legend>Address</legend>
            <div class="field"><label>Home address <span class="muted" style="font-weight:400;">(country of origin)</span></label><textarea name="home_address"><?= e($old['home_address'] ?? '') ?></textarea></div>
            <div class="field-row">
                <div class="field"><label>Emergency contact name</label><input type="text" name="emergency_contact_name" value="<?= e($old['emergency_contact_name'] ?? '') ?>"></div>
                <div class="field"><label>Emergency contact phone</label><input type="tel" name="emergency_contact_phone" value="<?= e($old['emergency_contact_phone'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Current staying address</label><textarea name="current_staying_address"><?= e($old['current_staying_address'] ?? '') ?></textarea></div>
        </fieldset>

        <fieldset>
            <legend>Banking <span class="muted" style="font-weight:400;">— for client payouts, restricted to you and Admin</span></legend>
            <div class="field"><label>Bank name</label><input type="text" name="bank_name" value="<?= e($old['bank_name'] ?? '') ?>"></div>
            <div class="field-row">
                <div class="field"><label>Account holder name</label><input type="text" name="bank_account_holder" value="<?= e($old['bank_account_holder'] ?? '') ?>"></div>
                <div class="field"><label>Account number</label><input type="text" name="bank_account_number" value="<?= e($old['bank_account_number'] ?? '') ?>"></div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Login</legend>
            <div class="field-row">
                <div class="field"><label for="email">Email (used to log in)</label><input type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>"></div>
                <div class="field"><label for="phone">Phone</label><input type="tel" id="phone" name="phone" required value="<?= e($old['phone'] ?? '') ?>"></div>
            </div>
            <div class="field-row">
                <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required minlength="8"></div>
                <div class="field"><label for="password_confirm">Confirm password</label><input type="password" id="password_confirm" name="password_confirm" required minlength="8"></div>
            </div>
        </fieldset>

        <div class="checkbox-item" style="margin-bottom:16px;">
            <input type="checkbox" id="consent" name="consent" <?= isset($old['consent']) ? 'checked' : '' ?>>
            <label for="consent" style="font-weight:400;">I consent to MaidTrack collecting and using my personal data, photo, and documents to provide this service, in line with Malaysia's PDPA 2010.</label>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Create account</button>
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/login.php">Already have an account? Log in</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

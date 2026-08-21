<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('freelancer');

$freelancerId = current_id();
$f = Freelancer::findById($freelancerId);
$countries = MasterData::countries();
$locationsByState = MasterData::locationsByState();
$myLocationIds = Freelancer::getLocationIds($freelancerId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    Freelancer::updateProfile($freelancerId, [
        'full_name' => trim($_POST['full_name'] ?? $f['full_name']),
        'date_of_birth' => ($_POST['date_of_birth'] ?? '') ?: null,
        'gender' => $_POST['gender'] ?? $f['gender'],
        'nationality_country_id' => (int) ($_POST['nationality_country_id'] ?? $f['nationality_country_id']),
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

    if (!empty($_FILES['photo']['name'])) {
        $photoPath = handle_upload('photo', UPLOAD_FREELANCER_DIR);
        if ($photoPath) {
            Freelancer::updatePhoto($freelancerId, $photoPath);
        }
    }

    $locationIds = array_map('intval', $_POST['locations'] ?? []);
    Freelancer::attachLocations($freelancerId, $locationIds);

    flash('success', 'Profile updated.');
    redirect(rtrim(APP_URL, '/') . '/freelancer/profile.php');
}

$f = Freelancer::findById($freelancerId);
$myLocationIds = Freelancer::getLocationIds($freelancerId);

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1>My profile</h1>
            <p>What Admin reviews and, once approved, what clients see.</p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <fieldset>
            <legend>Identity</legend>
            <div class="field">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" required value="<?= e($f['full_name']) ?>">
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="date_of_birth">Date of birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($f['date_of_birth'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="female" <?= $f['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="male" <?= $f['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="nationality_country_id">Nationality</label>
                    <select id="nationality_country_id" name="nationality_country_id" required>
                        <?php foreach ($countries as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $f['nationality_country_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="years_experience">Years of experience</label>
                    <input type="number" id="years_experience" name="years_experience" min="0" max="60" value="<?= e((string) ($f['years_experience'] ?? '')) ?>">
                </div>
            </div>
            <div class="field">
                <label for="photo">Photo</label>
                <?php if ($f['photo_path']): ?>
                    <p class="hint"><img src="<?= e(rtrim(APP_URL, '/')) ?>/download.php?kind=freelancer_photo&id=<?= (int) $freelancerId ?>" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover;"></p>
                <?php endif; ?>
                <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png">
            </div>
        </fieldset>

        <fieldset>
            <legend>Documents</legend>
            <p class="hint" style="margin-top:0;">Uploaded scans are managed at registration; number/expiry fields can be updated here. To replace a scan, contact Admin.</p>
            <div class="field-row">
                <div class="field"><label>Passport number</label><input type="text" name="passport_number" value="<?= e($f['passport_number'] ?? '') ?>"></div>
                <div class="field"><label>Passport expiry</label><input type="date" name="passport_expiry" value="<?= e($f['passport_expiry'] ?? '') ?>"></div>
            </div>
            <div class="field-row">
                <div class="field"><label>Work permit number</label><input type="text" name="work_permit_number" value="<?= e($f['work_permit_number'] ?? '') ?>"></div>
                <div class="field"><label>Work permit expiry</label><input type="date" name="work_permit_expiry" value="<?= e($f['work_permit_expiry'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>National ID number</label><input type="text" name="national_id_number" value="<?= e($f['national_id_number'] ?? '') ?>"></div>
        </fieldset>

        <fieldset>
            <legend>Service areas <span class="muted" style="font-weight:400;">— where you'll actually work, not your nationality</span></legend>
            <?php foreach ($locationsByState as $state => $locs): ?>
            <details style="margin-bottom:8px;">
                <summary style="cursor:pointer;font-weight:600;padding:6px 0;"><?= e($state) ?></summary>
                <div class="checkbox-grid">
                    <?php foreach ($locs as $l): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" id="loc_<?= (int) $l['id'] ?>" name="locations[]" value="<?= (int) $l['id'] ?>" <?= in_array((int) $l['id'], $myLocationIds, true) ? 'checked' : '' ?>>
                        <label for="loc_<?= (int) $l['id'] ?>"><?= e($l['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Address</legend>
            <div class="field"><label>Home address <span class="muted" style="font-weight:400;">(country of origin)</span></label><textarea name="home_address"><?= e($f['home_address'] ?? '') ?></textarea></div>
            <div class="field-row">
                <div class="field"><label>Emergency contact name</label><input type="text" name="emergency_contact_name" value="<?= e($f['emergency_contact_name'] ?? '') ?>"></div>
                <div class="field"><label>Emergency contact phone</label><input type="tel" name="emergency_contact_phone" value="<?= e($f['emergency_contact_phone'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Current staying address</label><textarea name="current_staying_address"><?= e($f['current_staying_address'] ?? '') ?></textarea></div>
        </fieldset>

        <fieldset>
            <legend>Banking</legend>
            <div class="field"><label>Bank name</label><input type="text" name="bank_name" value="<?= e($f['bank_name'] ?? '') ?>"></div>
            <div class="field-row">
                <div class="field"><label>Account holder name</label><input type="text" name="bank_account_holder" value="<?= e($f['bank_account_holder'] ?? '') ?>"></div>
                <div class="field"><label>Account number</label><input type="text" name="bank_account_number" value="<?= e($f['bank_account_number'] ?? '') ?>"></div>
            </div>
        </fieldset>

        <button type="submit" class="btn btn-primary">Save changes</button>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

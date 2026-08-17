<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('agency');

$agencyId = current_id();
$stepLabels = [1 => 'Identity', 2 => 'Documents', 3 => 'Address', 4 => 'Skills & Experience', 5 => 'Review & Submit'];

// Fresh entry point — no step param at all means "start a new intake".
if (!isset($_GET['step']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['hm_draft'] = ['skills' => [], 'languages' => [], 'work_countries' => [], 'documents' => []];
    redirect(rtrim(APP_URL, '/') . '/agency/housemaid_add.php?step=1');
}

if (!isset($_SESSION['hm_draft'])) {
    $_SESSION['hm_draft'] = ['skills' => [], 'languages' => [], 'work_countries' => [], 'documents' => []];
}
$draft = &$_SESSION['hm_draft'];

$step = (int) ($_POST['step'] ?? $_GET['step'] ?? 1);
$step = max(1, min(5, $step));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if ($step === 1) {
        $draft['full_name'] = trim($_POST['full_name'] ?? '');
        $draft['date_of_birth'] = $_POST['date_of_birth'] ?? '';
        $draft['gender'] = $_POST['gender'] ?? 'female';
        $draft['nationality_country_id'] = $_POST['nationality_country_id'] ?? '';
        $draft['marital_status'] = $_POST['marital_status'] ?? '';
        $draft['religion'] = trim($_POST['religion'] ?? '');

        if ($draft['full_name'] === '') $errors[] = 'Full name is required.';
        if (!$draft['nationality_country_id']) $errors[] = 'Nationality is required.';

        $photo = handle_upload('photo', UPLOAD_TMP_DIR);
        if ($photo) $draft['photo_tmp'] = $photo;

        if (!$errors) redirect(rtrim(APP_URL, '/') . '/agency/housemaid_add.php?step=2');
    }

    if ($step === 2) {
        $draft['passport_number'] = trim($_POST['passport_number'] ?? '');
        $draft['passport_expiry'] = $_POST['passport_expiry'] ?? '';
        $draft['work_permit_number'] = trim($_POST['work_permit_number'] ?? '');
        $draft['work_permit_expiry'] = $_POST['work_permit_expiry'] ?? '';
        $draft['national_id_number'] = trim($_POST['national_id_number'] ?? '');

        $docFields = [
            'passport'         => 'passport_scan',
            'work_permit'      => 'work_permit_scan',
            'national_id'      => 'national_id_scan',
            'police_clearance' => 'police_clearance_scan',
            'medical_report'   => 'medical_report_scan',
        ];
        foreach ($docFields as $docType => $fieldName) {
            $tmp = handle_upload($fieldName, UPLOAD_TMP_DIR);
            if ($tmp) {
                $draft['documents'][$docType] = [
                    'tmp'    => $tmp,
                    'expiry' => $docType === 'passport' ? $draft['passport_expiry'] : ($docType === 'work_permit' ? $draft['work_permit_expiry'] : null),
                ];
            }
        }

        if (!$errors) redirect(rtrim(APP_URL, '/') . '/agency/housemaid_add.php?step=3');
    }

    if ($step === 3) {
        $draft['home_address'] = trim($_POST['home_address'] ?? '');
        $draft['emergency_contact_name'] = trim($_POST['emergency_contact_name'] ?? '');
        $draft['emergency_contact_phone'] = trim($_POST['emergency_contact_phone'] ?? '');
        $draft['current_staying_address'] = trim($_POST['current_staying_address'] ?? '');

        if (!$errors) redirect(rtrim(APP_URL, '/') . '/agency/housemaid_add.php?step=4');
    }

    if ($step === 4) {
        $draft['years_experience'] = $_POST['years_experience'] ?? '';
        $draft['skills'] = array_map('intval', $_POST['skills'] ?? []);
        $draft['languages'] = array_map('intval', $_POST['languages'] ?? []);
        $draft['work_countries'] = array_map('intval', $_POST['work_countries'] ?? []);
        $draft['consent'] = isset($_POST['consent']);

        if (!$draft['consent']) $errors[] = 'PDPA consent is required before this profile can be submitted.';

        if (!$errors) redirect(rtrim(APP_URL, '/') . '/agency/housemaid_add.php?step=5');
    }

    if ($step === 5 && isset($_POST['confirm_submit'])) {
        $countryId = $draft['nationality_country_id'] ?: null;
        $hmId = Housemaid::create([
            'agency_id'               => $agencyId,
            'full_name'               => $draft['full_name'],
            'photo_path'              => null, // set after promotion below
            'date_of_birth'           => $draft['date_of_birth'] ?: null,
            'gender'                  => $draft['gender'],
            'nationality_country_id'  => $countryId,
            'marital_status'          => $draft['marital_status'] ?: null,
            'religion'                => $draft['religion'] ?: null,
            'passport_number'         => $draft['passport_number'] ?: null,
            'passport_expiry'         => $draft['passport_expiry'] ?: null,
            'work_permit_number'      => $draft['work_permit_number'] ?: null,
            'work_permit_expiry'      => $draft['work_permit_expiry'] ?: null,
            'national_id_number'      => $draft['national_id_number'] ?: null,
            'home_address'            => $draft['home_address'] ?: null,
            'emergency_contact_name'  => $draft['emergency_contact_name'] ?: null,
            'emergency_contact_phone' => $draft['emergency_contact_phone'] ?: null,
            'current_staying_address' => $draft['current_staying_address'] ?: null,
            'years_experience'        => $draft['years_experience'] !== '' ? (int) $draft['years_experience'] : null,
        ]);

        if (!empty($draft['photo_tmp'])) {
            $photoFile = promote_upload($draft['photo_tmp'], UPLOAD_HOUSEMAID_DIR);
            if ($photoFile) {
                getDB()->prepare('UPDATE housemaids SET photo_path = ? WHERE id = ?')->execute([$photoFile, $hmId]);
            }
        }

        foreach ($draft['documents'] as $docType => $doc) {
            $file = promote_upload($doc['tmp'], UPLOAD_HOUSEMAID_DIR);
            if ($file) {
                HousemaidDocument::create($hmId, $docType, $file, null, null, $doc['expiry'] ?: null);
            }
        }

        if (!empty($draft['skills'])) Housemaid::attachSkills($hmId, $draft['skills']);
        if (!empty($draft['languages'])) Housemaid::attachLanguages($hmId, $draft['languages']);
        if (!empty($draft['work_countries'])) Housemaid::attachWorkCountries($hmId, $draft['work_countries']);

        getDB()->prepare('UPDATE housemaids SET consent_given_at = NOW() WHERE id = ?')->execute([$hmId]);

        AuditLog::record('agency', $agencyId, 'housemaid.submit', 'housemaid', $hmId);

        unset($_SESSION['hm_draft']);
        flash('success', $draft['full_name'] . "'s profile was submitted and is now pending Admin review.");
        redirect(rtrim(APP_URL, '/') . '/agency/housemaid_view.php?id=' . $hmId);
    }
}

$countries = MasterData::countries();
$skills = MasterData::skills();
$languages = MasterData::languages();

$pageTitle = 'Add Housemaid — ' . $stepLabels[$step];
require __DIR__ . '/../includes/header.php';
?>
<div class="container narrow">
    <h1>Add a housemaid</h1>

    <div class="stepper">
        <?php foreach ($stepLabels as $n => $label): ?>
            <div class="step <?= $n === $step ? 'current' : ($n < $step ? 'done' : '') ?>"><?= $n ?>. <?= e($label) ?></div>
        <?php endforeach; ?>
    </div>

    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <?php if ($step === 1): ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="1">
        <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" required value="<?= e($draft['full_name'] ?? '') ?>">
        </div>
        <div class="field-row">
            <div class="field">
                <label for="date_of_birth">Date of birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($draft['date_of_birth'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="female" <?= ($draft['gender'] ?? 'female') === 'female' ? 'selected' : '' ?>>Female</option>
                    <option value="male" <?= ($draft['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                </select>
            </div>
        </div>
        <div class="field-row">
            <div class="field">
                <label for="nationality_country_id">Nationality</label>
                <select id="nationality_country_id" name="nationality_country_id" required>
                    <option value="">Select…</option>
                    <?php foreach ($countries as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string) ($draft['nationality_country_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="marital_status">Marital status</label>
                <select id="marital_status" name="marital_status">
                    <option value="">—</option>
                    <?php foreach (['single', 'married', 'divorced', 'widowed'] as $ms): ?>
                    <option value="<?= $ms ?>" <?= ($draft['marital_status'] ?? '') === $ms ? 'selected' : '' ?>><?= ucfirst($ms) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="religion">Religion <span class="muted" style="font-weight:400;">(optional)</span></label>
            <input type="text" id="religion" name="religion" value="<?= e($draft['religion'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="photo">Photo</label>
            <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png">
            <?php if (!empty($draft['photo_tmp'])): ?><p class="hint">Photo staged ✓ — upload again to replace it.</p><?php endif; ?>
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Next: Documents →</button>
        </div>
    </form>

    <?php elseif ($step === 2): ?>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="2">
        <fieldset>
            <legend>Passport</legend>
            <div class="field-row">
                <div class="field"><label>Passport number</label><input type="text" name="passport_number" value="<?= e($draft['passport_number'] ?? '') ?>"></div>
                <div class="field"><label>Expiry date</label><input type="date" name="passport_expiry" value="<?= e($draft['passport_expiry'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Passport scan</label><input type="file" name="passport_scan" accept=".pdf,.jpg,.jpeg,.png"><?php if (isset($draft['documents']['passport'])): ?><p class="hint">Staged ✓</p><?php endif; ?></div>
        </fieldset>
        <fieldset>
            <legend>Work permit / visa</legend>
            <div class="field-row">
                <div class="field"><label>Permit number</label><input type="text" name="work_permit_number" value="<?= e($draft['work_permit_number'] ?? '') ?>"></div>
                <div class="field"><label>Expiry date</label><input type="date" name="work_permit_expiry" value="<?= e($draft['work_permit_expiry'] ?? '') ?>"></div>
            </div>
            <div class="field"><label>Work permit scan</label><input type="file" name="work_permit_scan" accept=".pdf,.jpg,.jpeg,.png"><?php if (isset($draft['documents']['work_permit'])): ?><p class="hint">Staged ✓</p><?php endif; ?></div>
        </fieldset>
        <fieldset>
            <legend>Other documents</legend>
            <div class="field"><label>National ID number</label><input type="text" name="national_id_number" value="<?= e($draft['national_id_number'] ?? '') ?>"></div>
            <div class="field"><label>National ID scan</label><input type="file" name="national_id_scan" accept=".pdf,.jpg,.jpeg,.png"><?php if (isset($draft['documents']['national_id'])): ?><p class="hint">Staged ✓</p><?php endif; ?></div>
            <div class="field"><label>Police clearance</label><input type="file" name="police_clearance_scan" accept=".pdf,.jpg,.jpeg,.png"><?php if (isset($draft['documents']['police_clearance'])): ?><p class="hint">Staged ✓</p><?php endif; ?></div>
            <div class="field"><label>Medical report</label><input type="file" name="medical_report_scan" accept=".pdf,.jpg,.jpeg,.png"><?php if (isset($draft['documents']['medical_report'])): ?><p class="hint">Staged ✓</p><?php endif; ?></div>
        </fieldset>
        <div class="btn-row">
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_add.php?step=1">← Back</a>
            <button type="submit" class="btn btn-primary">Next: Address →</button>
        </div>
    </form>

    <?php elseif ($step === 3): ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="3">
        <div class="field"><label>Home address <span class="muted" style="font-weight:400;">(country of origin)</span></label><textarea name="home_address"><?= e($draft['home_address'] ?? '') ?></textarea></div>
        <div class="field-row">
            <div class="field"><label>Emergency contact name</label><input type="text" name="emergency_contact_name" value="<?= e($draft['emergency_contact_name'] ?? '') ?>"></div>
            <div class="field"><label>Emergency contact phone</label><input type="tel" name="emergency_contact_phone" value="<?= e($draft['emergency_contact_phone'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>Current staying address</label><textarea name="current_staying_address"><?= e($draft['current_staying_address'] ?? '') ?></textarea></div>
        <div class="btn-row">
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_add.php?step=2">← Back</a>
            <button type="submit" class="btn btn-primary">Next: Skills &amp; Experience →</button>
        </div>
    </form>

    <?php elseif ($step === 4): ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="4">
        <div class="field">
            <label for="years_experience">Years of experience</label>
            <input type="number" id="years_experience" name="years_experience" min="0" max="60" value="<?= e((string) ($draft['years_experience'] ?? '')) ?>">
        </div>
        <fieldset>
            <legend>Skills</legend>
            <div class="checkbox-grid">
                <?php foreach ($skills as $s): ?>
                <div class="checkbox-item">
                    <input type="checkbox" id="skill_<?= (int) $s['id'] ?>" name="skills[]" value="<?= (int) $s['id'] ?>" <?= in_array((int) $s['id'], $draft['skills'] ?? [], true) ? 'checked' : '' ?>>
                    <label for="skill_<?= (int) $s['id'] ?>"><?= e($s['name']) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset>
            <legend>Languages spoken</legend>
            <div class="checkbox-grid">
                <?php foreach ($languages as $l): ?>
                <div class="checkbox-item">
                    <input type="checkbox" id="lang_<?= (int) $l['id'] ?>" name="languages[]" value="<?= (int) $l['id'] ?>" <?= in_array((int) $l['id'], $draft['languages'] ?? [], true) ? 'checked' : '' ?>>
                    <label for="lang_<?= (int) $l['id'] ?>"><?= e($l['name']) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset>
            <legend>Previously worked in <span class="muted" style="font-weight:400;">(optional)</span></legend>
            <div class="checkbox-grid">
                <?php foreach ($countries as $c): ?>
                <div class="checkbox-item">
                    <input type="checkbox" id="wc_<?= (int) $c['id'] ?>" name="work_countries[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $draft['work_countries'] ?? [], true) ? 'checked' : '' ?>>
                    <label for="wc_<?= (int) $c['id'] ?>"><?= e($c['name']) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <fieldset>
            <legend>PDPA consent</legend>
            <div class="checkbox-item">
                <input type="checkbox" id="consent" name="consent" <?= !empty($draft['consent']) ? 'checked' : '' ?>>
                <label for="consent">I confirm this housemaid has consented to her personal data, photo, and documents being held and shown on MaidTrack, in line with Malaysia's PDPA 2010.</label>
            </div>
        </fieldset>
        <div class="btn-row">
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_add.php?step=3">← Back</a>
            <button type="submit" class="btn btn-primary">Next: Review →</button>
        </div>
    </form>

    <?php elseif ($step === 5): ?>
    <div class="card" style="margin-bottom:20px;">
        <h2>Review</h2>
        <div class="detail-grid">
            <div class="detail-item"><div class="dl">Name</div><div class="dv"><?= e($draft['full_name'] ?? '') ?></div></div>
            <div class="detail-item"><div class="dl">Nationality</div><div class="dv"><?= e(array_reduce($countries, fn($c, $x) => (string) $x['id'] === (string) ($draft['nationality_country_id'] ?? '') ? $x['name'] : $c, '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Passport</div><div class="dv mono"><?= e(mask_document_number($draft['passport_number'] ?? null)) ?></div></div>
            <div class="detail-item"><div class="dl">Years experience</div><div class="dv"><?= e((string) ($draft['years_experience'] ?? '—')) ?></div></div>
            <div class="detail-item"><div class="dl">Documents staged</div><div class="dv"><?= count($draft['documents'] ?? []) ?></div></div>
            <div class="detail-item"><div class="dl">Skills</div><div class="dv"><?= count($draft['skills'] ?? []) ?> selected</div></div>
        </div>
        <p class="hint">Use the stepper above to jump back and fix anything before submitting.</p>
    </div>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="step" value="5">
        <input type="hidden" name="confirm_submit" value="1">
        <div class="btn-row">
            <a class="btn btn-outline" href="<?= e(rtrim(APP_URL, '/')) ?>/agency/housemaid_add.php?step=4">← Back</a>
            <button type="submit" class="btn btn-primary">Submit for Admin review</button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

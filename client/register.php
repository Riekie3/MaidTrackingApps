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

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!isset($_POST['consent'])) $errors[] = 'PDPA consent is required to create an account.';

    if (!$errors && Client::findByEmail($email)) {
        $errors[] = 'An account with this email already exists.';
    }

    if (!$errors) {
        $id = Client::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'address' => $address ?: null,
        ]);
        AuditLog::record('client', $id, 'client.register', 'client', $id);
        $_SESSION['pending_client_id'] = $id;
        redirect(rtrim(APP_URL, '/') . '/client/verify.php');
    }
}

$pageTitle = 'Create a client account';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Find a housemaid</h1>
        <p class="sub">Create an account to browse verified profiles and their history.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="name">Full name</label>
                <input type="text" id="name" name="name" required value="<?= e($old['name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" required value="<?= e($old['phone'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="address">Address <span class="muted" style="font-weight:400;">(optional)</span></label>
                <input type="text" id="address" name="address" value="<?= e($old['address'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="field">
                <label for="password_confirm">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>
            <div class="checkbox-item" style="margin-bottom:16px;">
                <input type="checkbox" id="consent" name="consent" <?= isset($old['consent']) ? 'checked' : '' ?>>
                <label for="consent" style="font-weight:400;">I consent to MaidTrack collecting and using my personal data to provide this service, in line with Malaysia's PDPA 2010.</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Create account</button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="<?= e(rtrim(APP_URL, '/')) ?>/login.php">Log in</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

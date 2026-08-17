<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(dashboard_url_for(current_role()));
}

$clientId = $_SESSION['pending_client_id'] ?? null;
if (!$clientId) {
    redirect(rtrim(APP_URL, '/') . '/login.php');
}
$client = Client::findById((int) $clientId);
if (!$client || $client['status'] !== 'pending_verification') {
    unset($_SESSION['pending_client_id']);
    redirect(rtrim(APP_URL, '/') . '/login.php');
}

$errors = [];
$devCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['resend'])) {
        $devCode = ClientOtp::issue((int) $clientId);
    } else {
        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            $errors[] = 'Enter the verification code.';
        } elseif (ClientOtp::verify((int) $clientId, $code)) {
            Client::markVerified((int) $clientId);
            unset($_SESSION['pending_client_id']);
            AuditLog::record('client', (int) $clientId, 'client.verify', 'client', (int) $clientId);
            login_as('client', (int) $clientId, $client['name']);
            flash('success', 'Account verified — welcome to MaidTrack.');
            redirect(dashboard_url_for('client'));
        } else {
            $errors[] = 'That code is incorrect or has expired. Request a new one below.';
        }
    }
} else {
    // First visit to this screen after registering — issue the first code.
    $devCode = ClientOtp::issue((int) $clientId);
}

$pageTitle = 'Verify your account';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Verify your account</h1>
        <p class="sub">One code confirms both your email and phone before browsing unlocks.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <?php if ($devCode): ?>
        <div class="alert success">
            <strong>Dev mode:</strong> no email/SMS gateway is wired up yet, so here's the code that would normally be sent —
            <span class="mono" style="font-size:16px;letter-spacing:.05em;"><?= e($devCode) ?></span>
        </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="code">Verification code</label>
                <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-bottom:10px;">Verify</button>
        </form>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="btn btn-outline" style="width:100%;">Send a new code</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

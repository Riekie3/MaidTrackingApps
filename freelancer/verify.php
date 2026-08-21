<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(dashboard_url_for(current_role()));
}

$freelancerId = $_SESSION['pending_freelancer_id'] ?? null;
if (!$freelancerId) {
    redirect(rtrim(APP_URL, '/') . '/login.php');
}
$freelancer = Freelancer::findById((int) $freelancerId);
if (!$freelancer || Freelancer::isVerified($freelancer)) {
    unset($_SESSION['pending_freelancer_id']);
    redirect(rtrim(APP_URL, '/') . '/login.php');
}

$errors = [];
$devCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['resend'])) {
        $devCode = FreelancerOtp::issue((int) $freelancerId);
    } else {
        $code = trim($_POST['code'] ?? '');
        if ($code === '') {
            $errors[] = 'Enter the verification code.';
        } elseif (FreelancerOtp::verify((int) $freelancerId, $code)) {
            Freelancer::markEmailPhoneVerified((int) $freelancerId);
            unset($_SESSION['pending_freelancer_id']);
            AuditLog::record('freelancer', (int) $freelancerId, 'freelancer.verify', 'freelancer', (int) $freelancerId);
            login_as('freelancer', (int) $freelancerId, $freelancer['full_name']);
            flash('success', 'Account verified. Your application is now pending Admin review before clients can book you.');
            redirect(dashboard_url_for('freelancer'));
        } else {
            $errors[] = 'That code is incorrect or has expired. Request a new one below.';
        }
    }
} else {
    $devCode = FreelancerOtp::issue((int) $freelancerId);
}

$pageTitle = 'Verify your account';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Verify your account</h1>
        <p class="sub">One code confirms both your email and phone — required on top of Admin's document review, since there's no agency vouching for you first.</p>

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

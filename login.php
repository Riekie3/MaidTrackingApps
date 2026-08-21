<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(dashboard_url_for(current_role()));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Enter both email and password.';
    } elseif (is_login_locked_out($email)) {
        $errors[] = 'Too many failed attempts for this account. Try again in ' . LOGIN_LOCKOUT_MINUTES . ' minutes.';
    } else {
        $validCredentials = false;

        $admin = Admin::findByEmail($email);
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $validCredentials = true;
            login_as('admin', (int) $admin['id'], $admin['name']);
            redirect(dashboard_url_for('admin'));
        }

        $agency = Agency::findByEmail($email);
        if ($agency && password_verify($password, $agency['password_hash'])) {
            $validCredentials = true;
            if ($agency['approval_status'] === 'pending') {
                $errors[] = 'Your agency registration is still awaiting Admin approval. You\'ll be able to log in once it\'s approved.';
            } elseif ($agency['approval_status'] === 'rejected') {
                $errors[] = 'Your agency registration was not approved' . ($agency['rejection_reason'] ? ': ' . $agency['rejection_reason'] : '.') . ' Contact the platform admin for details.';
            } else {
                login_as('agency', (int) $agency['id'], $agency['company_name']);
                redirect(dashboard_url_for('agency'));
            }
        }

        $client = Client::findByEmail($email);
        if ($client && password_verify($password, $client['password_hash'])) {
            $validCredentials = true;
            if ($client['status'] === 'pending_verification') {
                $_SESSION['pending_client_id'] = (int) $client['id'];
                redirect(rtrim(APP_URL, '/') . '/client/verify.php');
            } elseif ($client['status'] === 'suspended') {
                $errors[] = 'This account has been suspended. Contact the platform admin for details.';
            } else {
                login_as('client', (int) $client['id'], $client['name']);
                redirect(dashboard_url_for('client'));
            }
        }

        $freelancer = Freelancer::findByEmail($email);
        if ($freelancer && password_verify($password, $freelancer['password_hash'])) {
            $validCredentials = true;
            if (!Freelancer::isVerified($freelancer)) {
                $_SESSION['pending_freelancer_id'] = (int) $freelancer['id'];
                redirect(rtrim(APP_URL, '/') . '/freelancer/verify.php');
            } else {
                login_as('freelancer', (int) $freelancer['id'], $freelancer['full_name']);
                redirect(dashboard_url_for('freelancer'));
            }
        }

        if (!$validCredentials) {
            record_failed_login($email);
            $errors[] = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="sub">Log in to MaidTrack</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Log in</button>
        </form>

        <div class="auth-links">
            Housemaid agency? <a href="<?= e(rtrim(APP_URL, '/')) ?>/agency/register.php">Register here</a><br>
            Independent housemaid? <a href="<?= e(rtrim(APP_URL, '/')) ?>/freelancer/register.php">Register as a freelancer</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>

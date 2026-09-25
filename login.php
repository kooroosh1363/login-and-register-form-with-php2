<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

$current = authenticated_user();
if ($current !== null) {
    redirect(dashboard_for_role($current['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = LoginValidator::normalize($_POST);
    $errors = LoginValidator::validate($data);

    if (!csrf_is_valid($_POST['_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    if ($errors === []) {
        $result = $authService->attempt($data['email'], $data['password'], time());

        if ($result['ok'] && is_array($result['user'])) {
            sign_in_session($result['user']);
            redirect(dashboard_for_role($result['user']['role']));
        }

        $errors['form'] = $result['reason'] === 'locked'
            ? 'Too many sign-in attempts. Please wait a few minutes.'
            : 'The email or password is incorrect.';
    }

    flash('login_errors', $errors);
    flash('login_email', $data['email']);
    redirect('/login.php');
}

$errors = pull_flash('login_errors', []);
$oldEmail = pull_flash('login_email', '');
$notice = pull_flash('notice');
$registered = pull_flash('registered');

function login_error(array $errors, string $field): ?string {
    return isset($errors[$field]) && is_string($errors[$field]) ? $errors[$field] : null;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="AccessBoundary secure PHP role-based authentication demo.">
<meta name="color-scheme" content="light dark">
<title>AccessBoundary — Sign in</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="skip-link" href="#login-form">Skip to sign in</a>
<main class="auth-shell">
<section class="story-panel">
<a class="brand" href="/"><span class="brand-mark">AB</span><span>AccessBoundary</span></a>
<div class="story-copy">
<p class="eyebrow">Role-based access control / PHP + PDO</p>
<h1>Authentication answers who you are. Authorization decides where you may go.</h1>
<p>This demo separates public registration from privileged administration. New public accounts are always regular users; administrator access is provisioned only through a trusted CLI path.</p>
</div>
<div class="boundary-grid">
<article><span>Public</span><strong>Register as user</strong><p>No role selector is exposed to the browser.</p></article>
<article><span>Trusted</span><strong>Create admin via CLI</strong><p>Privileged accounts come from an operator-controlled path.</p></article>
<article><span>Runtime</span><strong>Enforce role guards</strong><p>Admin and user routes verify authorization server-side.</p></article>
</div>
</section>

<section class="form-panel">
<div class="form-card">
<p class="section-index">Access / 01</p>
<h2>Sign in</h2>
<p class="form-intro">Your destination is selected from the role stored in the database, never from form input.</p>

<?php if (is_string($registered) && $registered !== ''): ?><div class="notice notice--success" role="status"><?= e($registered) ?></div><?php endif; ?>
<?php if (is_string($notice) && $notice !== ''): ?><div class="notice" role="status"><?= e($notice) ?></div><?php endif; ?>
<?php if (($errors['form'] ?? null) !== null): ?><div class="notice notice--error" role="alert"><?= e((string)$errors['form']) ?></div><?php endif; ?>

<form id="login-form" method="post" action="/login.php" novalidate>
<input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
<div class="field">
<label for="email">Email</label>
<input id="email" name="email" type="email" autocomplete="username" maxlength="254" required value="<?= e(is_string($oldEmail)?$oldEmail:'') ?>" <?= login_error($errors,'email') ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
<?php if ($error=login_error($errors,'email')): ?><p id="email-error" class="field-error"><?= e($error) ?></p><?php endif; ?>
</div>
<div class="field">
<label for="password">Password</label>
<input id="password" name="password" type="password" autocomplete="current-password" maxlength="4096" required <?= login_error($errors,'password') ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>>
<?php if ($error=login_error($errors,'password')): ?><p id="password-error" class="field-error"><?= e($error) ?></p><?php endif; ?>
</div>
<button class="primary-button" type="submit">Sign in →</button>
</form>

<p class="switch-link">Need a regular account? <a href="/register.php">Register</a></p>
</div>
</section>
</main>
</body>
</html>
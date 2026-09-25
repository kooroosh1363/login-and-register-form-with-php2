<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

$current = authenticated_user();
if ($current !== null) {
    redirect(dashboard_for_role($current['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = RegistrationValidator::normalize($_POST);
    $errors = RegistrationValidator::validate($data);

    if (!csrf_is_valid($_POST['_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    if ($errors === []) {
        $result = $registrationService->register($data);

        if ($result['ok']) {
            flash('registered', 'Account created with the user role. You can sign in now.');
            redirect('/login.php');
        }

        $errors['form'] = $result['error'] ?? 'Unable to create the account.';
    }

    $safeOld = ['name' => $data['name'], 'email' => $data['email']];
    flash('register_errors', $errors);
    flash('register_old', $safeOld);
    redirect('/register.php');
}

$errors = pull_flash('register_errors', []);
$old = pull_flash('register_old', []);

function reg_value(array $old, string $field): string {
    return isset($old[$field]) && is_string($old[$field]) ? $old[$field] : '';
}
function reg_error(array $errors, string $field): ?string {
    return isset($errors[$field]) && is_string($errors[$field]) ? $errors[$field] : null;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Register a regular user account in the AccessBoundary RBAC demo.">
<meta name="color-scheme" content="light dark">
<title>Register — AccessBoundary</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="skip-link" href="#register-form">Skip to registration</a>
<main class="register-shell">
<section class="register-copy">
<a class="brand" href="/"><span class="brand-mark">AB</span><span>AccessBoundary</span></a>
<p class="eyebrow">Public registration / least privilege</p>
<h1>Public signup never grants administrator access.</h1>
<p>Every account created here receives the <strong>user</strong> role. Admin creation is intentionally absent from the web form and available only through the trusted CLI provisioning command.</p>
<div class="role-note"><span>User role</span><p>Can access the user dashboard only.</p></div>
</section>

<section class="register-card">
<p class="section-index">Account / 02</p>
<h2>Create user</h2>
<p class="form-intro">Passwords must be at least 12 characters.</p>
<?php if (($errors['form'] ?? null) !== null): ?><div class="notice notice--error" role="alert"><?= e((string)$errors['form']) ?></div><?php endif; ?>

<form id="register-form" method="post" action="/register.php" novalidate>
<input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
<div class="field"><label for="name">Name</label><input id="name" name="name" autocomplete="name" maxlength="80" required value="<?= e(reg_value($old,'name')) ?>" <?= reg_error($errors,'name') ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>><?php if($e=reg_error($errors,'name')):?><p id="name-error" class="field-error"><?=e($e)?></p><?php endif;?></div>
<div class="field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?= e(reg_value($old,'email')) ?>" <?= reg_error($errors,'email') ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>><?php if($e=reg_error($errors,'email')):?><p id="email-error" class="field-error"><?=e($e)?></p><?php endif;?></div>
<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="128" required <?= reg_error($errors,'password') ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>><?php if($e=reg_error($errors,'password')):?><p id="password-error" class="field-error"><?=e($e)?></p><?php endif;?></div>
<div class="field"><label for="password_confirm">Confirm password</label><input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="12" maxlength="128" required <?= reg_error($errors,'password_confirm') ? 'aria-invalid="true" aria-describedby="password-confirm-error"' : '' ?>><?php if($e=reg_error($errors,'password_confirm')):?><p id="password-confirm-error" class="field-error"><?=e($e)?></p><?php endif;?></div>
<button class="primary-button" type="submit">Create regular user →</button>
</form>

<p class="switch-link">Already registered? <a href="/login.php">Sign in</a></p>
</section>
</main>
</body>
</html>
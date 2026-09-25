<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

$sessionUser = require_role(Roles::ADMIN);
$account = $userRepository->findById($sessionUser['id']);
if ($account === null || !Authorization::canAccessAdmin($account)) {
    http_response_code(403);
    exit('Forbidden');
}
$users = $userRepository->listUsers();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Protected administrator dashboard for AccessBoundary RBAC demo.">
<meta name="color-scheme" content="light dark">
<title>Admin — AccessBoundary</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="dashboard-header">
<a class="brand" href="/admin.php"><span class="brand-mark">AB</span><span>AccessBoundary</span></a>
<div class="header-actions"><span class="role-badge role-badge--admin">admin</span><form method="post" action="/logout.php"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><button class="secondary-button" type="submit">Sign out</button></form></div>
</header>
<main class="dashboard-shell">
<p class="eyebrow">Protected route / administrator only</p>
<h1>Admin boundary verified.</h1>
<p class="dashboard-lead">Welcome, <strong><?= e($account['name']) ?></strong>. This page is authorized by the role stored server-side in the database.</p>

<section class="admin-panel" aria-labelledby="accounts-title">
<div class="section-heading"><div><p class="section-index">Directory / read-only</p><h2 id="accounts-title">Account directory</h2></div><p><?= count($users) ?> account<?= count($users)===1?'':'s' ?> currently provisioned.</p></div>
<div class="table-wrap">
<table>
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
<tbody>
<?php foreach ($users as $user): ?>
<tr><td><?= $user['id'] ?></td><td><?= e($user['name']) ?></td><td><?= e($user['email']) ?></td><td><span class="role-badge role-badge--<?= e($user['role']) ?>"><?= e($user['role']) ?></span></td><td><?= e($user['created_at']) ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>
</main>
</body>
</html>
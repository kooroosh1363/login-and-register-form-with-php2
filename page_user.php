<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

$sessionUser = require_role(Roles::USER);
$account = $userRepository->findById($sessionUser['id']);
if ($account === null || !Authorization::canAccessUserArea($account)) {
    http_response_code(403);
    exit('Forbidden');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Protected regular-user dashboard for AccessBoundary RBAC demo.">
<meta name="color-scheme" content="light dark">
<title>User Dashboard — AccessBoundary</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="dashboard-header">
<a class="brand" href="/page_user.php"><span class="brand-mark">AB</span><span>AccessBoundary</span></a>
<div class="header-actions"><span class="role-badge role-badge--user">user</span><form method="post" action="/logout.php"><input type="hidden" name="_token" value="<?= e(csrf_token()) ?>"><button class="secondary-button" type="submit">Sign out</button></form></div>
</header>
<main class="dashboard-shell">
<p class="eyebrow">Protected route / regular user</p>
<h1>Welcome, <?= e($account['name']) ?>.</h1>
<p class="dashboard-lead">Your session carries the <strong>user</strong> role. That role authorizes this page and does not authorize the administrator route.</p>
<section class="user-cards">
<article><span>Identity</span><strong><?= e($account['email']) ?></strong><p>Account identity comes from the database.</p></article>
<article><span>Role</span><strong><?= e($account['role']) ?></strong><p>Public registration cannot choose or elevate this value.</p></article>
<article><span>Boundary</span><strong>Least privilege</strong><p>Admin access requires a separately provisioned administrator account.</p></article>
</section>
</main>
</body>
</html>
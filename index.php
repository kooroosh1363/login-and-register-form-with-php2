<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

$user = authenticated_user();
if ($user !== null) {
    redirect(dashboard_for_role($user['role']));
}
redirect('/login.php');

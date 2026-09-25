<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Config.php';
require_once dirname(__DIR__) . '/src/Database.php';
require_once dirname(__DIR__) . '/src/Roles.php';
require_once dirname(__DIR__) . '/src/RegistrationValidator.php';
require_once dirname(__DIR__) . '/src/LoginValidator.php';
require_once dirname(__DIR__) . '/src/Authorization.php';
require_once dirname(__DIR__) . '/src/UserRepository.php';
require_once dirname(__DIR__) . '/src/RegistrationService.php';
require_once dirname(__DIR__) . '/src/AuthService.php';

$tests = [];

function test(string $name, callable $callback): void { global $tests; $tests[] = [$name, $callback]; }
function expect_true(bool $condition, string $message='Expected true.'): void { if (!$condition) throw new RuntimeException($message); }
function expect_same(mixed $expected, mixed $actual): void {
    if ($expected !== $actual) throw new RuntimeException(sprintf('Expected %s, got %s.', var_export($expected,true), var_export($actual,true)));
}

function repo_for(string $dsn, ?string $user=null, ?string $password=null): UserRepository {
    $pdo = Database::connect($dsn, $user, $password);
    $repo = new UserRepository($pdo);
    $repo->migrate();
    return $repo;
}

function exercise_rbac_flow(UserRepository $repo): void {
    $registration = new RegistrationService($repo);

    $payload = RegistrationValidator::normalize([
        'name' => 'Regular User',
        'email' => 'user+' . bin2hex(random_bytes(3)) . '@example.com',
        'password' => 'correct-horse-battery-staple',
        'password_confirm' => 'correct-horse-battery-staple',
        'userType' => 'admin',
        'role' => 'admin',
    ]);

    $created = $registration->register($payload);
    expect_same(true, $created['ok']);
    expect_same(Roles::USER, $created['user']['role']);

    $stored = $repo->findByEmail($payload['email']);
    expect_true(is_array($stored));
    expect_same(Roles::USER, $stored['role']);
    expect_true(!Authorization::canAccessAdmin($stored));
    expect_true(Authorization::canAccessUserArea($stored));

    $adminEmail = 'admin+' . bin2hex(random_bytes(3)) . '@example.com';
    $adminId = $repo->createPrivilegedUser(
        'Admin User',
        $adminEmail,
        password_hash('correct-horse-battery-staple', PASSWORD_DEFAULT),
        Roles::ADMIN,
    );

    $admin = $repo->findById($adminId);
    expect_true(is_array($admin));
    expect_true(Authorization::canAccessAdmin($admin));
    expect_true(!Authorization::canAccessUserArea($admin));

    $auth = new AuthService($repo);
    $userLogin = $auth->attempt($payload['email'], 'correct-horse-battery-staple', 1000);
    expect_same(true, $userLogin['ok']);
    expect_same(Roles::USER, $userLogin['user']['role']);

    $adminLogin = $auth->attempt($adminEmail, 'correct-horse-battery-staple', 1001);
    expect_same(true, $adminLogin['ok']);
    expect_same(Roles::ADMIN, $adminLogin['user']['role']);
}

test('registration validation rejects weak input', function (): void {
    $errors = RegistrationValidator::validate(RegistrationValidator::normalize([
        'name' => 'A',
        'email' => 'bad',
        'password' => 'short',
        'password_confirm' => 'different',
    ]));
    foreach (['name','email','password','password_confirm'] as $field) expect_true(isset($errors[$field]));
});

test('public registration ignores attempted role escalation on SQLite', function (): void {
    expect_true(in_array('sqlite', PDO::getAvailableDrivers(), true));
    exercise_rbac_flow(repo_for('sqlite::memory:'));
});

test('invalid privileged roles are rejected', function (): void {
    $repo = repo_for('sqlite::memory:');
    $thrown = false;
    try {
        $repo->createPrivilegedUser(
            'Bad Role',
            'bad-role@example.com',
            password_hash('correct-horse-battery-staple', PASSWORD_DEFAULT),
            'superadmin',
        );
    } catch (InvalidArgumentException) {
        $thrown = true;
    }
    expect_true($thrown);
});

test('login throttling locks repeated failures', function (): void {
    $repo = repo_for('sqlite::memory:');
    $id = $repo->createPublicUser(
        'Locked User',
        'locked@example.com',
        password_hash('correct-horse-battery-staple', PASSWORD_DEFAULT),
    );
    expect_true($id > 0);

    $auth = new AuthService($repo);
    for ($i=1; $i<=5; $i++) $result = $auth->attempt('locked@example.com','wrong',2000+$i);

    expect_same('locked', $result['reason']);
    expect_same(false, $auth->attempt('locked@example.com','correct-horse-battery-staple',2100)['ok']);
    expect_same(true, $auth->attempt('locked@example.com','correct-horse-battery-staple',2400)['ok']);
});

$mysqlDsn = getenv('TEST_MYSQL_DSN');
if (is_string($mysqlDsn) && $mysqlDsn !== '') {
    test('RBAC and privilege-escalation protections hold on MySQL', function () use ($mysqlDsn): void {
        expect_true(in_array('mysql', PDO::getAvailableDrivers(), true));
        exercise_rbac_flow(repo_for(
            $mysqlDsn,
            getenv('TEST_MYSQL_USER') ?: null,
            getenv('TEST_MYSQL_PASSWORD') ?: null,
        ));
    });
}

$failures = 0;
foreach ($tests as [$name,$callback]) {
    try { $callback(); fwrite(STDOUT, "[pass] {$name}\n"); }
    catch (Throwable $error) { $failures++; fwrite(STDERR, "[fail] {$name}: {$error->getMessage()}\n"); }
}
fwrite(STDOUT, sprintf("\n%d test(s), %d failure(s).\n", count($tests), $failures));
exit($failures === 0 ? 0 : 1);

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only run from the CLI.\n");
    exit(1);
}

$name = trim((string) ($argv[1] ?? ''));
$email = trim((string) ($argv[2] ?? ''));
$password = (string) ($argv[3] ?? '');

$data = RegistrationValidator::normalize([
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'password_confirm' => $password,
]);
$errors = RegistrationValidator::validate($data);

if ($errors !== []) {
    foreach ($errors as $message) {
        fwrite(STDERR, $message . "\n");
    }
    exit(1);
}

if ($userRepository->emailExists($data['email'])) {
    fwrite(STDERR, "An account with that email already exists.\n");
    exit(1);
}

try {
    $id = $userRepository->createPrivilegedUser(
        $data['name'],
        $data['email'],
        password_hash($data['password'], PASSWORD_DEFAULT),
        Roles::ADMIN,
    );

    fwrite(STDOUT, sprintf(
        "Created admin #%d for %s.\n",
        $id,
        $data['email'],
    ));
} catch (PDOException $error) {
    fwrite(STDERR, "Unable to create admin account.\n");
    exit(1);
}

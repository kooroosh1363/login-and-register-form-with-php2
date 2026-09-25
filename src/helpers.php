<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_is_valid(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $key, mixed $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function pull_flash(string $key, mixed $default = null): mixed
{
    $value = $_SESSION['_flash'][$key] ?? $default;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

/** @param array{id:int,name:string,email:string,role:string} $user */
function sign_in_session(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['auth'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regeneration'] = time();
    unset($_SESSION['csrf_token']);
}

function sign_out_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly'],
        );
    }

    session_destroy();
}

/** @return array{id:int,name:string,email:string,role:string}|null */
function authenticated_user(): ?array
{
    $auth = $_SESSION['auth'] ?? null;

    if (
        !is_array($auth)
        || !isset($auth['id'], $auth['name'], $auth['email'], $auth['role'])
        || !Roles::isValid((string) $auth['role'])
    ) {
        return null;
    }

    return [
        'id' => (int) $auth['id'],
        'name' => (string) $auth['name'],
        'email' => (string) $auth['email'],
        'role' => (string) $auth['role'],
    ];
}

/** @return array{id:int,name:string,email:string,role:string} */
function require_role(string $role): array
{
    $user = authenticated_user();

    if ($user === null) {
        flash('notice', 'Please sign in to continue.');
        redirect('/login.php');
    }

    if (!Authorization::hasRole($user, $role)) {
        http_response_code(403);
        exit('Forbidden');
    }

    return $user;
}

function dashboard_for_role(string $role): string
{
    return $role === Roles::ADMIN ? '/admin.php' : '/page_user.php';
}

function refresh_authenticated_session(int $now): void
{
    if (authenticated_user() === null) return;

    $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);

    if (($now - $lastActivity) > Config::idleTimeoutSeconds()) {
        unset(
            $_SESSION['auth'],
            $_SESSION['last_activity'],
            $_SESSION['last_regeneration'],
            $_SESSION['csrf_token'],
        );

        session_regenerate_id(true);
        flash('notice', 'Your session expired. Please sign in again.');
        return;
    }

    $lastRegeneration = (int) ($_SESSION['last_regeneration'] ?? $now);

    if (($now - $lastRegeneration) > Config::sessionRegenerationSeconds()) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = $now;
    }

    $_SESSION['last_activity'] = $now;
}

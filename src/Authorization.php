<?php

declare(strict_types=1);

final class Authorization
{
    /** @param array{role:string}|null $user */
    public static function hasRole(?array $user, string $role): bool
    {
        return $user !== null
            && Roles::isValid($role)
            && ($user['role'] ?? null) === $role;
    }

    /** @param array{role:string}|null $user */
    public static function canAccessAdmin(?array $user): bool
    {
        return self::hasRole($user, Roles::ADMIN);
    }

    /** @param array{role:string}|null $user */
    public static function canAccessUserArea(?array $user): bool
    {
        return self::hasRole($user, Roles::USER);
    }
}

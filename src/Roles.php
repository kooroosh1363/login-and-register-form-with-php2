<?php

declare(strict_types=1);

final class Roles
{
    public const USER = 'user';
    public const ADMIN = 'admin';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::USER, self::ADMIN];
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}

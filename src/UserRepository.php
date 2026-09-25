<?php

declare(strict_types=1);

final class UserRepository
{
    private string $driver;

    public function __construct(private PDO $pdo)
    {
        $this->driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function migrate(): void
    {
        if ($this->driver === 'mysql') {
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS users (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(80) NOT NULL,
                    email VARCHAR(254) NOT NULL,
                    normalized_email VARCHAR(254) NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    role ENUM('user','admin') NOT NULL DEFAULT 'user',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY users_normalized_email_unique (normalized_email),
                    KEY users_role_index (role)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS login_attempts (
                    identifier_hash CHAR(64) NOT NULL,
                    failures INT UNSIGNED NOT NULL,
                    first_failed_at BIGINT UNSIGNED NOT NULL,
                    locked_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (identifier_hash)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );

            return;
        }

        if ($this->driver !== 'sqlite') {
            throw new RuntimeException('Unsupported database driver: ' . $this->driver);
        }

        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                normalized_email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user','admin')),
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                identifier_hash TEXT PRIMARY KEY,
                failures INTEGER NOT NULL,
                first_failed_at INTEGER NOT NULL,
                locked_until INTEGER NOT NULL DEFAULT 0
            )'
        );
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE normalized_email = :normalized_email LIMIT 1'
        );
        $statement->execute([':normalized_email' => self::normalizeEmail($email)]);

        return $statement->fetchColumn() !== false;
    }

    public function createPublicUser(
        string $name,
        string $email,
        string $passwordHash,
    ): int {
        return $this->createUserWithRole(
            $name,
            $email,
            $passwordHash,
            Roles::USER,
        );
    }

    public function createPrivilegedUser(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
    ): int {
        if (!Roles::isValid($role)) {
            throw new InvalidArgumentException('Unknown role.');
        }

        return $this->createUserWithRole($name, $email, $passwordHash, $role);
    }

    /** @return array{id:int,name:string,email:string,password_hash:string,role:string,created_at:string}|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at
             FROM users
             WHERE normalized_email = :normalized_email
             LIMIT 1'
        );
        $statement->execute([':normalized_email' => self::normalizeEmail($email)]);

        $row = $statement->fetch();
        return is_array($row) ? self::mapUser($row) : null;
    }

    /** @return array{id:int,name:string,email:string,password_hash:string,role:string,created_at:string}|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute([':id' => $id]);

        $row = $statement->fetch();
        return is_array($row) ? self::mapUser($row) : null;
    }

    /** @return list<array{id:int,name:string,email:string,role:string,created_at:string}> */
    public function listUsers(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY id ASC'
        );

        $users = [];
        foreach ($statement->fetchAll() as $row) {
            $users[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'role' => (string) $row['role'],
                'created_at' => (string) $row['created_at'],
            ];
        }

        return $users;
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $statement->execute([
            ':password_hash' => $passwordHash,
            ':id' => $userId,
        ]);
    }

    /** @return array{failures:int,first_failed_at:int,locked_until:int} */
    public function attemptState(string $identifierHash): array
    {
        $statement = $this->pdo->prepare(
            'SELECT failures, first_failed_at, locked_until
             FROM login_attempts
             WHERE identifier_hash = :identifier_hash
             LIMIT 1'
        );
        $statement->execute([':identifier_hash' => $identifierHash]);

        $row = $statement->fetch();
        if (!is_array($row)) {
            return ['failures' => 0, 'first_failed_at' => 0, 'locked_until' => 0];
        }

        return [
            'failures' => (int) $row['failures'],
            'first_failed_at' => (int) $row['first_failed_at'],
            'locked_until' => (int) $row['locked_until'],
        ];
    }

    public function recordFailure(
        string $identifierHash,
        int $now,
        int $maxAttempts,
        int $windowSeconds,
        int $lockSeconds,
    ): int {
        $state = $this->attemptState($identifierHash);

        if ($state['first_failed_at'] === 0 || ($now - $state['first_failed_at']) >= $windowSeconds) {
            $failures = 1;
            $firstFailedAt = $now;
        } else {
            $failures = $state['failures'] + 1;
            $firstFailedAt = $state['first_failed_at'];
        }

        $lockedUntil = $failures >= $maxAttempts ? $now + $lockSeconds : 0;

        if ($state['first_failed_at'] === 0) {
            $statement = $this->pdo->prepare(
                'INSERT INTO login_attempts
                    (identifier_hash, failures, first_failed_at, locked_until)
                 VALUES
                    (:identifier_hash, :failures, :first_failed_at, :locked_until)'
            );
        } else {
            $statement = $this->pdo->prepare(
                'UPDATE login_attempts
                 SET failures = :failures,
                     first_failed_at = :first_failed_at,
                     locked_until = :locked_until
                 WHERE identifier_hash = :identifier_hash'
            );
        }

        $statement->execute([
            ':identifier_hash' => $identifierHash,
            ':failures' => $failures,
            ':first_failed_at' => $firstFailedAt,
            ':locked_until' => $lockedUntil,
        ]);

        return $lockedUntil;
    }

    public function clearFailures(string $identifierHash): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM login_attempts WHERE identifier_hash = :identifier_hash'
        );
        $statement->execute([':identifier_hash' => $identifierHash]);
    }

    private function createUserWithRole(
        string $name,
        string $email,
        string $passwordHash,
        string $role,
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO users
                (name, email, normalized_email, password_hash, role)
             VALUES
                (:name, :email, :normalized_email, :password_hash, :role)'
        );

        $statement->execute([
            ':name' => trim($name),
            ':email' => trim($email),
            ':normalized_email' => self::normalizeEmail($email),
            ':password_hash' => $passwordHash,
            ':role' => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /** @param array<string,mixed> $row
     *  @return array{id:int,name:string,email:string,password_hash:string,role:string,created_at:string}
     */
    private static function mapUser(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'password_hash' => (string) $row['password_hash'],
            'role' => (string) $row['role'],
            'created_at' => (string) $row['created_at'],
        ];
    }
}

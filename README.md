# AccessBoundary — Secure PHP RBAC Authentication

[![Quality](https://github.com/kooroosh1363/login-and-register-form-with-php2/actions/workflows/quality.yml/badge.svg)](https://github.com/kooroosh1363/login-and-register-form-with-php2/actions/workflows/quality.yml)

AccessBoundary modernizes the original 2023 PHP/MySQL login/register project into a role-based access control demo focused on **authorization boundaries**.

## Why this repository is different

The earlier version allowed anyone registering through the public form to choose `admin` as their own role.

That is a direct privilege-escalation flaw.

The current design separates trusted and untrusted role assignment:

```text
Public web registration -> always USER
Trusted CLI provisioning -> ADMIN
Runtime route guard       -> role checked server-side
```

## Security controls

- public registration cannot choose a role
- regular accounts are always created with `user`
- administrator accounts are created only via CLI
- admin and user pages enforce explicit server-side role guards
- PDO prepared statements
- password hashing and verification
- automatic password rehashing
- login throttling
- CSRF protection
- strict sessions and session rotation
- inactivity timeout
- POST-only logout
- security headers
- environment-based database configuration

## Local setup

SQLite is the zero-configuration default:

```bash
php bin/migrate.php
php bin/create-admin.php "Admin User" admin@example.com "correct-horse-battery-staple"
php -S localhost:8000
```

Then:

- create a normal account at `/register.php`
- sign in at `/login.php`
- admin accounts land on `/admin.php`
- regular users land on `/page_user.php`

## Database support

The same RBAC model supports:

- SQLite for local development
- MySQL for the original project stack

GitHub Actions starts a real MySQL 8 service and runs the same authorization tests against both engines.

## Privilege-escalation protection

Tests deliberately submit fields such as:

```text
userType=admin
role=admin
```

during public registration.

Those fields are ignored by the registration contract, and the resulting account is asserted to have the `user` role.

## Architecture

```text
register.php
   │
   ▼
RegistrationService
   │
   └── createPublicUser() -> role=user

bin/create-admin.php
   │
   └── createPrivilegedUser() -> role=admin

login.php
   │
   ▼
AuthService
   │
   ▼
session auth state
   │
   ├── require_role(user)  -> page_user.php
   └── require_role(admin) -> admin.php
```

## Tests

```bash
php tests/run.php
```

Coverage includes:

- registration validation
- attempted public role escalation
- invalid privileged role rejection
- user/admin authorization decisions
- login role preservation
- login throttling
- SQLite RBAC flow
- MySQL RBAC flow

## Scope

This is an RBAC/authentication engineering demo, not a complete IAM system. MFA, password reset, email verification, OAuth/OIDC, audit retention, permission matrices, and distributed rate limits remain out of scope.

## Deployment

GitHub Pages cannot run PHP. Deploy to a PHP-capable host and provide database credentials through environment configuration.

## License

No license is currently included.

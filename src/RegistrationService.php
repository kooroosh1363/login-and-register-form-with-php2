<?php

declare(strict_types=1);

final class RegistrationService
{
    public function __construct(private UserRepository $users) {}

    /** @param array{name:string,email:string,password:string,password_confirm:string} $data
     *  @return array{ok:bool,error:?string,user:?array{id:int,name:string,email:string,role:string}}
     */
    public function register(array $data): array
    {
        if ($this->users->emailExists($data['email'])) {
            return [
                'ok' => false,
                'error' => 'An account with that email already exists.',
                'user' => null,
            ];
        }

        try {
            $id = $this->users->createPublicUser(
                $data['name'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
            );
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000'
                || str_contains($error->getMessage(), 'UNIQUE constraint failed')
                || str_contains($error->getMessage(), 'Duplicate entry')) {
                return [
                    'ok' => false,
                    'error' => 'An account with that email already exists.',
                    'user' => null,
                ];
            }

            throw $error;
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new RuntimeException('Created account could not be reloaded.');
        }

        return [
            'ok' => true,
            'error' => null,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ];
    }
}

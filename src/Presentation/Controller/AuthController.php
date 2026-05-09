<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\LoginUser;
use App\Application\UseCase\RegisterUser;
use DomainException;
use Throwable;

final class AuthController
{
    public function __construct(
        private readonly RegisterUser $registerUser,
        private readonly LoginUser $loginUser
    ) {
    }

    /**
     * @return array{status:int,body:array<string,mixed>}
     */
    public function handle(array $payload): array
    {
        $mode = ($payload['mode'] ?? '') === 'register' ? 'register' : 'login';
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            return [
                'status' => 422,
                'body' => ['message' => 'Informe usuário e senha.'],
            ];
        }

        try {
            if ($mode === 'register') {
                $user = $this->registerUser->execute($username, $password);
                $_SESSION['auth_user'] = $user->username();
                $_SESSION['auth_user_id'] = $user->id();

                return [
                    'status' => 201,
                    'body' => [
                        'message' => 'Conta criada com sucesso.',
                        'user' => $user->username(),
                        'created_at' => $user->createdAt(),
                    ],
                ];
            }

            $user = $this->loginUser->execute($username, $password);
            $_SESSION['auth_user'] = $user->username();
                $_SESSION['auth_user_id'] = $user->id();

            return [
                'status' => 200,
                'body' => ['message' => 'Login realizado com sucesso.', 'user' => $user->username()],
            ];
        } catch (DomainException $exception) {
            return [
                'status' => 422,
                'body' => ['message' => $exception->getMessage()],
            ];
        } catch (Throwable) {
            return [
                'status' => 500,
                'body' => ['message' => 'Erro interno ao processar autenticação.'],
            ];
        }
    }
}

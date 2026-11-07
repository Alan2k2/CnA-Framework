<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use CnA\Auth\Hasher;
use CnA\Contracts\Exceptions\AuthenticationException;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\JsonResponse;
use CnA\Validation\Validator;

class AuthController
{
    public function __construct(private Hasher $hasher)
    {
    }

    public function login(RequestInterface $request): ResponseInterface
    {
        $validated = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ])->validate();

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !$this->hasher->check($validated['password'], $user['password'])) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        User::query()->where('id', $user['id'])->update(['api_token' => $hashedToken]);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ],
        ]);
    }
}


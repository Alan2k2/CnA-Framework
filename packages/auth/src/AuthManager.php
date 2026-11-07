<?php

declare(strict_types=1);

namespace CnA\Auth;

use CnA\Contracts\Exceptions\AuthenticationException;
use CnA\Contracts\Http\RequestInterface;

class AuthManager
{
    protected ?object $user = null;

    public function __construct(
        protected Hasher $hasher,
        protected ?string $userModel = null
    ) {
    }

    public function setUser(?object $user): void
    {
        $this->user = $user;
    }

    public function user(): ?object
    {
        return $this->user;
    }

    public function id(): int|string|null
    {
        return $this->user?->id ?? null;
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function authenticate(RequestInterface $request): object
    {
        $token = $request->bearerToken();
        if ($token === null && $this->user === null) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->userModel !== null && method_exists($this->userModel, 'where')) {
            $user = $this->userModel::where('api_token', hash('sha256', $token))->first();
            if ($user !== null) {
                $this->user = $user;
                return $user;
            }
        }

        throw new AuthenticationException('Invalid bearer token.');
    }

    public function attempt(string $email, string $password): bool
    {
        if ($this->userModel === null || !method_exists($this->userModel, 'where')) {
            return false;
        }

        $user = $this->userModel::where('email', $email)->first();
        if ($user === null) {
            return false;
        }

        if ($this->hasher->check($password, (string) $user->password)) {
            $this->setUser($user);
            return true;
        }

        return false;
    }
}


<?php

declare(strict_types=1);

namespace CnA\Contracts\Core;

use CnA\Contracts\Container\ContainerInterface;

interface ApplicationInterface extends ContainerInterface
{
    public function version(): string;

    public function basePath(string $path = ''): string;

    public function configPath(string $path = ''): string;

    public function storagePath(string $path = ''): string;

    public function environment(): string;

    public function isProduction(): bool;

    public function isLocal(): bool;

    public function register(ServiceProviderInterface|string $provider): ServiceProviderInterface;

    public function bootstrap(): void;

    public function isBooted(): bool;

    public function terminate(): void;
}


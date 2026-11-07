<?php

declare(strict_types=1);

namespace CnA\Contracts\Core;

interface ServiceProviderInterface
{
    public function register(): void;

    public function boot(): void;
}


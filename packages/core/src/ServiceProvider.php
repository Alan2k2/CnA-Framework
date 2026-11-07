<?php

declare(strict_types=1);

namespace CnA\Core;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Core\ServiceProviderInterface;

abstract class ServiceProvider implements ServiceProviderInterface
{
    public function __construct(protected ApplicationInterface $app)
    {
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}


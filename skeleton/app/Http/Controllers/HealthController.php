<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\JsonResponse;

class HealthController
{
    public function __construct(private ApplicationInterface $app)
    {
    }

    public function check(RequestInterface $request): ResponseInterface
    {
        // Minimal information disclosure in production health checks
        return new JsonResponse([
            'status' => 'healthy',
            'timestamp' => time(),
        ]);
    }
}


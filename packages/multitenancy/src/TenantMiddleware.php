<?php

declare(strict_types=1);

namespace CnA\Tenancy;

use CnA\Contracts\Http\MiddlewareInterface;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Contracts\Tenancy\TenantManagerInterface;

class TenantMiddleware implements MiddlewareInterface
{
    public function __construct(protected TenantManagerInterface $manager)
    {
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        // 1. Check X-Tenant-ID header
        $headerId = $request->header('x-tenant-id');
        if ($headerId !== null && $headerId !== '') {
            $tenant = $this->manager->resolve($headerId);
            if ($tenant !== null) {
                $this->manager->setTenant($tenant);
            }
        } else {
            // 2. Check Host header
            $host = $request->header('host');
            if ($host !== null && $host !== '') {
                $domain = explode(':', $host)[0];
                $tenant = $this->manager->resolve($domain);
                if ($tenant !== null) {
                    $this->manager->setTenant($tenant);
                }
            }
        }

        try {
            return $next($request);
        } finally {
            // Clear scoped tenant context after response completes
            $this->manager->clear();
        }
    }
}

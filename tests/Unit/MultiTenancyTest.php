<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\Request;
use CnA\Http\Response;
use CnA\Tenancy\Tenant;
use CnA\Tenancy\TenantManager;
use CnA\Tenancy\TenantMiddleware;
use CnA\Testing\TestCase;

class MultiTenancyTest extends TestCase
{
    public function testTenantManagerLifecycle(): void
    {
        $manager = new TenantManager();

        $this->assertNull($manager->current());
        $this->assertFalse($manager->has('acme'));

        $tenant = new Tenant('acme', 'Acme Corp', ['db' => 'acme_db', 'plan' => 'enterprise']);
        $manager->register($tenant);

        $this->assertTrue($manager->has('acme'));
        $this->assertEquals('Acme Corp', $manager->get('acme')?->getName());
        $this->assertEquals('enterprise', $manager->get('acme')?->get('plan'));

        $manager->setCurrent($tenant);
        $this->assertEquals('acme', $manager->current()?->getId());

        $manager->clear();
        $this->assertNull($manager->current());
    }

    public function testTenantMiddlewareResolution(): void
    {
        $manager = new TenantManager();
        $tenant = new Tenant('globex', 'Globex Industries');
        $manager->register($tenant);

        $middleware = new TenantMiddleware($manager);

        $request = new Request('GET', '/api/users', ['X-Tenant-ID' => 'globex']);

        $nextCalled = false;
        $tenantDuringExecution = null;

        $next = function (RequestInterface $req) use (&$nextCalled, &$tenantDuringExecution, $manager) {
            $nextCalled = true;
            $tenantDuringExecution = $manager->current()?->getId();
            return new Response('OK');
        };

        $response = $middleware->process($request, $next);

        $this->assertTrue($nextCalled);
        $this->assertEquals('globex', $tenantDuringExecution);
        $this->assertNull($manager->current(), 'Tenant context must be cleared after request execution');
        $this->assertEquals(200, $response->getStatusCode());
    }
}

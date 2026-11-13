<?php

declare(strict_types=1);

namespace Tests\Feature;

use CnA\Testing\TestCase;

class AdminStudioTest extends TestCase
{
    public function testAdminDashboardRouteReturnsOkHtml(): void
    {
        $response = $this->get('/admin');

        $response->assertOk();
        $response->assertSee('Admin Studio & Observability');
        $response->assertSee('Runtime Engine');
    }

    public function testAdminOverviewApiReturnsJson(): void
    {
        $response = $this->get('/admin/api/overview');

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals('2.1.0', $data['version']);
        $this->assertTrue(isset($data['memory_usage_mb']));
        $this->assertTrue(isset($data['workflows_count']));
    }
}

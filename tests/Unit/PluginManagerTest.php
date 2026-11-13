<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Plugins\BasePlugin;
use CnA\Plugins\PluginManager;
use CnA\Testing\TestCase;

class DummyAuditPlugin extends BasePlugin
{
    protected string $name = 'vendor/audit-logger';
    protected string $version = '2.1.0';
    protected string $description = 'Audit log integration plugin';
    public bool $isRegistered = false;
    public bool $isBooted = false;

    public function register(ApplicationInterface $app): void
    {
        $this->isRegistered = true;
    }

    public function boot(ApplicationInterface $app): void
    {
        $this->isBooted = true;
    }
}

class PluginManagerTest extends TestCase
{
    public function testPluginRegistrationAndLifecycle(): void
    {
        $app = $this->createApplication();
        $manager = new PluginManager($app);

        $plugin = new DummyAuditPlugin();

        $this->assertFalse($manager->has('vendor/audit-logger'));

        $manager->register($plugin);

        $this->assertTrue($manager->has('vendor/audit-logger'));
        $this->assertTrue($plugin->isRegistered);
        $this->assertEquals('2.1.0', $manager->get('vendor/audit-logger')?->getVersion());
        $this->assertEquals('Audit log integration plugin', $manager->get('vendor/audit-logger')?->getDescription());

        $this->assertFalse($plugin->isBooted);
        $manager->boot();
        $this->assertTrue($plugin->isBooted);

        $this->assertEquals(1, count($manager->all()));
    }
}


<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Container\Container;
use CnA\Contracts\Exceptions\ContainerException;
use CnA\Testing\TestCase;

class ContainerTest extends TestCase
{
    public function testBindAndResolveTransient(): void
    {
        $container = new Container();
        $container->bind('service', fn() => new \stdClass());

        $a = $container->make('service');
        $b = $container->make('service');

        $this->assertTrue($a instanceof \stdClass);
        $this->assertTrue($a !== $b, 'Transient binding must produce distinct instances');
    }

    public function testSingletonBinding(): void
    {
        $container = new Container();
        $container->singleton('service', fn() => new \stdClass());

        $a = $container->make('service');
        $b = $container->make('service');

        $this->assertTrue($a === $b, 'Singleton must resolve the exact same instance');
    }

    public function testCircularDependencyDetection(): void
    {
        $container = new Container();
        $container->bind('A', fn($c) => $c->make('B'));
        $container->bind('B', fn($c) => $c->make('A'));

        $threw = false;
        try {
            $container->make('A');
        } catch (ContainerException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Circular dependency detected'));
        }

        $this->assertTrue($threw, 'Container must detect circular dependencies');
    }

    public function testTaggedServices(): void
    {
        $container = new Container();
        $container->singleton('logger1', fn() => (object) ['name' => 'l1']);
        $container->singleton('logger2', fn() => (object) ['name' => 'l2']);

        $container->tag(['logger1', 'logger2'], 'loggers');

        $loggers = iterator_to_array($container->tagged('loggers'));
        $this->assertCount(2, $loggers);
        $this->assertEquals('l1', $loggers[0]->name);
        $this->assertEquals('l2', $loggers[1]->name);
    }
}


<?php

declare(strict_types=1);

namespace CnA\Core;

use CnA\Config\Config;
use CnA\Config\Env;
use CnA\Container\Container;
use CnA\Contracts\Container\ContainerInterface;
use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Core\ServiceProviderInterface;

class Application extends Container implements ApplicationInterface
{
    public const VERSION = '2.1.0';

    protected string $basePath;
    protected bool $booted = false;

    /** @var array<string, ServiceProviderInterface> */
    protected array $serviceProviders = [];

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath !== null ? rtrim($basePath, '/\\') : dirname(__DIR__, 2);
        static::setInstance($this);

        $this->registerBaseBindings();
        $this->registerBaseEnvironment();
    }

    public function version(): string
    {
        return self::VERSION;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function environment(): string
    {
        return (string) Env::get('APP_ENV', 'production');
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function isLocal(): bool
    {
        return in_array($this->environment(), ['local', 'development', 'testing'], true);
    }

    public function register(ServiceProviderInterface|string $provider): ServiceProviderInterface
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $name = get_class($provider);
        if (isset($this->serviceProviders[$name])) {
            return $this->serviceProviders[$name];
        }

        $provider->register();
        $this->serviceProviders[$name] = $provider;

        if ($this->booted) {
            $provider->boot();
        }

        return $provider;
    }

    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        // Mitigate CVE-2026-115560 (Session upload progress memory corruption)
        if (ini_get('session.upload_progress.enabled') === '1') {
            @ini_set('session.upload_progress.enabled', '0');
        }

        // Load configuration
        $config = Config::loadFromDirectory($this->configPath());
        $this->instance(Config::class, $config);
        $this->instance('config', $config);

        // Register configured providers if present
        $providers = (array) $config->get('app.providers', []);
        foreach ($providers as $providerClass) {
            $this->register($providerClass);
        }

        // Boot all registered service providers
        foreach ($this->serviceProviders as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function terminate(): void
    {
        $this->forgetScopedInstances();
    }

    protected function registerBaseBindings(): void
    {
        $this->instance(ContainerInterface::class, $this);
        $this->instance(ApplicationInterface::class, $this);
        $this->instance(Application::class, $this);
        $this->instance('app', $this);
    }

    protected function registerBaseEnvironment(): void
    {
        $envFile = $this->basePath('.env');
        if (file_exists($envFile)) {
            Env::load($envFile);
        }
    }
}


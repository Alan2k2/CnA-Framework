<?php

declare(strict_types=1);

namespace CnA\Plugins;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Plugins\PluginInterface;
use CnA\Contracts\Plugins\PluginManagerInterface;

class PluginManager implements PluginManagerInterface
{
    /** @var array<string, PluginInterface> */
    protected array $plugins = [];

    /** @var array<string, bool> */
    protected array $booted = [];

    protected ?ApplicationInterface $app;
    protected string $pluginsPath;

    public function __construct(?ApplicationInterface $app = null, string $pluginsPath = '')
    {
        $this->app = $app;
        $this->pluginsPath = $pluginsPath !== '' ? $pluginsPath : (defined('CNA_ROOT') ? CNA_ROOT . '/plugins' : getcwd() . '/plugins');
    }

    public function register(PluginInterface $plugin): void
    {
        $name = $plugin->getName();
        $this->plugins[$name] = $plugin;

        if ($this->app !== null) {
            $plugin->register($this->app);
        }
    }

    public function boot(): void
    {
        if ($this->app === null) {
            return;
        }

        foreach ($this->plugins as $name => $plugin) {
            if (empty($this->booted[$name])) {
                $plugin->boot($this->app);
                $this->booted[$name] = true;
            }
        }
    }

    public function get(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * @return array<string, PluginInterface>
     */
    public function all(): array
    {
        return $this->plugins;
    }

    public function discover(): void
    {
        if (!is_dir($this->pluginsPath)) {
            return;
        }

        $iterator = new \DirectoryIterator($this->pluginsPath);
        foreach ($iterator as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }

            $pluginFile = $item->getPathname() . '/plugin.php';
            if (file_exists($pluginFile)) {
                $plugin = require $pluginFile;
                if ($plugin instanceof PluginInterface) {
                    $this->register($plugin);
                }
            }
        }
    }
}

<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Trees
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Plugins;

use PluginInterface;
use Trees\Router\Router;
use Trees\Contracts\ContainerInterface;

class PluginManager
{
    private ContainerInterface $container;
    private HookManager $hookManager;
    private Router $router;
    private array $plugins = [];
    private array $activePlugins = [];
    private string $pluginsPath;
    private string $statePath;

    public function __construct(
        ContainerInterface $container,
        HookManager $hookManager,
        Router $router,
        string $pluginsPath,
        string $statePath
    ) {
        $this->container = $container;
        $this->hookManager = $hookManager;
        $this->router = $router;
        $this->pluginsPath = rtrim($pluginsPath, '/');
        $this->statePath = $statePath;
        $this->loadActivePluginsState();
    }

    /**
     * Discover all available plugins
     */
    public function discover(): void
    {
        if (!is_dir($this->pluginsPath)) {
            return;
        }

        $directories = scandir($this->pluginsPath);

        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $pluginPath = $this->pluginsPath . '/' . $dir;
            $pluginFile = $pluginPath . '/Plugin.php';

            if (is_dir($pluginPath) && file_exists($pluginFile)) {
                $this->loadPlugin($dir, $pluginPath);
            }
        }
    }

    /**
     * Load a plugin class
     */
    private function loadPlugin(string $dir, string $path): void
    {
        require_once $path . '/Plugin.php';

        $className = 'Plugins\\' . $dir . '\\Plugin';

        if (!class_exists($className)) {
            return;
        }

        $plugin = new $className($path);

        if (!$plugin instanceof PluginInterface) {
            return;
        }

        $this->plugins[$plugin->getId()] = $plugin;
    }

    /**
     * Boot all active plugins
     */
    public function bootPlugins(): void
    {
        foreach ($this->activePlugins as $pluginId) {
            if (isset($this->plugins[$pluginId])) {
                $this->bootPlugin($this->plugins[$pluginId]);
            }
        }
    }

    /**
     * Boot a single plugin
     */
    private function bootPlugin(PluginInterface $plugin): void
    {
        // Register plugin services
        $plugin->register();

        // Load plugin routes
        $routesPath = $plugin->getRoutesPath();
        if ($routesPath && file_exists($routesPath)) {
            $router = $this->router;
            $hookManager = $this->hookManager;
            $container = $this->container;
            $pluginId = $plugin->getId();

            require $routesPath;
        }

        // Boot plugin
        $plugin->boot();
    }

    /**
     * Activate a plugin
     */
    public function activate(string $pluginId): bool
    {
        if (!isset($this->plugins[$pluginId])) {
            return false;
        }

        if (in_array($pluginId, $this->activePlugins)) {
            return true;
        }

        $plugin = $this->plugins[$pluginId];
        
        // Call activation hook
        $plugin->onActivate();

        // Boot the plugin
        $this->bootPlugin($plugin);

        // Mark as active
        $this->activePlugins[] = $pluginId;
        $this->saveActivePluginsState();

        return true;
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate(string $pluginId): bool
    {
        if (!in_array($pluginId, $this->activePlugins)) {
            return false;
        }

        $plugin = $this->plugins[$pluginId];

        // Call deactivation hook
        $plugin->onDeactivate();

        // Remove plugin hooks
        $this->hookManager->removePluginHooks($pluginId);

        // Remove plugin routes
        $this->router->removePluginRoutes($pluginId);

        // Remove from active plugins
        $this->activePlugins = array_filter($this->activePlugins, fn($id) => $id !== $pluginId);
        $this->saveActivePluginsState();

        return true;
    }

    /**
     * Get all plugins
     */
    public function getAllPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get active plugins
     */
    public function getActivePlugins(): array
    {
        return array_filter($this->plugins, fn($plugin) => 
            in_array($plugin->getId(), $this->activePlugins)
        );
    }

    /**
     * Check if plugin is active
     */
    public function isActive(string $pluginId): bool
    {
        return in_array($pluginId, $this->activePlugins);
    }

    /**
     * Load active plugins state from file
     */
    private function loadActivePluginsState(): void
    {
        if (file_exists($this->statePath)) {
            $data = json_decode(file_get_contents($this->statePath), true);
            $this->activePlugins = $data['active_plugins'] ?? [];
        }
    }

    /**
     * Save active plugins state to file
     */
    private function saveActivePluginsState(): void
    {
        $dir = dirname($this->statePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->statePath, json_encode([
            'active_plugins' => $this->activePlugins
        ]));
    }
}
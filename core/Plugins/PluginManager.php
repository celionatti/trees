<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* PluginManager - WITH CHANGES HIGHLIGHTED
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Plugins;

use Trees\Router\Router;
use Trees\View\View;  // ← ADDED: Import View class
use Trees\Contracts\PluginInterface;
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
     * 
     * MAIN CHANGES ARE IN THIS METHOD
     */
    private function bootPlugin(PluginInterface $plugin): void
    {
        // Register plugin services
        $plugin->register();

        // ========================================
        // CHANGE #1: Create view instance for plugin
        // ========================================
        $viewsPath = $plugin->getBasePath() . '/views';
        if (is_dir($viewsPath)) {
            $view = new View($viewsPath);
            $this->container->singleton("view.{$plugin->getId()}", fn() => $view);
        }

        // Load plugin routes
        $routesPath = $plugin->getRoutesPath();
        if ($routesPath && file_exists($routesPath)) {
            // ========================================
            // CHANGE #2: Make variables available to routes file
            // ========================================
            $router = $this->router;
            $hookManager = $this->hookManager;
            $container = $this->container;
            $pluginId = $plugin->getId();  // ← ADDED: This was missing before!

            // ========================================
            // CHANGE #3: Get view instance if available
            // ========================================
            $view = $container->has("view.{$pluginId}")
                ? $container->get("view.{$pluginId}")
                : null;  // ← ADDED: Make $view available in routes

            // Now when we require the routes file, all these variables
            // are available: $router, $hookManager, $container, $pluginId, $view
            require $routesPath;
        }

        // Boot plugin
        $plugin->boot();
    }

    public function activate(string $pluginId): bool
    {
        if (!isset($this->plugins[$pluginId])) {
            return false;
        }

        if (in_array($pluginId, $this->activePlugins)) {
            return true;
        }

        $plugin = $this->plugins[$pluginId];

        $plugin->onActivate();
        $this->bootPlugin($plugin);

        $this->activePlugins[] = $pluginId;
        $this->saveActivePluginsState();

        return true;
    }

    public function deactivate(string $pluginId): bool
    {
        if (!in_array($pluginId, $this->activePlugins)) {
            return false;
        }

        $plugin = $this->plugins[$pluginId];

        $plugin->onDeactivate();
        $this->hookManager->removePluginHooks($pluginId);
        $this->router->removePluginRoutes($pluginId);

        $this->activePlugins = array_filter($this->activePlugins, fn($id) => $id !== $pluginId);
        $this->saveActivePluginsState();

        return true;
    }

    public function getAllPlugins(): array
    {
        return $this->plugins;
    }

    public function getActivePlugins(): array
    {
        return array_filter(
            $this->plugins,
            fn($plugin) =>
            in_array($plugin->getId(), $this->activePlugins)
        );
    }

    public function isActive(string $pluginId): bool
    {
        return in_array($pluginId, $this->activePlugins);
    }

    private function loadActivePluginsState(): void
    {
        if (file_exists($this->statePath)) {
            $data = json_decode(file_get_contents($this->statePath), true);
            $this->activePlugins = $data['active_plugins'] ?? [];
        }
    }

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

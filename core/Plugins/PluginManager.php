<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* PluginManager - With Individual Plugin Configs
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Plugins;

use Trees\Router\Router;
use Trees\View\View;
use Trees\Contracts\PluginInterface;
use Trees\Contracts\ContainerInterface;

class PluginManager
{
    private ContainerInterface $container;
    private HookManager $hookManager;
    private Router $router;
    private array $plugins = [];
    private array $pluginConfigs = [];
    private string $pluginsPath;

    public function __construct(
        ContainerInterface $container,
        HookManager $hookManager,
        Router $router,
        string $pluginsPath
    ) {
        $this->container = $container;
        $this->hookManager = $hookManager;
        $this->router = $router;
        $this->pluginsPath = rtrim($pluginsPath, '/');
    }

    /**
     * Discover all available plugins and load their configs
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
            $configFile = $pluginPath . '/plugin.json';

            // Plugin must have both Plugin.php and plugin.json
            if (is_dir($pluginPath) && file_exists($pluginFile) && file_exists($configFile)) {
                $this->loadPlugin($dir, $pluginPath, $configFile);
            }
        }
    }

    /**
     * Load a plugin and its configuration
     */
    private function loadPlugin(string $dir, string $path, string $configFile): void
    {
        // Load plugin config
        $configData = json_decode(file_get_contents($configFile), true);

        if (!$configData) {
            error_log("Invalid plugin.json for: $dir");
            return;
        }

        // Validate required config fields
        if (!isset($configData['id']) || !isset($configData['name'])) {
            error_log("Plugin config missing required fields (id, name) for: $dir");
            return;
        }

        // Load the plugin class
        require_once $path . '/Plugin.php';

        $className = 'Plugins\\' . $dir . '\\Plugin';

        if (!class_exists($className)) {
            error_log("Plugin class not found: $className");
            return;
        }

        $plugin = new $className($path);

        if (!$plugin instanceof PluginInterface) {
            error_log("Plugin does not implement PluginInterface: $className");
            return;
        }

        $pluginId = $plugin->getId();

        // Store plugin and its config
        $this->plugins[$pluginId] = $plugin;
        $this->pluginConfigs[$pluginId] = $configData;
    }

    /**
     * Boot all active plugins based on their config
     */
    public function bootPlugins(): void
    {
        foreach ($this->plugins as $pluginId => $plugin) {
            $config = $this->pluginConfigs[$pluginId] ?? [];

            // Check if plugin is enabled in its config
            if (!empty($config['enabled'])) {
                $this->bootPlugin($plugin, $config);
            }
        }
    }

    /**
     * Boot a single plugin with its configuration
     */
    private function bootPlugin(PluginInterface $plugin, array $config): void
    {
        $pluginId = $plugin->getId();

        error_log("Booting plugin: $pluginId");

        // Check dependencies
        if (!empty($config['dependencies'])) {
            foreach ($config['dependencies'] as $dependency) {
                if (!$this->isActive($dependency)) {
                    error_log("Plugin $pluginId requires $dependency but it's not active");
                    return;
                }
            }
        }

        // Register plugin configuration in container
        $this->container->singleton("config.{$pluginId}", fn() => $config);

        // Register plugin services
        $plugin->register();

        // Create view instance if views directory exists
        $viewsPath = $plugin->getBasePath() . '/views';
        if (is_dir($viewsPath)) {
            $view = new View($viewsPath);
            $this->container->singleton("view.{$pluginId}", fn() => $view);
        }

        // Load plugin routes
        $routesPath = $plugin->getRoutesPath();
        if ($routesPath && file_exists($routesPath)) {
            $router = $this->router;
            $hookManager = $this->hookManager;
            $container = $this->container;
            $view = $container->has("view.{$pluginId}")
                ? $container->get("view.{$pluginId}")
                : null;

            // Make config available in routes
            $pluginConfig = $config;

            require $routesPath;
        }

        // Boot plugin
        $plugin->boot();
    }

    /**
     * Activate a plugin (update its config file)
     */
    public function activate(string $pluginId): bool
    {
        if (!isset($this->plugins[$pluginId])) {
            return false;
        }

        $config = $this->pluginConfigs[$pluginId];

        if (!empty($config['enabled'])) {
            return true; // Already active
        }

        $plugin = $this->plugins[$pluginId];

        // Update config
        $config['enabled'] = true;
        $this->savePluginConfig($pluginId, $config);

        // Call activation hook
        $plugin->onActivate();

        // Boot the plugin
        $this->bootPlugin($plugin, $config);

        return true;
    }

    /**
     * Deactivate a plugin (update its config file)
     */
    public function deactivate(string $pluginId): bool
    {
        if (!isset($this->plugins[$pluginId])) {
            return false;
        }

        $config = $this->pluginConfigs[$pluginId];

        if (empty($config['enabled'])) {
            return true; // Already inactive
        }

        $plugin = $this->plugins[$pluginId];

        // Call deactivation hook
        $plugin->onDeactivate();

        // Remove plugin hooks
        $this->hookManager->removePluginHooks($pluginId);

        // Remove plugin routes
        $this->router->removePluginRoutes($pluginId);

        // Update config
        $config['enabled'] = false;
        $this->savePluginConfig($pluginId, $config);

        return true;
    }

    /**
     * Save plugin configuration to its plugin.json file
     */
    private function savePluginConfig(string $pluginId, array $config): void
    {
        $plugin = $this->plugins[$pluginId];
        $configPath = $plugin->getBasePath() . '/plugin.json';

        file_put_contents(
            $configPath,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Update in-memory config
        $this->pluginConfigs[$pluginId] = $config;
    }

    /**
     * Get plugin configuration
     */
    public function getPluginConfig(string $pluginId): ?array
    {
        return $this->pluginConfigs[$pluginId] ?? null;
    }

    /**
     * Update plugin configuration
     */
    public function updatePluginConfig(string $pluginId, array $updates): bool
    {
        if (!isset($this->pluginConfigs[$pluginId])) {
            return false;
        }

        $config = array_merge($this->pluginConfigs[$pluginId], $updates);
        $this->savePluginConfig($pluginId, $config);

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
        return array_filter($this->plugins, function ($plugin) {
            $config = $this->pluginConfigs[$plugin->getId()] ?? [];
            return !empty($config['enabled']);
        });
    }

    /**
     * Check if plugin is active
     */
    public function isActive(string $pluginId): bool
    {
        $config = $this->pluginConfigs[$pluginId] ?? [];
        return !empty($config['enabled']);
    }

    /**
     * Get plugin by ID
     */
    public function getPlugin(string $pluginId): ?PluginInterface
    {
        return $this->plugins[$pluginId] ?? null;
    }

    /**
     * Check if plugin meets minimum system requirements
     */
    public function checkRequirements(string $pluginId): array
    {
        $config = $this->pluginConfigs[$pluginId] ?? null;

        if (!$config) {
            return ['valid' => false, 'errors' => ['Plugin not found']];
        }

        $errors = [];

        // Check PHP version
        if (!empty($config['requires']['php'])) {
            if (version_compare(PHP_VERSION, $config['requires']['php'], '<')) {
                $errors[] = "Requires PHP {$config['requires']['php']} or higher";
            }
        }

        // Check required PHP extensions
        if (!empty($config['requires']['extensions'])) {
            foreach ($config['requires']['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    $errors[] = "Requires PHP extension: $extension";
                }
            }
        }

        // Check dependencies
        if (!empty($config['dependencies'])) {
            foreach ($config['dependencies'] as $dependency) {
                if (!isset($this->plugins[$dependency])) {
                    $errors[] = "Requires plugin: $dependency";
                } elseif (!$this->isActive($dependency)) {
                    $errors[] = "Requires plugin '$dependency' to be active";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

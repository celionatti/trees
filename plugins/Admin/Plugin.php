<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Admin - Plugin.php
* ----------------------------------------------
* @package Trees 2025
*/

namespace Plugins\Admin;

use Trees\Contracts\PluginInterface;

class Plugin implements PluginInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function getId(): string
    {
        return 'admin';
    }

    public function getName(): string
    {
        return 'Admin Dashboard';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getRoutesPath(): ?string
    {
        return $this->basePath . '/routes.php';
    }

    public function register(): void
    {
        // Register admin services
    }

    public function boot(): void
    {
        global $app;
        $hookManager = $app->getHookManager();
        $config = $app->getPluginManager()->getPluginConfig($this->getId());

        // Add admin to navigation
        if ($config['settings']['show_in_nav'] ?? true) {
            $hookManager->addFilter('navigation.items', function ($items) use ($config) {
                $items[] = [
                    'label' => $config['settings']['nav_label'] ?? 'Admin',
                    'url' => $config['settings']['admin_path'] ?? '/admin',
                    'plugin' => 'admin'
                ];
                return $items;
            }, 100); // High priority to appear last
        }
    }

    public function onActivate(): void
    {
        error_log("Admin Plugin activated");
    }

    public function onDeactivate(): void
    {
        error_log("Admin Plugin deactivated");
    }
}

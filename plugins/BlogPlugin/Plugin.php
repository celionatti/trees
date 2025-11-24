<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* BlogPlugin - Plugin.php
* ----------------------------------------------
* @package Trees 2025
*/

namespace Plugins\BlogPlugin;

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
        return 'blog-plugin';
    }

    public function getName(): string
    {
        return 'Blog Plugin';
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
        // Register plugin services in container
    }

    public function boot(): void
    {
        // Boot logic - register hooks, filters, etc.
        global $app;
        
        $hookManager = $app->getHookManager();
        
        // Add navigation item
        $hookManager->addFilter('navigation.items', function($items) {
            $items[] = [
                'label' => 'Blog',
                'url' => '/blog',
                'plugin' => 'blog-plugin'
            ];
            return $items;
        });

        // Add custom action
        $hookManager->addAction('app.init', function($app) {
            // Initialization logic
        });
    }

    public function onActivate(): void
    {
        // Run on plugin activation (e.g., create database tables)
        error_log("Blog Plugin activated");
    }

    public function onDeactivate(): void
    {
        // Run on plugin deactivation (e.g., cleanup)
        error_log("Blog Plugin deactivated");
    }
}
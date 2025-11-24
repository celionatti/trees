<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* PluginInterface
* ----------------------------------------------
* @package Trees 2025
*/

interface PluginInterface
{
    /**
     * Get plugin identifier
     */
    public function getId(): string;

    /**
     * Get plugin name
     */
    public function getName(): string;

    /**
     * Get plugin version
     */
    public function getVersion(): string;

    /**
     * Boot the plugin
     */
    public function boot(): void;

    /**
     * Register plugin services
     */
    public function register(): void;

    /**
     * Get plugin routes file path
     */
    public function getRoutesPath(): ?string;

    /**
     * Get plugin base directory
     */
    public function getBasePath(): string;

    /**
     * Called when plugin is activated
     */
    public function onActivate(): void;

    /**
     * Called when plugin is deactivated
     */
    public function onDeactivate(): void;
}
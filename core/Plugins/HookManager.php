<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* HookManager
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Plugins;

class HookManager
{
    private array $filters = [];
    private array $actions = [];

    /**
     * Register a filter hook
     */
    public function addFilter(string $tag, callable $callback, int $priority = 10): void
    {
        $this->filters[$tag][$priority][] = $callback;
        ksort($this->filters[$tag]);
    }

    /**
     * Apply filters to a value
     */
    public function applyFilters(string $tag, $value, ...$args)
    {
        if (!isset($this->filters[$tag])) {
            return $value;
        }

        foreach ($this->filters[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func($callback, $value, ...$args);
            }
        }

        return $value;
    }

    /**
     * Register an action hook
     */
    public function addAction(string $tag, callable $callback, int $priority = 10): void
    {
        $this->actions[$tag][$priority][] = $callback;
        ksort($this->actions[$tag]);
    }

    /**
     * Execute action hooks
     */
    public function doAction(string $tag, ...$args): void
    {
        if (!isset($this->actions[$tag])) {
            return;
        }

        foreach ($this->actions[$tag] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func($callback, ...$args);
            }
        }
    }

    /**
     * Remove all hooks for a specific plugin
     */
    public function removePluginHooks(string $pluginId): void
    {
        foreach ($this->filters as $tag => &$priorities) {
            foreach ($priorities as $priority => &$callbacks) {
                $callbacks = array_filter($callbacks, function($callback) use ($pluginId) {
                    return !$this->isPluginCallback($callback, $pluginId);
                });
                if (empty($callbacks)) {
                    unset($priorities[$priority]);
                }
            }
            if (empty($priorities)) {
                unset($this->filters[$tag]);
            }
        }

        foreach ($this->actions as $tag => &$priorities) {
            foreach ($priorities as $priority => &$callbacks) {
                $callbacks = array_filter($callbacks, function($callback) use ($pluginId) {
                    return !$this->isPluginCallback($callback, $pluginId);
                });
                if (empty($callbacks)) {
                    unset($priorities[$priority]);
                }
            }
            if (empty($priorities)) {
                unset($this->actions[$tag]);
            }
        }
    }

    private function isPluginCallback($callback, string $pluginId): bool
    {
        if (is_array($callback) && is_object($callback[0])) {
            $class = get_class($callback[0]);
            return strpos($class, $pluginId) !== false;
        }
        return false;
    }
}
<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Container
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Container;

use Trees\Contracts\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];
    private array $singletons = [];

    public function bind(string $id, $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    public function singleton(string $id, $concrete): void
    {
        $this->singletons[$id] = $concrete;
        $this->bind($id, $concrete);
    }

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new class("Service {$id} not found") extends \Exception implements NotFoundExceptionInterface {};
        }

        // Return existing singleton instance
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $concrete = $this->bindings[$id];

        // Resolve the binding
        if (is_callable($concrete)) {
            $object = $concrete($this);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $object = new $concrete();
        } else {
            $object = $concrete;
        }

        // Store singleton instances
        if (isset($this->singletons[$id])) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }
}
<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* ContainerInterface
* ----------------------------------------------
* @package Trees 2025
*/

namespace Trees\Contracts;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Bind a value to the container
     */
    public function bind(string $id, $concrete): void;

    /**
     * Bind a singleton to the container
     */
    public function singleton(string $id, $concrete): void;
}
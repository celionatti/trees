<?php

declare(strict_types=1);

use Trees\Support\Dumper\Dumper;

if(!function_exists('dump')) {
    function dump(...$args)
    {
        Dumper::dump(...$args);
        exit;
    }
}

if(!function_exists('quick_dump')) {
    function quick_dump(...$args)
    {
        Dumper::quickDump(...$args);
        exit;
    }
}

if(!function_exists('dump_log')) {
    function dump_log(...$args)
    {
        Dumper::log(...$args);
        exit;
    }
}

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and continue execution.
     *
     * @param mixed ...$vars
     * @return void
     */
    function dd(mixed ...$vars): void
    {
        Dumper::dd(...$vars);
        exit(1);
    }
}

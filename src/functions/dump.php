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

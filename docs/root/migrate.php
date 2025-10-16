#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = parse_ini_file(__DIR__ . '/.env');
    foreach ($dotenv as $key => $value) {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

use Trees\Database\Connection;
use Trees\Database\MigrationRunner;

$config = require __DIR__ . '/config/database.php';
$dbConfig = $config['connections'][$config['default']];

$connection = Connection::getInstance($dbConfig);
$runner = new MigrationRunner($connection, __DIR__ . '/database/migrations');

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        $runner->run();
        break;
        
    case 'rollback':
        $steps = (int) ($argv[2] ?? 1);
        $runner->rollback($steps);
        break;
        
    case 'reset':
        $runner->reset();
        break;
        
    case 'fresh':
        $runner->reset();
        $runner->run();
        break;
        
    default:
        echo "Trees Framework Migration Tool\n\n";
        echo "Usage:\n";
        echo "  php migrate.php migrate          Run pending migrations\n";
        echo "  php migrate.php rollback [steps] Rollback last migration(s)\n";
        echo "  php migrate.php reset            Rollback all migrations\n";
        echo "  php migrate.php fresh            Reset and re-run all migrations\n";
        break;
}
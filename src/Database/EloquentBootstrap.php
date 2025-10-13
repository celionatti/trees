<?php

declare(strict_types=1);

namespace Trees\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class EloquentBootstrap
{
    public static function boot(array $config): void
    {
        $capsule = new Capsule;
        
        $capsule->addConnection([
            'driver' => $config['driver'],
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset' => $config['charset'] ?? 'utf8mb4',
            'collation' => $config['collation'] ?? 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
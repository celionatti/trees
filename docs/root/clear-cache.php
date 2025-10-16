<?php

$cacheDir = __DIR__ . '/storage/views';

if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "Cache cleared successfully!\n";
} else {
    echo "Cache directory not found.\n";
}
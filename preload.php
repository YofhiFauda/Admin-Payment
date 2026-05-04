<?php
/**
 * OPcache Preloading Script
 * 
 * This file preloads Laravel framework files into OPcache
 * for better performance in production.
 * 
 * PHP 8.0+ feature
 */

if (php_sapi_name() !== 'cli') {
    die('Preloading can only be done via CLI');
}

$baseDir = __DIR__;

// Load Composer autoloader
require_once $baseDir . '/vendor/autoload.php';

// Preload Laravel framework
$files = [
    // Core
    '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
    '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
    '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
    
    // HTTP
    '/vendor/laravel/framework/src/Illuminate/Http/Request.php',
    '/vendor/laravel/framework/src/Illuminate/Http/Response.php',
    '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
    
    // Database
    '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php',
    '/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php',
    
    // Cache
    '/vendor/laravel/framework/src/Illuminate/Cache/CacheManager.php',
    '/vendor/laravel/framework/src/Illuminate/Redis/RedisManager.php',
    
    // Queue
    '/vendor/laravel/framework/src/Illuminate/Queue/QueueManager.php',
];

foreach ($files as $file) {
    $fullPath = $baseDir . $file;
    if (file_exists($fullPath)) {
        opcache_compile_file($fullPath);
    }
}

echo "Preloading completed\n";

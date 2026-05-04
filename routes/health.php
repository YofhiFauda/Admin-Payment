<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\SupervisorRepository;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| Routes untuk monitoring dan health checks
| Digunakan oleh load balancer, monitoring tools, dll
|
*/

// Basic health check - cepat, tidak hit database
Route::get('/ping', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Detailed health check - check semua services
Route::get('/health', function () {
    $health = [
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'services' => [],
    ];

    // Check Database
    try {
        DB::connection()->getPdo();
        $health['services']['database'] = [
            'status' => 'connected',
            'connection' => config('database.default'),
        ];
    } catch (\Exception $e) {
        $health['status'] = 'unhealthy';
        $health['services']['database'] = [
            'status' => 'disconnected',
            'error' => $e->getMessage(),
        ];
    }

    // Check Redis
    try {
        Redis::connection()->ping();
        $health['services']['redis'] = [
            'status' => 'connected',
            'memory_usage' => Redis::connection()->info('memory')['used_memory_human'] ?? 'unknown',
        ];
    } catch (\Exception $e) {
        $health['status'] = 'unhealthy';
        $health['services']['redis'] = [
            'status' => 'disconnected',
            'error' => $e->getMessage(),
        ];
    }

    // Check Queue (Horizon)
    try {
        $masters = app(SupervisorRepository::class)->all();
        $health['services']['queue'] = [
            'status' => count($masters) > 0 ? 'running' : 'stopped',
            'supervisors' => count($masters),
        ];
        
        if (count($masters) === 0) {
            $health['status'] = 'degraded';
        }
    } catch (\Exception $e) {
        $health['status'] = 'degraded';
        $health['services']['queue'] = [
            'status' => 'unknown',
            'error' => $e->getMessage(),
        ];
    }

    // Check Storage
    try {
        $storagePath = storage_path('app');
        $freeSpace = disk_free_space($storagePath);
        $totalSpace = disk_total_space($storagePath);
        $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

        $health['services']['storage'] = [
            'status' => $usedPercent < 90 ? 'ok' : 'warning',
            'used_percent' => $usedPercent,
            'free_space' => round($freeSpace / 1024 / 1024 / 1024, 2) . ' GB',
        ];

        if ($usedPercent >= 90) {
            $health['status'] = 'degraded';
        }
    } catch (\Exception $e) {
        $health['services']['storage'] = [
            'status' => 'unknown',
            'error' => $e->getMessage(),
        ];
    }

    // Determine HTTP status code
    $statusCode = match ($health['status']) {
        'healthy' => 200,
        'degraded' => 200, // Still operational
        'unhealthy' => 503,
        default => 500,
    };

    return response()->json($health, $statusCode);
});

// Readiness check - untuk Kubernetes/load balancer
Route::get('/ready', function () {
    try {
        // Check critical services only
        DB::connection()->getPdo();
        Redis::connection()->ping();
        
        return response()->json([
            'status' => 'ready',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'not_ready',
            'error' => $e->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ], 503);
    }
});

// Liveness check - untuk Kubernetes
Route::get('/alive', function () {
    return response()->json([
        'status' => 'alive',
        'timestamp' => now()->toIso8601String(),
    ], 200);
});

// Metrics endpoint - untuk Prometheus/monitoring
Route::get('/metrics', function () {
    $metrics = [];

    // Database metrics
    try {
        $dbSize = DB::select("
            SELECT 
                SUM(data_length + index_length) / 1024 / 1024 AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ", [config('database.connections.mysql.database')]);
        
        $metrics['database_size_mb'] = round($dbSize[0]->size_mb ?? 0, 2);
        $metrics['database_connections'] = DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
    } catch (\Exception $e) {
        $metrics['database_error'] = $e->getMessage();
    }

    // Redis metrics
    try {
        $redisInfo = Redis::connection()->info();
        $metrics['redis_memory_used_mb'] = round($redisInfo['used_memory'] / 1024 / 1024, 2);
        $metrics['redis_connected_clients'] = $redisInfo['connected_clients'] ?? 0;
        $metrics['redis_total_commands'] = $redisInfo['total_commands_processed'] ?? 0;
    } catch (\Exception $e) {
        $metrics['redis_error'] = $e->getMessage();
    }

    // Queue metrics
    try {
        $metrics['queue_pending'] = DB::table('jobs')->count();
        $metrics['queue_failed'] = DB::table('failed_jobs')->count();
    } catch (\Exception $e) {
        $metrics['queue_error'] = $e->getMessage();
    }

    // Application metrics
    $metrics['app_version'] = config('app.version', '1.0.0');
    $metrics['app_env'] = config('app.env');
    $metrics['php_version'] = PHP_VERSION;
    $metrics['laravel_version'] = app()->version();

    return response()->json($metrics, 200);
});

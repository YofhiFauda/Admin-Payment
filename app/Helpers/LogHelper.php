<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogHelper
{
    /**
     * Log OCR processing
     */
    public static function ocr(string $level, string $message, array $context = []): void
    {
        Log::channel('ocr')->{$level}($message, array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]));
    }

    /**
     * Log security events
     */
    public static function security(string $message, array $context = []): void
    {
        Log::channel('security')->warning($message, array_merge($context, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log audit trail
     */
    public static function audit(string $action, string $model, $modelId, array $changes = []): void
    {
        Log::channel('audit')->info("Audit: {$action}", [
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip' => request()->ip(),
            'changes' => $changes,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log performance issues
     */
    public static function performance(string $operation, float $duration, array $context = []): void
    {
        // Only log if duration > 1 second
        if ($duration > 1000) {
            Log::channel('performance')->warning("Slow operation: {$operation}", array_merge($context, [
                'duration_ms' => round($duration, 2),
                'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'timestamp' => now()->toIso8601String(),
            ]));
        }
    }

    /**
     * Log queue job
     */
    public static function queue(string $level, string $jobName, array $context = []): void
    {
        Log::channel('queue')->{$level}("Job: {$jobName}", array_merge($context, [
            'job' => $jobName,
            'queue' => $context['queue'] ?? 'default',
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Sanitize sensitive data before logging
     */
    public static function sanitize(array $data): array
    {
        $sensitive = [
            'password',
            'token',
            'secret',
            'api_key',
            'apikey',
            'api-key',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'authorization',
            'bearer',
        ];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitize($value);
            } elseif (is_string($key) && Str::contains(strtolower($key), $sensitive)) {
                $data[$key] = '***REDACTED***';
            }
        }
        
        return $data;
    }

    /**
     * Log with automatic sanitization
     */
    public static function safe(string $level, string $message, array $context = []): void
    {
        Log::{$level}($message, self::sanitize($context));
    }

    /**
     * Log exception with full context
     */
    public static function exception(\Throwable $exception, array $context = []): void
    {
        Log::error($exception->getMessage(), array_merge($context, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toIso8601String(),
        ]));
    }

    /**
     * Log slow query
     */
    public static function slowQuery(string $sql, float $time, array $bindings = []): void
    {
        if ($time > 1000) { // > 1 second
            Log::channel('performance')->warning('Slow query detected', [
                'sql' => $sql,
                'time_ms' => round($time, 2),
                'bindings' => self::sanitize($bindings),
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Log API request/response
     */
    public static function api(string $direction, string $endpoint, array $data = []): void
    {
        Log::info("API {$direction}: {$endpoint}", self::sanitize([
            'direction' => $direction, // 'request' or 'response'
            'endpoint' => $endpoint,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}

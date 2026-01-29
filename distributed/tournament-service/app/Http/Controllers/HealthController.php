<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'tournament-service',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'uptime' => $this->getUptime()
        ]);
    }

    /**
     * Comprehensive health check with all components
     */
    public function comprehensive(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'external_services' => $this->checkExternalServices(),
            'system' => $this->checkSystemResources(),
            'application' => $this->getApplicationMetrics(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return response()->json([
            'status' => $overallStatus,
            'service' => 'tournament-service',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString(),
            'uptime' => $this->getUptime(),
            'checks' => $checks,
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
        ], $overallStatus === 'healthy' ? 200 : 503);
    }

    /**
     * Database health check
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            
            // Test basic connection
            DB::connection()->getPdo();
            
            // Test query execution
            DB::select('SELECT 1');
            
            // Test table access
            $tournamentCount = DB::table('tournaments')->count();
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => 'healthy',
                'response_time_ms' => round($responseTime, 2),
                'connection' => 'ok',
                'query_execution' => 'ok',
                'table_access' => 'ok',
                'tournaments_count' => $tournamentCount,
                'max_connections' => DB::select('SHOW VARIABLES LIKE "max_connections"')[0]->Value ?? 'unknown',
                'active_connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'connection' => 'failed',
                'query_execution' => 'failed',
                'table_access' => 'failed',
            ];
        }
    }

    /**
     * Cache/Redis health check
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            
            // Test cache write
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . time();
            Cache::put($testKey, $testValue, 60);
            
            // Test cache read
            $retrieved = Cache::get($testKey);
            
            // Clean up
            Cache::forget($testKey);
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'status' => $retrieved === $testValue ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($responseTime, 2),
                'write_operation' => $retrieved === $testValue ? 'ok' : 'failed',
                'read_operation' => $retrieved === $testValue ? 'ok' : 'failed',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);
            
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'write_operation' => 'failed',
                'read_operation' => 'failed',
            ];
        }
    }

    /**
     * Check external service dependencies
     */
    protected function checkExternalServices(): array
    {
        $services = [];
        
        // Check Auth Service dependency
        try {
            $authUrl = config('services.auth.url', 'http://localhost:8001');
            $startTime = microtime(true);
            $response = Http::timeout(5)->get($authUrl . '/api/health');
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $services['auth_service'] = [
                'status' => $response->status() === 200 ? 'healthy' : 'unhealthy',
                'response_time_ms' => round($responseTime, 2),
                'url' => $authUrl,
                'response_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            $services['auth_service'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'url' => config('services.auth.url', 'http://localhost:8001'),
            ];
        }
        
        // Check Redis (if configured)
        try {
            $redisUrl = config('database.redis.default.url');
            if ($redisUrl) {
                $services['redis'] = [
                    'status' => 'healthy',
                    'url' => $redisUrl,
                ];
            } else {
                $services['redis'] = [
                    'status' => 'not_configured',
                    'message' => 'Redis not configured',
                ];
            }
        } catch (\Exception $e) {
            $services['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
        
        return [
            'status' => $this->getExternalServicesStatus($services),
            'services' => $services,
        ];
    }

    /**
     * System resources health check
     */
    protected function checkSystemResources(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $memoryUsagePercent = $memoryLimitBytes ? ($memoryUsage / $memoryLimitBytes) * 100 : 0;

        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = $diskTotal ? (($diskTotal - $diskFree) / $diskTotal) * 100 : 0;

        $loadAverage = sys_getloadavg();
        $cpuCores = $this->getCpuCores();

        return [
            'status' => $this->getSystemHealthStatus($memoryUsagePercent, $diskUsagePercent, $loadAverage),
            'memory' => [
                'usage_bytes' => $memoryUsage,
                'limit_bytes' => $memoryLimitBytes,
                'usage_percent' => round($memoryUsagePercent, 2),
                'limit_readable' => $memoryLimit,
                'usage_readable' => $this->formatBytes($memoryUsage),
            ],
            'disk' => [
                'free_bytes' => $diskFree,
                'total_bytes' => $diskTotal,
                'usage_percent' => round($diskUsagePercent, 2),
                'free_readable' => $this->formatBytes($diskFree),
                'total_readable' => $this->formatBytes($diskTotal),
            ],
            'cpu' => [
                'load_average_1min' => $loadAverage[0] ?? 0,
                'load_average_5min' => $loadAverage[1] ?? 0,
                'load_average_15min' => $loadAverage[2] ?? 0,
                'cores' => $cpuCores,
                'load_per_core' => $cpuCores ? round(($loadAverage[0] ?? 0) / $cpuCores, 2) : 0,
            ],
        ];
    }

    /**
     * Application metrics
     */
    protected function getApplicationMetrics(): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            $logSize = file_exists($logFile) ? filesize($logFile) : 0;
            
            // Get database metrics
            $tournamentCount = DB::table('tournaments')->count();
            $sportCount = DB::table('sports')->count();
            $venueCount = DB::table('venues')->count();
            
            return [
                'status' => 'healthy',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'log_file_size_bytes' => $logSize,
                'log_file_size_readable' => $this->formatBytes($logSize),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'maintenance_mode' => app()->isDownForMaintenance(),
                'debug_mode' => config('app.debug'),
                'database_metrics' => [
                    'tournaments_count' => $tournamentCount,
                    'sports_count' => $sportCount,
                    'venues_count' => $venueCount,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get application uptime
     */
    protected function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $startTime = defined('LARAVEL_START') ? LARAVEL_START : time();
            $uptime = time() - $startTime;
            return $this->formatDuration($uptime);
        }
        
        return 'unknown';
    }

    /**
     * Determine overall health status
     */
    protected function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        
        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Get external services status
     */
    protected function getExternalServicesStatus(array $services): string
    {
        $statuses = array_column($services, 'status');
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        
        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }
        
        if (in_array('not_configured', $statuses)) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Get system health status based on resource usage
     */
    protected function getSystemHealthStatus(float $memoryPercent, float $diskPercent, array $loadAverage): string
    {
        if ($memoryPercent > 90 || $diskPercent > 90 || ($loadAverage[0] ?? 0) > 10) {
            return 'unhealthy';
        }
        
        if ($memoryPercent > 75 || $diskPercent > 75 || ($loadAverage[0] ?? 0) > 5) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    /**
     * Parse memory limit string to bytes
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = strtolower($limit);
        $multiplier = 1;
        
        if (str_ends_with($limit, 'g')) {
            $multiplier = 1024 * 1024 * 1024;
        } elseif (str_ends_with($limit, 'm')) {
            $multiplier = 1024 * 1024;
        } elseif (str_ends_with($limit, 'k')) {
            $multiplier = 1024;
        }
        
        return (int) ((int) $limit * $multiplier);
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Format duration to human readable format
     */
    protected function formatDuration(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        if ($seconds > 0 || empty($parts)) $parts[] = "{$seconds}s";
        
        return implode(' ', $parts);
    }

    /**
     * Get number of CPU cores
     */
    protected function getCpuCores(): int
    {
        if (function_exists('shell_exec')) {
            $cores = shell_exec('nproc 2>/dev/null || echo 1');
            return (int) trim($cores);
        }
        
        return 1;
    }
}

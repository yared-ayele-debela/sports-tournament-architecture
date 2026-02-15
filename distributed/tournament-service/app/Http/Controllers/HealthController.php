<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HealthController extends Controller
{
    /**
     * Comprehensive health check endpoint
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'dependencies' => $this->checkDependencies(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return response()->json([
            'status' => $overallStatus,
            'service' => 'tournament-service',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $overallStatus === 'ok' ? 200 : 503);
    }

    /**
     * Database health check
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => 'Database connection failed'];
        }
    }

    /**
     * Redis health check
     */
    protected function checkRedis(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'ok';
            
            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return $retrieved === $testValue 
                ? ['status' => 'ok'] 
                : ['status' => 'error', 'error' => 'Redis read/write failed'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => 'Redis connection failed'];
        }
    }

    /**
     * Check service dependencies
     */
    protected function checkDependencies(): array
    {
        $dependencies = [];
        
        // Check Auth Service dependency
        try {
            $authUrl = config('services.auth.url', 'http://auth-service:8001');
            $response = Http::timeout(3)->get($authUrl . '/api/health');
            
            $dependencies['auth_service'] = [
                'status' => $response->status() === 200 ? 'ok' : 'error',
                'response_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            $dependencies['auth_service'] = [
                'status' => 'error',
                'error' => 'Auth service unreachable',
            ];
        }

        $hasErrors = collect($dependencies)->contains('status', 'error');
        
        return [
            'status' => $hasErrors ? 'error' : 'ok',
            'services' => $dependencies,
        ];
    }

    /**
     * Determine overall health status
     */
    protected function determineOverallStatus(array $checks): string
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                return 'error';
            }
        }
        return 'ok';
    }
}

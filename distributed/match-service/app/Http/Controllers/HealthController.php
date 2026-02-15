<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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
            'queue' => $this->checkQueue(),
            'dependencies' => $this->checkDependencies(),
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return response()->json([
            'status' => $overallStatus,
            'service' => 'match-service',
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
     * Queue health check
     */
    protected function checkQueue(): array
    {
        try {
            $queueSize = 0;
            $failedJobs = 0;
            
            // Try to get queue size based on connection type
            $queueConnection = config('queue.default');
            
            if ($queueConnection === 'redis') {
                try {
                    $redis = app('redis');
                    $queueSize = $redis->llen('queues:default');
                    $failedJobs = $redis->llen('queues:failed');
                } catch (\Exception $e) {
                    // Redis not available for queue check
                }
            } elseif ($queueConnection === 'database') {
                try {
                    $queueSize = DB::table('jobs')->count();
                    $failedJobs = DB::table('failed_jobs')->count();
                } catch (\Exception $e) {
                    // Database queue check failed
                }
            }
            
            return [
                'status' => 'ok',
                'connection' => $queueConnection,
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => 'Queue check failed'];
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

        // Check Tournament Service dependency
        try {
            $tournamentUrl = config('services.tournament_service.url', 'http://tournament-service:8002');
            $response = Http::timeout(3)->get($tournamentUrl . '/api/health');
            
            $dependencies['tournament_service'] = [
                'status' => $response->status() === 200 ? 'ok' : 'error',
                'response_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            $dependencies['tournament_service'] = [
                'status' => 'error',
                'error' => 'Tournament service unreachable',
            ];
        }

        // Check Team Service dependency
        try {
            $teamUrl = config('services.team_service.url', 'http://team-service:8003');
            $response = Http::timeout(3)->get($teamUrl . '/api/health');
            
            $dependencies['team_service'] = [
                'status' => $response->status() === 200 ? 'ok' : 'error',
                'response_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            $dependencies['team_service'] = [
                'status' => 'error',
                'error' => 'Team service unreachable',
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

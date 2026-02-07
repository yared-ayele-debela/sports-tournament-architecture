<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class TestRedis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Redis connection and caching';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Redis Connection...');
        $this->newLine();

        // Test 1: Redis Connection
        try {
            $ping = Redis::connection()->ping();
            if ($ping) {
                $this->info('✅ Redis connection: SUCCESS');
            } else {
                $this->error('❌ Redis connection: FAILED');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Redis connection: FAILED');
            $this->error('   Error: ' . $e->getMessage());
            return 1;
        }

        // Test 2: Cache Store
        $cacheStore = config('cache.default');
        $this->info("📦 Cache store: {$cacheStore}");

        if ($cacheStore !== 'redis') {
            $this->warn('⚠️  Warning: Cache store is not set to Redis');
            $this->warn('   Set CACHE_STORE=redis in your .env file');
        }

        // Test 3: Basic Cache Operations
        $this->newLine();
        $this->info('Testing Cache Operations...');

        $testKey = 'redis_test_' . time();
        $testValue = 'Redis is working!';

        try {
            // Put
            Cache::put($testKey, $testValue, 60);
            $this->info('✅ Cache PUT: SUCCESS');

            // Get
            $retrieved = Cache::get($testKey);
            if ($retrieved === $testValue) {
                $this->info('✅ Cache GET: SUCCESS');
            } else {
                $this->error('❌ Cache GET: FAILED (value mismatch)');
                return 1;
            }

            // Delete
            Cache::forget($testKey);
            $this->info('✅ Cache DELETE: SUCCESS');
        } catch (\Exception $e) {
            $this->error('❌ Cache operations: FAILED');
            $this->error('   Error: ' . $e->getMessage());
            return 1;
        }

        // Test 4: Direct Redis Operations
        $this->newLine();
        $this->info('Testing Direct Redis Operations...');

        try {
            $directKey = 'redis_direct_test_' . time();
            Redis::setex($directKey, 60, $testValue);
            $this->info('✅ Redis SETEX: SUCCESS');

            $directValue = Redis::get($directKey);
            if ($directValue === $testValue) {
                $this->info('✅ Redis GET: SUCCESS');
            } else {
                $this->error('❌ Redis GET: FAILED');
                return 1;
            }

            Redis::del($directKey);
            $this->info('✅ Redis DEL: SUCCESS');
        } catch (\Exception $e) {
            $this->error('❌ Direct Redis operations: FAILED');
            $this->error('   Error: ' . $e->getMessage());
            return 1;
        }

        // Test 5: Redis Info
        $this->newLine();
        $this->info('Redis Server Information:');
        try {
            $info = Redis::info();
            $this->line("   Version: " . ($info['redis_version'] ?? 'N/A'));
            $this->line("   Connected Clients: " . ($info['connected_clients'] ?? 'N/A'));
            $this->line("   Used Memory: " . ($this->formatBytes($info['used_memory'] ?? 0)));
            $this->line("   Total Keys: " . (Redis::dbsize() ?? 'N/A'));
        } catch (\Exception $e) {
            $this->warn('⚠️  Could not retrieve Redis info');
        }

        $this->newLine();
        $this->info('🎉 All Redis tests passed!');
        return 0;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

<?php

namespace App\Console\Commands;

use App\Services\PublicCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * Monitor cache hit/miss rates and invalidation statistics
 */
class CacheMonitorCommand extends Command
{
    protected $signature = 'cache:monitor 
                            {--stats : Show cache statistics}
                            {--keys : Show cached keys by pattern}
                            {--pattern= : Pattern to match keys (e.g., "public_api:match:*")}
                            {--reset : Reset cache statistics}';

    protected $description = 'Monitor public API cache performance and statistics';

    protected PublicCacheService $cacheService;

    public function __construct(PublicCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    public function handle(): int
    {
        if ($this->option('reset')) {
            $this->resetStats();
            return 0;
        }

        if ($this->option('stats')) {
            $this->showStats();
            return 0;
        }

        if ($this->option('keys')) {
            $this->showKeys();
            return 0;
        }

        // Default: show all information
        $this->showStats();
        $this->newLine();
        $this->showKeys();

        return 0;
    }

    protected function showStats(): void
    {
        $stats = $this->cacheService->getCacheStats();

        $this->info('📊 Public API Cache Statistics');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', number_format($stats['total_requests'])],
                ['Cache Hits', number_format($stats['hits'])],
                ['Cache Misses', number_format($stats['misses'])],
                ['Hit Rate', $stats['hit_rate'] . '%'],
                ['Invalidations', number_format($stats['invalidations'])],
                ['Last Reset', $stats['last_reset']],
            ]
        );

        // Show hit rate visualization
        $hitRate = $stats['hit_rate'];
        $barLength = 50;
        $filled = (int) ($hitRate / 100 * $barLength);
        $bar = str_repeat('█', $filled) . str_repeat('░', $barLength - $filled);

        $this->line("Hit Rate: [{$bar}] {$hitRate}%");
        $this->newLine();

        // Performance assessment
        if ($hitRate >= 80) {
            $this->info('✅ Excellent cache performance!');
        } elseif ($hitRate >= 60) {
            $this->comment('⚠️  Good cache performance');
        } elseif ($hitRate >= 40) {
            $this->warn('⚠️  Moderate cache performance - consider reviewing TTLs');
        } else {
            $this->error('❌ Low cache performance - cache may not be effective');
        }
    }

    protected function showKeys(): void
    {
        $pattern = $this->option('pattern') ?? 'public_api:*';

        $this->info("🔍 Cached Keys (Pattern: {$pattern})");
        $this->newLine();

        try {
            $keys = $this->cacheService->getKeysByPattern($pattern);

            if (empty($keys)) {
                $this->warn('No keys found matching pattern: ' . $pattern);
                return;
            }

            $this->info('Found ' . count($keys) . ' keys:');
            $this->newLine();

            // Group keys by prefix for better readability
            $grouped = [];
            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $prefix = implode(':', array_slice($parts, 0, min(3, count($parts))));
                if (!isset($grouped[$prefix])) {
                    $grouped[$prefix] = [];
                }
                $grouped[$prefix][] = $key;
            }

            foreach ($grouped as $prefix => $prefixKeys) {
                $count = count($prefixKeys);
                $this->line("<comment>{$prefix}*</comment> ({$count} keys)");
                if ($count <= 10) {
                    foreach ($prefixKeys as $key) {
                        $this->line("  - {$key}");
                    }
                } else {
                    $this->line("  ... and " . ($count - 10) . " more");
                }
                $this->newLine();
            }

            // Show Redis memory usage if available
            if (config('cache.default') === 'redis') {
                try {
                    $redis = Redis::connection();
                    $info = $redis->info('memory');
                    $usedMemory = $info['used_memory_human'] ?? 'N/A';
                    $this->line("💾 Redis Memory Used: {$usedMemory}");
                } catch (\Exception $e) {
                    // Ignore
                }
            }

        } catch (\Exception $e) {
            $this->error('Failed to retrieve keys: ' . $e->getMessage());
        }
    }

    protected function resetStats(): void
    {
        if ($this->confirm('Are you sure you want to reset cache statistics?', true)) {
            $result = $this->cacheService->resetStats();
            if ($result) {
                $this->info('✅ Cache statistics reset successfully');
            } else {
                $this->error('❌ Failed to reset cache statistics');
            }
        } else {
            $this->info('Statistics reset cancelled');
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class QueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor
                            {--failed : Show only failed jobs}
                            {--processed : Show processed events}
                            {--stats : Show queue statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue jobs and processed events';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $showFailed = $this->option('failed');
        $showProcessed = $this->option('processed');
        $showStats = $this->option('stats');

        if (!$showFailed && !$showProcessed && !$showStats) {
            // Show all by default
            $this->showStats();
            $this->newLine();
            $this->showFailed();
            $this->newLine();
            $this->showProcessed();
        } else {
            if ($showStats) {
                $this->showStats();
            }
            if ($showFailed) {
                $this->showFailed();
            }
            if ($showProcessed) {
                $this->showProcessed();
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Show queue statistics
     */
    protected function showStats(): void
    {
        $this->info('📊 Queue Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $connection = config('queue.default');
            $queues = ['high', 'default', 'low'];

            foreach ($queues as $queue) {
                $size = Queue::size($queue);
                $this->line("Queue '{$queue}': {$size} jobs");
            }

            // Show Redis queue sizes if using Redis
            if ($connection === 'redis') {
                $this->newLine();
                $this->info('Redis Queue Sizes:');
                $redis = app('redis')->connection();

                $queueNames = [
                    'events-high',
                    'events-default',
                    'default',
                    'events-low',
                ];

                foreach ($queueNames as $queueName) {
                    $size = $redis->llen("queues:{$queueName}");
                    $this->line("  {$queueName}: {$size} jobs");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error getting queue stats: {$e->getMessage()}");
        }
    }

    /**
     * Show failed jobs
     */
    protected function showFailed(): void
    {
        $this->info('❌ Failed Jobs');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $failedJobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(10)
                ->get();

            if ($failedJobs->isEmpty()) {
                $this->line('✅ No failed jobs');
                return;
            }

            $this->table(
                ['ID', 'Queue', 'Event Type', 'Failed At', 'Exception'],
                $failedJobs->map(function ($job) {
                    $eventType = 'unknown';

                    // Try to extract event_type from job payload
                    try {
                        $payload = json_decode($job->payload, true);

                        // Laravel stores job data in different formats
                        if (isset($payload['data']['commandName'])) {
                            // Try to extract from serialized command
                            try {
                                $command = unserialize($payload['data']['commandName']);
                                if (is_object($command) && isset($command->event['event_type'])) {
                                    $eventType = $command->event['event_type'];
                                } elseif (is_object($command) && property_exists($command, 'event') && is_array($command->event)) {
                                    $eventType = $command->event['event_type'] ?? 'unknown';
                                }
                            } catch (\Exception $e) {
                                // If unserialize fails, try to extract from JSON
                                if (isset($payload['data']['command'])) {
                                    $eventType = $payload['data']['command'];
                                }
                            }
                        } elseif (isset($payload['displayName'])) {
                            // Extract from display name (e.g., "App\Jobs\ProcessEventJob")
                            $eventType = $payload['displayName'];
                        } elseif (isset($payload['job'])) {
                            $eventType = $payload['job'];
                        }
                    } catch (\Exception $e) {
                        // If all extraction methods fail, use default
                        $eventType = 'unknown';
                    }

                    $exception = $job->exception ? substr($job->exception, 0, 100) . '...' : 'N/A';

                    return [
                        substr($job->uuid, 0, 8) . '...',
                        $job->queue ?? 'default',
                        $eventType,
                        Carbon::parse($job->failed_at)->diffForHumans(),
                        $exception,
                    ];
                })->toArray()
            );

            $totalFailed = DB::table('failed_jobs')->count();
            $this->newLine();
            $this->line("Total failed jobs: {$totalFailed}");
            $this->line("Run 'php artisan queue:failed' to see all failed jobs");
            $this->line("Run 'php artisan queue:retry {id}' to retry a failed job");
        } catch (\Exception $e) {
            $this->error("Error getting failed jobs: {$e->getMessage()}");
        }
    }

    /**
     * Show processed events
     */
    protected function showProcessed(): void
    {
        $this->info('✅ Processed Events');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        try {
            $processedEvents = DB::table('processed_events')
                ->orderBy('processed_at', 'desc')
                ->limit(20)
                ->get();

            if ($processedEvents->isEmpty()) {
                $this->line('No processed events found');
                return;
            }

            $this->table(
                ['Event ID', 'Event Type', 'Processed At'],
                $processedEvents->map(function ($event) {
                    return [
                        substr($event->event_id, 0, 8) . '...',
                        $event->event_type,
                        Carbon::parse($event->processed_at)->format('Y-m-d H:i:s'),
                    ];
                })->toArray()
            );

            $totalProcessed = DB::table('processed_events')->count();
            $byType = DB::table('processed_events')
                ->select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->get();

            $this->newLine();
            $this->line("Total processed events: {$totalProcessed}");
            $this->newLine();
            $this->info('By Event Type:');
            foreach ($byType as $type) {
                $this->line("  {$type->event_type}: {$type->count}");
            }
        } catch (\Exception $e) {
            $this->error("Error getting processed events: {$e->getMessage()}");
            $this->line("Make sure the 'processed_events' table exists. Run migrations if needed.");
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\ProcessEventJob;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TestEventConsumption extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:event-consumption 
                            {event_type : The event type to test (tournament.created or tournament.status.changed)}
                            {--payload= : JSON payload string (optional)}
                            {--queue=default : Queue name to dispatch to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test event consumption by dispatching a test event to the queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventType = $this->argument('event_type');
        $queue = $this->option('queue');
        $payloadJson = $this->option('payload');

        // Build default payloads based on event type
        $payload = $this->buildPayload($eventType, $payloadJson);

        // Build event structure
        $event = [
            'event_id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'service' => 'tournament-service', // Simulating event from tournament-service
            'payload' => $payload,
            'timestamp' => Carbon::now()->utc()->toIso8601String(),
            'version' => '1.0',
            'retry_count' => 0,
            'max_retries' => 3,
        ];

        $this->info("Dispatching test event to queue: {$queue}");
        $this->line("Event Type: {$eventType}");
        $this->line("Event ID: {$event['event_id']}");
        $this->line("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));

        try {
            // Dispatch ProcessEventJob directly to test consumption
            $job = new ProcessEventJob($event);
            $job->onQueue($queue);
            dispatch($job);

            $this->info("✅ Event dispatched successfully!");
            $this->line("");
            $this->line("To verify consumption:");
            $this->line("1. Check queue worker logs: tail -f storage/logs/laravel.log");
            $this->line("2. Check if handler processed the event");
            $this->line("3. Check Redis cache for tournament data (if tournament.created)");
            $this->line("4. Check database for locked teams (if tournament.status.changed with status=completed)");

        } catch (\Exception $e) {
            $this->error("❌ Failed to dispatch event: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Build payload based on event type
     *
     * @param string $eventType
     * @param string|null $payloadJson
     * @return array
     */
    protected function buildPayload(string $eventType, ?string $payloadJson): array
    {
        if ($payloadJson) {
            $payload = json_decode($payloadJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON payload: " . json_last_error_msg());
                exit(1);
            }
            return $payload;
        }

        // Default payloads
        switch ($eventType) {
            case 'tournament.created':
                return [
                    'tournament_id' => 1,
                    'name' => 'Test Tournament',
                    'status' => 'upcoming',
                    'start_date' => Carbon::now()->addDays(30)->toIso8601String(),
                    'end_date' => Carbon::now()->addDays(60)->toIso8601String(),
                    'sport_id' => 1,
                    'created_at' => Carbon::now()->toIso8601String(),
                ];

            case 'tournament.status.changed':
                return [
                    'tournament_id' => 1,
                    'old_status' => 'upcoming',
                    'new_status' => 'completed',
                    'changed_at' => Carbon::now()->toIso8601String(),
                    'transition_reason' => 'test',
                ];

            default:
                return [
                    'test' => true,
                    'timestamp' => Carbon::now()->toIso8601String(),
                ];
        }
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessEventJob;
use Exception;

class QueueEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The event data
     *
     * @var array
     */
    public array $event;

    /**
     * Create a new job instance.
     *
     * @param array $event Event data structure
     */
    public function __construct(array $event)
    {
        $this->event = $event;
        $this->tries = $event['max_retries'] ?? 3;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Update retry count if this is a retry
            if ($this->attempts() > 1) {
                $this->event['retry_count'] = $this->attempts() - 1;
            }

            Log::info('Queue event received, dispatching to ProcessEventJob', [
                'event_id' => $this->event['event_id'] ?? null,
                'event_type' => $this->event['event_type'] ?? null,
                'service' => $this->event['service'] ?? null,
                'attempt' => $this->attempts(),
                'max_retries' => $this->tries,
            ]);

            // Dispatch to ProcessEventJob which will route to handlers
            ProcessEventJob::dispatch($this->event)
                ->onQueue($this->queue ?? 'default');

        } catch (Exception $e) {
            Log::error('Error processing queue event', [
                'event_id' => $this->event['event_id'] ?? null,
                'event_type' => $this->event['event_type'] ?? null,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Queue event job failed permanently', [
            'event_id' => $this->event['event_id'] ?? null,
            'event_type' => $this->event['event_type'] ?? null,
            'service' => $this->event['service'] ?? null,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'final_attempt' => $this->attempts(),
            'max_retries' => $this->tries,
            'event_data' => $this->event,
        ]);

        // Here you could implement additional failure handling:
        // - Store in failed_jobs table (Laravel does this automatically)
        // - Send notification
        // - Store in dead letter queue
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        // Exponential backoff: 60s, 120s, 240s
        return [60, 120, 240];
    }
}

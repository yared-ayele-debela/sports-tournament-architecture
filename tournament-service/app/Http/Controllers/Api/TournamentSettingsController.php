<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentSettings;
use App\Models\Tournament;
use App\Services\Queue\QueuePublisher;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TournamentSettingsController extends Controller
{
    protected QueuePublisher $queuePublisher;

    public function __construct(QueuePublisher $queuePublisher)
    {
        $this->queuePublisher = $queuePublisher;
    }
    /**
     * Display tournament settings.
     */
    public function show(string $tournamentId): JsonResponse
    {
        try {
            // Verify tournament exists
            $tournament = Tournament::find($tournamentId);

            if (!$tournament) {
                return ApiResponse::notFound('Tournament not found');
            }

            $settings = TournamentSettings::where('tournament_id', $tournamentId)->first();

            return ApiResponse::success($settings, 'Tournament settings retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament settings', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve tournament settings', $e);
        }
    }

    /**
     * Create or update tournament settings.
     */
    public function store(Request $request, string $tournamentId): JsonResponse
    {
        try {
            // Verify tournament exists
            $tournament = Tournament::find($tournamentId);

            if (!$tournament) {
                return ApiResponse::notFound('Tournament not found');
            }

            $validated = $request->validate([
                'match_duration' => 'nullable|integer|min:1|max:480',
                'win_rest_time' => 'nullable|integer|min:0|max:1440',
                'daily_start_time' => 'nullable|date_format:H:i',
                'daily_end_time' => 'nullable|date_format:H:i|after:daily_start_time'
            ]);

            // Get old settings if they exist (for update scenario)
            $oldSettings = TournamentSettings::where('tournament_id', $tournamentId)->first();
            $oldData = $oldSettings ? $oldSettings->toArray() : null;

            // Use updateOrCreate to handle both create and update scenarios
            $settings = TournamentSettings::updateOrCreate(
                ['tournament_id' => $tournamentId],
                $validated
            );

            Log::info('Tournament settings saved successfully', [
                'tournament_id' => $tournamentId,
                'settings_id' => $settings->id,
                'is_new' => $oldSettings === null
            ]);

            // Dispatch tournament settings updated event (low priority)
            $this->dispatchTournamentSettingsUpdatedQueueEvent($tournament, $settings, $oldData);

            return ApiResponse::success($settings, 'Tournament settings saved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to save tournament settings', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to save tournament settings', $e);
        }
    }

    /**
     * Dispatch tournament settings updated event to queue (low priority)
     *
     * @param Tournament $tournament
     * @param TournamentSettings $settings
     * @param array|null $oldData
     * @return void
     */
    protected function dispatchTournamentSettingsUpdatedQueueEvent(
        Tournament $tournament,
        TournamentSettings $settings,
        ?array $oldData
    ): void {
        try {
            $this->queuePublisher->dispatchLow('events', [
                'tournament_id' => $tournament->id,
                'id' => $tournament->id,
                'settings_id' => $settings->id,
                'match_duration' => $settings->match_duration,
                'win_rest_time' => $settings->win_rest_time,
                'daily_start_time' => $settings->daily_start_time?->format('H:i'),
                'daily_end_time' => $settings->daily_end_time?->format('H:i'),
                'old_data' => $oldData,
                'updated_at' => now()->toIso8601String(),
            ], 'tournament.settings.updated');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch tournament settings updated queue event', [
                'tournament_id' => $tournament->id,
                'settings_id' => $settings->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

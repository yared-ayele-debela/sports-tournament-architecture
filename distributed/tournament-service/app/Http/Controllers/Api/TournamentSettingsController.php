<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TournamentSettings;
use App\Models\Tournament;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TournamentSettingsController extends Controller
{
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

            // Use updateOrCreate to handle both create and update scenarios
            $settings = TournamentSettings::updateOrCreate(
                ['tournament_id' => $tournamentId],
                $validated
            );

            Log::info('Tournament settings saved successfully', [
                'tournament_id' => $tournamentId,
                'settings_id' => $settings->id
            ]);

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
}

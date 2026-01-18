<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentSettings;
use App\Services\HttpClients\AuthServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TournamentSettingsController extends Controller
{
    protected AuthServiceClient $authClient;

    /**
     * Create a new TournamentSettingsController instance.
     */
    public function __construct(AuthServiceClient $authClient)
    {
        $this->authClient = $authClient;
    }

    /**
     * Display tournament settings.
     */
    public function show(string $tournamentId): JsonResponse
    {
        try {
            $tournament = Tournament::find($tournamentId);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $settings = $tournament->settings;

            if (!$settings) {
                return response()->json([
                    'success' => true,
                    'message' => 'No settings found for tournament',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament settings retrieved successfully',
                'data' => $settings
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving tournament settings', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament settings',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create or update tournament settings.
     */
    public function store(Request $request, string $tournamentId): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_tournaments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            $tournament = Tournament::find($tournamentId);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'match_duration' => 'required|integer|min:1|max:480', // Max 8 hours
                'win_rest_time' => 'required|integer|min:0|max:120', // Max 2 hours
                'daily_start_time' => 'required|date_format:H:i',
                'daily_end_time' => 'required|date_format:H:i|after:daily_start_time'
            ]);

            // Update or create settings
            $settings = $tournament->settings()->updateOrCreate(
                ['tournament_id' => $tournamentId],
                $validated
            );

            Log::info('Tournament settings saved successfully', [
                'tournament_id' => $tournamentId,
                'settings_id' => $settings->id,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament settings saved successfully',
                'data' => $settings
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving tournament settings', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save tournament settings',
                'error' => 'Internal server error'
            ], 500);
        }
    }
}

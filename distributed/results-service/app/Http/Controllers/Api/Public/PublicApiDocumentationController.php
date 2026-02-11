<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Throwable;

/**
 * Public API Documentation Controller for Results Service
 */
class PublicApiDocumentationController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected string $serviceName = 'results-service';
    protected string $serviceVersion = '1.0.0';
    protected string $baseUrl;

    public function __construct(PublicCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->baseUrl = config('app.url', env('APP_URL', 'http://results-service:8005'));
    }

    /**
     * Get API documentation
     *
     * GET /api/public/docs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('api:documentation');
            $tags = ['public-api', 'documentation'];
            $ttl = 3600; // 1 hour

            $documentation = $this->cacheService->remember($cacheKey, $ttl, function () {
                return $this->generateDocumentation();
            }, $tags, 'static');

            return $this->successResponse($documentation, 'API documentation', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to generate API documentation', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to generate API documentation', 500);
        }
    }

    /**
     * Generate API documentation from routes
     */
    protected function generateDocumentation(): array
    {
        $routes = $this->getPublicRoutes();
        $endpoints = [];

        foreach ($routes as $route) {
            $endpoint = $this->extractEndpointInfo($route);
            if ($endpoint) {
                $endpoints[] = $endpoint;
            }
        }

        usort($endpoints, function ($a, $b) {
            return strcmp($a['path'], $b['path']);
        });

        return [
            'service' => $this->serviceName,
            'version' => $this->serviceVersion,
            'base_url' => $this->baseUrl,
            'generated_at' => now()->toISOString(),
            'endpoints' => $endpoints,
            'total_endpoints' => count($endpoints),
        ];
    }

    /**
     * Get all public API routes
     */
    protected function getPublicRoutes(): array
    {
        $routes = [];
        $allRoutes = Route::getRoutes();

        foreach ($allRoutes as $route) {
            $uri = $route->uri();
            $name = $route->getName();

            if (str_starts_with($uri, 'api/public') && $name && str_starts_with($name, 'public.')) {
                if ($name !== 'public.docs') {
                    $routes[] = $route;
                }
            }
        }

        return $routes;
    }

    /**
     * Extract endpoint information from route
     */
    protected function extractEndpointInfo($route): ?array
    {
        try {
            $uri = $route->uri();
            $methods = $route->methods();
            $method = in_array('GET', $methods) ? 'GET' : $methods[0] ?? 'GET';
            $name = $route->getName();
            $action = $route->getAction();

            $controller = $action['controller'] ?? null;
            if (!$controller || !is_string($controller)) {
                return null;
            }

            [$controllerClass, $methodName] = explode('@', $controller);

            $pathParameters = $this->extractPathParameters($uri);
            $queryParameters = $this->extractQueryParameters($controllerClass, $methodName);
            $description = $this->extractDescription($controllerClass, $methodName);
            $responseExample = $this->generateResponseExample($name);

            return [
                'method' => $method,
                'path' => '/' . $uri,
                'name' => $name,
                'description' => $description,
                'parameters' => array_merge($pathParameters, $queryParameters),
                'response_example' => $responseExample,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Extract path parameters from URI
     */
    protected function extractPathParameters(string $uri): array
    {
        $parameters = [];
        preg_match_all('/\{(\w+)\}/', $uri, $matches);

        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'type' => 'integer',
                'required' => true,
                'in' => 'path',
                'description' => $this->getParameterDescription($param),
            ];
        }

        return $parameters;
    }

    /**
     * Extract query parameters from controller method
     */
    protected function extractQueryParameters(string $controllerClass, string $methodName): array
    {
        $parameters = [];

        try {
            if (!class_exists($controllerClass)) {
                return $parameters;
            }

            $reflection = new ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($methodName)) {
                return $parameters;
            }

            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();

            if ($docComment && preg_match('/Query params: (.+)/', $docComment, $matches)) {
                $paramsString = $matches[1] ?? '';
                if (preg_match_all('/(\w+)=([^&]+)/', $paramsString, $paramMatches)) {
                    foreach ($paramMatches[1] as $index => $paramName) {
                        $paramValue = $paramMatches[2][$index] ?? '';
                        $parameters[] = [
                            'name' => $paramName,
                            'type' => $this->inferParameterType($paramName, $paramValue),
                            'required' => false,
                            'in' => 'query',
                            'description' => $this->getParameterDescription($paramName),
                            'example' => $paramValue,
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            // Silently fail
        }

        return $parameters;
    }

    /**
     * Extract description from docblock
     */
    protected function extractDescription(string $controllerClass, string $methodName): string
    {
        try {
            if (!class_exists($controllerClass)) {
                return '';
            }

            $reflection = new ReflectionClass($controllerClass);
            if (!$reflection->hasMethod($methodName)) {
                return '';
            }

            $method = $reflection->getMethod($methodName);
            $docComment = $method->getDocComment();

            if ($docComment && preg_match('/\/\*\*\s*\*\s*(.+?)(?:\n|$)/', $docComment, $matches)) {
                return trim($matches[1]);
            }
        } catch (Throwable $e) {
            // Silently fail
        }

        return '';
    }

    /**
     * Generate example response
     */
    protected function generateResponseExample(string $routeName): array
    {
        $examples = [
            'public.tournaments.standings.index' => [
                'success' => true,
                'message' => 'Tournament standings retrieved successfully',
                'data' => [
                    'standings' => [
                        [
                            'position' => 1,
                            'team' => ['id' => 1, 'name' => 'Team A'],
                            'played' => 8,
                            'won' => 5,
                            'drawn' => 2,
                            'lost' => 1,
                            'goals_for' => 15,
                            'goals_against' => 8,
                            'goal_difference' => 7,
                            'points' => 17,
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.tournaments.statistics.index' => [
                'success' => true,
                'message' => 'Tournament statistics retrieved successfully',
                'data' => [
                    'tournament_id' => 1,
                    'total_matches' => 8,
                    'total_goals' => 42,
                    'average_goals_per_match' => 5.25,
                    'teams_participating' => 8,
                    'top_scorers' => [],
                    'best_defense' => null,
                    'best_attack' => null,
                ],
                'cached' => true,
            ],
            'public.tournaments.top-scorers.index' => [
                'success' => true,
                'message' => 'Top scorers retrieved successfully',
                'data' => [
                    'scorers' => [
                        [
                            'player_id' => 1,
                            'player_name' => 'John Doe',
                            'team_id' => 1,
                            'team_name' => 'Team A',
                            'goals' => 10,
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.teams.standing.show' => [
                'success' => true,
                'message' => 'Team standing retrieved successfully',
                'data' => [
                    'team' => ['id' => 1, 'name' => 'Team A'],
                    'tournament' => ['id' => 1, 'name' => 'World Cup 2026'],
                    'position' => 1,
                    'played' => 8,
                    'won' => 5,
                    'drawn' => 2,
                    'lost' => 1,
                    'points' => 17,
                    'form' => 'WWDLW',
                ],
                'cached' => true,
            ],
        ];

        return $examples[$routeName] ?? [
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => [],
            'cached' => true,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Infer parameter type
     */
    protected function inferParameterType(string $name, string $value): string
    {
        if (in_array($name, ['tournamentId', 'teamId'])) {
            return 'integer';
        }
        return is_numeric($value) ? 'integer' : 'string';
    }

    /**
     * Get parameter description
     */
    protected function getParameterDescription(string $param): string
    {
        $descriptions = [
            'tournamentId' => 'Tournament ID',
            'teamId' => 'Team ID',
        ];

        return $descriptions[$param] ?? ucfirst(str_replace('_', ' ', $param));
    }
}

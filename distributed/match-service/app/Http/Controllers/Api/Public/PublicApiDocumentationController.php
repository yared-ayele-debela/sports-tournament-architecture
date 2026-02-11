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
 * Public API Documentation Controller for Match Service
 */
class PublicApiDocumentationController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected string $serviceName = 'match-service';
    protected string $serviceVersion = '1.0.0';
    protected string $baseUrl;

    public function __construct(PublicCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->baseUrl = config('app.url', env('APP_URL', 'http://match-service:8004'));
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
            'public.matches.live' => [
                'success' => true,
                'message' => 'Live matches retrieved successfully',
                'data' => [
                    'matches' => [
                        [
                            'id' => 1,
                            'home_team' => ['id' => 1, 'name' => 'Team A'],
                            'away_team' => ['id' => 2, 'name' => 'Team B'],
                            'home_score' => 2,
                            'away_score' => 1,
                            'current_minute' => 75,
                            'status' => 'in_progress',
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.tournaments.matches.index' => [
                'success' => true,
                'message' => 'Tournament matches retrieved successfully',
                'data' => [
                    'matches' => [
                        [
                            'id' => 1,
                            'home_team' => ['id' => 1, 'name' => 'Team A'],
                            'away_team' => ['id' => 2, 'name' => 'Team B'],
                            'match_date' => '2026-06-15T17:00:00Z',
                            'status' => 'completed',
                            'home_score' => 2,
                            'away_score' => 1,
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.matches.show' => [
                'success' => true,
                'message' => 'Match details retrieved successfully',
                'data' => [
                    'id' => 1,
                    'tournament' => ['id' => 1, 'name' => 'World Cup 2026'],
                    'home_team' => ['id' => 1, 'name' => 'Team A'],
                    'away_team' => ['id' => 2, 'name' => 'Team B'],
                    'match_date' => '2026-06-15T17:00:00Z',
                    'status' => 'completed',
                    'home_score' => 2,
                    'away_score' => 1,
                ],
                'cached' => true,
            ],
            'public.matches.events.index' => [
                'success' => true,
                'message' => 'Match events retrieved successfully',
                'data' => [
                    'events' => [
                        [
                            'id' => 1,
                            'minute' => 15,
                            'event_type' => 'goal',
                            'team' => ['id' => 1, 'name' => 'Team A'],
                            'player' => ['id' => 1, 'full_name' => 'John Doe'],
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.matches.today' => [
                'success' => true,
                'message' => 'Today\'s matches retrieved successfully',
                'data' => [
                    'matches' => [],
                ],
                'cached' => true,
            ],
            'public.matches.upcoming' => [
                'success' => true,
                'message' => 'Upcoming matches retrieved successfully',
                'data' => [
                    'matches' => [],
                ],
                'cached' => true,
            ],
            'public.search.matches' => [
                'success' => true,
                'message' => 'Matches found',
                'data' => [
                    'query' => 'Team A',
                    'matches' => [
                        [
                            'id' => 1,
                            'relevance' => 100.0,
                        ]
                    ],
                    'total' => 1,
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
        if (in_array($name, ['id', 'tournamentId', 'status', 'date', 'limit'])) {
            return in_array($name, ['limit']) ? 'integer' : 'string';
        }
        return is_numeric($value) ? 'integer' : 'string';
    }

    /**
     * Get parameter description
     */
    protected function getParameterDescription(string $param): string
    {
        $descriptions = [
            'id' => 'Match ID',
            'tournamentId' => 'Tournament ID',
            'status' => 'Match status (scheduled, in_progress, completed, cancelled)',
            'date' => 'Match date (YYYY-MM-DD)',
            'limit' => 'Number of results per page',
            'q' => 'Search query',
        ];

        return $descriptions[$param] ?? ucfirst(str_replace('_', ' ', $param));
    }
}

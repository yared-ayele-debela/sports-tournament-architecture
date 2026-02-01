<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Public API Documentation Controller
 *
 * Auto-generates API documentation from registered routes.
 */
class PublicApiDocumentationController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected string $serviceName = 'tournament-service';
    protected string $serviceVersion = '1.0.0';
    protected string $baseUrl;

    public function __construct(PublicCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->baseUrl = config('app.url', 'http://localhost:8002');
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
            $ttl = 3600; // 1 hour (documentation rarely changes)

            $documentation = $this->cacheService->remember($cacheKey, $ttl, function () {
                return $this->generateDocumentation();
            }, $tags, 'static');

            return $this->successResponse($documentation, 'API documentation', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to generate API documentation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

        // Sort endpoints by path
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

            // Only include public API routes
            if (str_starts_with($uri, 'api/public') && $name && str_starts_with($name, 'public.')) {
                // Exclude docs endpoint itself
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

            // Get controller and method
            $controller = $action['controller'] ?? null;
            if (!$controller || !is_string($controller)) {
                return null;
            }

            [$controllerClass, $methodName] = explode('@', $controller);

            // Extract path parameters
            $pathParameters = $this->extractPathParameters($uri);

            // Extract query parameters from controller method
            $queryParameters = $this->extractQueryParameters($controllerClass, $methodName);

            // Get description from docblock
            $description = $this->extractDescription($controllerClass, $methodName);

            // Generate example response
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
            Log::debug('Failed to extract endpoint info', [
                'route' => $route->getName(),
                'error' => $e->getMessage(),
            ]);
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

            // Extract from docblock
            if ($docComment && preg_match('/Query params: (.+)/', $docComment, $matches)) {
                $paramsString = $matches[1] ?? '';
                // Parse ?status=ongoing&sport_id=1&limit=20 format
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

            // Also check validation rules if method uses Request validation
            $parameters = array_merge($parameters, $this->extractFromValidationRules($controllerClass, $methodName));

        } catch (Throwable $e) {
            Log::debug('Failed to extract query parameters', [
                'controller' => $controllerClass,
                'method' => $methodName,
                'error' => $e->getMessage(),
            ]);
        }

        return $parameters;
    }

    /**
     * Extract parameters from validation rules
     */
    protected function extractFromValidationRules(string $controllerClass, string $methodName): array
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
            $source = file_get_contents($method->getFileName());
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            $methodSource = implode("\n", array_slice(explode("\n", $source), $startLine - 1, $endLine - $startLine + 1));

            // Look for validation rules: 'param' => 'rule1|rule2'
            if (preg_match_all("/['\"](\w+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $methodSource, $matches)) {
                foreach ($matches[1] as $index => $paramName) {
                    $rule = $matches[2][$index] ?? '';

                    // Skip if already added
                    if (in_array($paramName, array_column($parameters, 'name'))) {
                        continue;
                    }

                    // Only process if it looks like a validation rule (contains validation keywords)
                    if (!preg_match('/(nullable|required|integer|string|in:|min:|max:)/', $rule)) {
                        continue;
                    }

                    $type = $this->inferTypeFromRule($rule);
                    $options = $this->extractOptionsFromRule($rule);

                    $parameters[] = [
                        'name' => $paramName,
                        'type' => $type,
                        'required' => str_contains($rule, 'required') && !str_contains($rule, 'nullable'),
                        'in' => 'query',
                        'description' => $this->getParameterDescription($paramName),
                        'options' => $options,
                    ];
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

            if ($docComment) {
                // Extract first line of description
                if (preg_match('/\/\*\*\s*\*\s*(.+?)(?:\n|$)/', $docComment, $matches)) {
                    return trim($matches[1]);
                }
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
            'public.tournaments.index' => [
                'success' => true,
                'message' => 'Tournaments retrieved successfully',
                'data' => [
                    'tournaments' => [
                        [
                            'id' => 1,
                            'name' => 'World Cup 2026',
                            'sport' => ['id' => 1, 'name' => 'Soccer'],
                            'start_date' => '2026-06-01',
                            'end_date' => '2026-07-15',
                            'status' => 'ongoing',
                        ]
                    ],
                    'total' => 1,
                ],
                'cached' => true,
                'cache_expires_at' => '2026-02-01T16:00:00Z',
                'timestamp' => '2026-02-01T15:00:00Z',
            ],
            'public.tournaments.show' => [
                'success' => true,
                'message' => 'Tournament details retrieved successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'World Cup 2026',
                    'sport' => ['id' => 1, 'name' => 'Soccer'],
                    'location' => 'National Stadium',
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-07-15',
                    'status' => 'ongoing',
                    'team_count' => 32,
                    'match_count' => 64,
                ],
                'cached' => true,
                'cache_expires_at' => '2026-02-01T16:00:00Z',
                'timestamp' => '2026-02-01T15:00:00Z',
            ],
            'public.tournaments.featured' => [
                'success' => true,
                'message' => 'Featured tournaments retrieved successfully',
                'data' => [
                    'tournaments' => [
                        [
                            'id' => 1,
                            'name' => 'World Cup 2026',
                            'sport' => ['id' => 1, 'name' => 'Soccer'],
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.tournaments.upcoming' => [
                'success' => true,
                'message' => 'Upcoming tournaments retrieved successfully',
                'data' => [
                    'tournaments' => [
                        [
                            'id' => 1,
                            'name' => 'World Cup 2026',
                            'start_date' => '2026-06-01',
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.sports.index' => [
                'success' => true,
                'message' => 'Sports retrieved successfully',
                'data' => [
                    'sports' => [
                        [
                            'id' => 1,
                            'name' => 'Soccer',
                            'tournament_count' => 5,
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.venues.index' => [
                'success' => true,
                'message' => 'Venues retrieved successfully',
                'data' => [
                    'venues' => [
                        [
                            'id' => 1,
                            'name' => 'National Stadium',
                            'location' => 'Addis Ababa',
                            'capacity' => 50000,
                        ]
                    ],
                ],
                'cached' => true,
            ],
            'public.venues.show' => [
                'success' => true,
                'message' => 'Venue details retrieved successfully',
                'data' => [
                    'id' => 1,
                    'name' => 'National Stadium',
                    'location' => 'Addis Ababa',
                    'capacity' => 50000,
                ],
                'cached' => true,
            ],
            'public.search.tournaments' => [
                'success' => true,
                'message' => 'Tournaments found',
                'data' => [
                    'query' => 'World Cup',
                    'tournaments' => [
                        [
                            'id' => 1,
                            'name' => 'World Cup 2026',
                            'relevance' => 100.0,
                        ]
                    ],
                    'total' => 1,
                ],
                'cached' => true,
            ],
            'public.search.all' => [
                'success' => true,
                'message' => 'Search results',
                'data' => [
                    'query' => 'World Cup',
                    'tournaments' => [],
                    'teams' => [],
                    'matches' => [],
                    'total' => 0,
                ],
                'cached' => true,
            ],
        ];

        // Return example for route name if available
        if (isset($examples[$routeName])) {
            return $examples[$routeName];
        }

        // Default response structure
        return [
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => [],
            'cached' => true,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Infer parameter type from name and value
     */
    protected function inferParameterType(string $name, string $value): string
    {
        if (in_array($name, ['id', 'sport_id', 'tournament_id', 'team_id', 'venue_id', 'limit', 'page'])) {
            return 'integer';
        }
        if (in_array($name, ['status', 'q', 'query'])) {
            return 'string';
        }
        if (is_numeric($value)) {
            return 'integer';
        }
        return 'string';
    }

    /**
     * Infer type from validation rule
     */
    protected function inferTypeFromRule(string $rule): string
    {
        if (str_contains($rule, 'integer')) {
            return 'integer';
        }
        if (str_contains($rule, 'string')) {
            return 'string';
        }
        if (str_contains($rule, 'date')) {
            return 'string';
        }
        return 'string';
    }

    /**
     * Extract options from validation rule
     */
    protected function extractOptionsFromRule(string $rule): ?array
    {
        if (preg_match('/in:([^,)]+)/', $rule, $matches)) {
            return array_map('trim', explode(',', $matches[1]));
        }
        return null;
    }

    /**
     * Get parameter description
     */
    protected function getParameterDescription(string $param): string
    {
        $descriptions = [
            'id' => 'Resource ID',
            'status' => 'Tournament status',
            'sport_id' => 'Sport ID',
            'tournament_id' => 'Tournament ID',
            'team_id' => 'Team ID',
            'venue_id' => 'Venue ID',
            'limit' => 'Number of results per page',
            'page' => 'Page number',
            'q' => 'Search query',
            'query' => 'Search query',
        ];

        return $descriptions[$param] ?? ucfirst(str_replace('_', ' ', $param));
    }
}

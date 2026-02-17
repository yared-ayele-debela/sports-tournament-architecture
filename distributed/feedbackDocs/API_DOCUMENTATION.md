# Self-Documenting API Endpoints

## Overview

Each service now includes a self-documenting API endpoint that automatically generates comprehensive API documentation from registered routes.

## Endpoint

**GET `/api/public/docs`**

Available in all services:
- Tournament Service: `http://localhost:8002/api/public/docs`
- Team Service: `http://localhost:8003/api/public/docs`
- Match Service: `http://localhost:8004/api/public/docs`
- Results Service: `http://localhost:8005/api/public/docs`

## Response Format

```json
{
  "success": true,
  "message": "API documentation",
  "data": {
    "service": "tournament-service",
    "version": "1.0.0",
    "base_url": "http://localhost:8002",
    "generated_at": "2026-02-01T15:00:00Z",
    "total_endpoints": 8,
    "endpoints": [
      {
        "method": "GET",
        "path": "/api/public/tournaments",
        "name": "public.tournaments.index",
        "description": "List all public tournaments",
        "parameters": [
          {
            "name": "status",
            "type": "string",
            "required": false,
            "in": "query",
            "description": "Tournament status",
            "options": ["ongoing", "completed"]
          },
          {
            "name": "sport_id",
            "type": "integer",
            "required": false,
            "in": "query",
            "description": "Sport ID"
          },
          {
            "name": "limit",
            "type": "integer",
            "required": false,
            "in": "query",
            "description": "Number of results per page"
          }
        ],
        "response_example": {
          "success": true,
          "message": "Tournaments retrieved successfully",
          "data": {
            "tournaments": [
              {
                "id": 1,
                "name": "World Cup 2026",
                "sport": {
                  "id": 1,
                  "name": "Soccer"
                },
                "start_date": "2026-06-01",
                "end_date": "2026-07-15",
                "status": "ongoing"
              }
            ],
            "total": 1
          },
          "cached": true,
          "cache_expires_at": "2026-02-01T16:00:00Z",
          "timestamp": "2026-02-01T15:00:00Z"
        }
      }
    ]
  },
  "cached": true,
  "cache_expires_at": "2026-02-01T16:00:00Z"
}
```

## Features

### 1. Auto-Generation from Routes

- **Scans all public routes** automatically
- **Extracts route information:**
  - HTTP method
  - Path with parameters
  - Route name
  - Controller and method

### 2. Parameter Extraction

- **Path Parameters:**
  - Automatically detected from URI patterns (`{id}`, `{tournamentId}`, etc.)
  - Type inferred (integer for IDs)
  - Marked as required

- **Query Parameters:**
  - Extracted from controller docblocks (`Query params: ?status=ongoing&limit=20`)
  - Extracted from validation rules
  - Type inferred from validation rules
  - Options extracted from `in:` validation rules

### 3. Description Extraction

- Extracts from controller method docblocks
- First line of docblock used as description
- Example:
  ```php
  /**
   * List all public tournaments
   *
   * GET /api/public/tournaments
   * Query params: ?status=ongoing&sport_id=1&limit=20
   */
  ```

### 4. Example Responses

- Predefined examples for each endpoint
- Realistic sample data
- Includes full response structure with metadata

### 5. Caching

- Documentation cached for 1 hour
- Tags: `['public-api', 'documentation']`
- Reduces overhead of route scanning

## Implementation Details

### Route Scanning

```php
protected function getPublicRoutes(): array
{
    $routes = [];
    $allRoutes = Route::getRoutes();

    foreach ($allRoutes as $route) {
        $uri = $route->uri();
        $name = $route->getName();

        // Only include public API routes
        if (str_starts_with($uri, 'api/public') && 
            $name && 
            str_starts_with($name, 'public.')) {
            if ($name !== 'public.docs') {
                $routes[] = $route;
            }
        }
    }

    return $routes;
}
```

### Parameter Extraction

1. **Path Parameters:**
   - Uses regex to find `{param}` patterns
   - Infers type from parameter name
   - Marks as required

2. **Query Parameters:**
   - Parses docblock comments
   - Extracts from validation rules
   - Infers type and options

### Example Response Generation

- Predefined examples for known routes
- Falls back to default structure
- Includes realistic sample data

## Usage Examples

### Get Tournament Service Documentation

```bash
curl http://localhost:8002/api/public/docs
```

### Get Team Service Documentation

```bash
curl http://localhost:8003/api/public/docs
```

### Get Match Service Documentation

```bash
curl http://localhost:8004/api/public/docs
```

### Get Results Service Documentation

```bash
curl http://localhost:8005/api/public/docs
```

## Benefits

1. **Always Up-to-Date:** Documentation is generated from actual routes
2. **No Manual Maintenance:** Changes to routes automatically reflect in docs
3. **Consistent Format:** All services use the same documentation structure
4. **Developer-Friendly:** Easy to discover available endpoints
5. **Example-Driven:** Includes realistic response examples

## Customization

### Adding More Examples

Edit the `generateResponseExample()` method in each service's `PublicApiDocumentationController`:

```php
protected function generateResponseExample(string $routeName): array
{
    $examples = [
        'public.tournaments.index' => [
            // Your custom example
        ],
        // Add more examples...
    ];

    return $examples[$routeName] ?? $this->getDefaultExample();
}
```

### Improving Parameter Extraction

Enhance the `extractQueryParameters()` method to:
- Parse more validation rule types
- Extract from request classes
- Add parameter constraints (min, max, etc.)

## Testing

```bash
# Test documentation endpoint
curl http://localhost:8002/api/public/docs | jq

# Verify all endpoints are documented
curl http://localhost:8002/api/public/docs | jq '.data.total_endpoints'

# Check specific endpoint documentation
curl http://localhost:8002/api/public/docs | jq '.data.endpoints[] | select(.name == "public.tournaments.index")'
```

## Files Created

### Tournament Service
- `app/Http/Controllers/Api/Public/PublicApiDocumentationController.php`
- Route added: `GET /api/public/docs`

### Team Service
- `app/Http/Controllers/Api/Public/PublicApiDocumentationController.php`
- Route added: `GET /api/public/docs`

### Match Service
- `app/Http/Controllers/Api/Public/PublicApiDocumentationController.php`
- Route added: `GET /api/public/docs`

### Results Service
- `app/Http/Controllers/Api/Public/PublicApiDocumentationController.php`
- Route added: `GET /api/public/docs`

## Future Enhancements

1. **OpenAPI/Swagger Export:** Generate OpenAPI 3.0 specification
2. **Interactive Documentation:** HTML/UI for browsing endpoints
3. **Request Examples:** Include example requests with curl commands
4. **Authentication Info:** Document authentication requirements
5. **Rate Limit Info:** Include rate limit details in documentation
6. **Error Responses:** Document possible error responses

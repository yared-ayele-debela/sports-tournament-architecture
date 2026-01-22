<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

abstract class ServiceClient
{
    protected Client $httpClient;
    protected string $baseUrl;
    protected int $retries = 3;
    protected int $cacheTtl = 300; // 5 minutes default

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'connect_timeout' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a GET request with caching and retry logic
     */
    protected function get(string $endpoint, array $params = [], array $cacheTags = [], ?int $ttl = null): array
    {
        $cacheKey = $this->generateCacheKey('GET', $endpoint, $params);
        
        if (!empty($cacheTags)) {
            return Cache::tags($cacheTags)->remember($cacheKey, $ttl ?? $this->cacheTtl, function () use ($endpoint, $params) {
                return $this->makeRequest('GET', $endpoint, $params);
            });
        }

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Make a POST request with retry logic
     */
    protected function post(string $endpoint, array $data = [], array $cacheTags = []): array
    {
        $response = $this->makeRequest('POST', $endpoint, [], $data);
        
        // Invalidate cache tags if provided
        if (!empty($cacheTags)) {
            Cache::tags($cacheTags)->flush();
        }

        return $response;
    }

    /**
     * Make an async GET request
     */
    protected function getAsync(string $endpoint, array $params = []): PromiseInterface
    {
        return $this->httpClient->getAsync($endpoint, ['query' => $params]);
    }

    /**
     * Make the actual HTTP request with retry logic
     */
    private function makeRequest(string $method, string $endpoint, array $params = [], array $data = []): array
    {
        $options = [];
        
        if ($method === 'GET' && !empty($params)) {
            $options['query'] = $params;
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            $options['json'] = $data;
        }

        $attempt = 0;
        
        while ($attempt < $this->retries) {
            try {
                $response = $this->httpClient->request($method, $endpoint, $options);
                
                return [
                    'success' => true,
                    'data' => json_decode($response->getBody()->getContents(), true),
                    'status' => $response->getStatusCode(),
                ];
            } catch (RequestException $e) {
                $attempt++;
                
                if ($attempt >= $this->retries) {
                    Log::error("Service request failed after {$this->retries} attempts", [
                        'service' => class_basename($this),
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500,
                    ];
                }

                // Exponential backoff
                usleep(pow(2, $attempt) * 100000); // 100ms, 200ms, 400ms
            }
        }

        return [
            'success' => false,
            'error' => 'Max retries exceeded',
            'status' => 500,
        ];
    }

    /**
     * Generate cache key for requests
     */
    private function generateCacheKey(string $method, string $endpoint, array $params = []): string
    {
        $key = sprintf(
            '%s:%s:%s',
            class_basename($this),
            $method,
            str_replace('/', '.', ltrim($endpoint, '/'))
        );

        if (!empty($params)) {
            $key .= ':' . md5(serialize($params));
        }

        return $key;
    }

    /**
     * Handle multiple concurrent requests
     */
    protected function batchRequest(array $requests): array
    {
        $promises = [];
        
        foreach ($requests as $key => $request) {
            $method = $request['method'] ?? 'GET';
            $endpoint = $request['endpoint'];
            $params = $request['params'] ?? [];
            
            if ($method === 'GET') {
                $promises[$key] = $this->getAsync($endpoint, $params);
            }
        }

        $results = [];
        $responses = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

        foreach ($responses as $key => $response) {
            if ($response['state'] === 'fulfilled') {
                $results[$key] = [
                    'success' => true,
                    'data' => json_decode($response['value']->getBody()->getContents(), true),
                ];
            } else {
                $results[$key] = [
                    'success' => false,
                    'error' => $response['reason']->getMessage(),
                ];
            }
        }

        return $results;
    }
}

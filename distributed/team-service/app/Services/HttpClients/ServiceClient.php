<?php

namespace App\Services\HttpClients;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

abstract class ServiceClient
{
    protected Client $client;
    protected string $baseUrl;
    protected ?string $jwtToken = null;
    protected int $timeout = 5;
    protected int $maxRetries = 3;
    protected int $retryDelay = 100; // milliseconds

    /**
     * Create a new service client instance.
     */
    public function __construct(string $baseUrl, ?string $jwtToken = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->jwtToken = $jwtToken;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Set JWT token for authentication.
     */
    public function setJwtToken(string $token): void
    {
        $this->jwtToken = $token;
    }

    /**
     * Get JWT token.
     */
    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    /**
     * Clear JWT token.
     */
    public function clearJwtToken(): void
    {
        $this->jwtToken = null;
    }

    /**
     * Make a GET request with retry logic.
     */
    protected function get(string $endpoint, array $query = []): ?array
    {
        return $this->makeRequest('GET', $endpoint, null, $query);
    }

    /**
     * Make a POST request with retry logic.
     */
    protected function post(string $endpoint, ?array $data = null, array $query = []): ?array
    {
        return $this->makeRequest('POST', $endpoint, $data, $query);
    }

    /**
     * Make a PUT request with retry logic.
     */
    protected function put(string $endpoint, ?array $data = null, array $query = []): ?array
    {
        return $this->makeRequest('PUT', $endpoint, $data, $query);
    }

    /**
     * Make a DELETE request with retry logic.
     */
    protected function delete(string $endpoint, array $query = []): ?array
    {
        return $this->makeRequest('DELETE', $endpoint, null, $query);
    }

    /**
     * Make HTTP request with retry logic and error handling.
     */
    protected function makeRequest(string $method, string $endpoint, ?array $data = null, array $query = []): ?array
    {
        $url = $this->buildUrl($endpoint, $query);
        $options = $this->buildRequestOptions($data);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->client->request($method, $url, $options);
                return $this->parseResponse($response);
            } catch (RequestException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $this->logRetry($method, $url, $attempt, $e);
                    usleep($this->retryDelay * 1000); // Convert to microseconds
                } else {
                    $this->logFailure($method, $url, $e);
                }
            } catch (GuzzleException $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    $this->logRetry($method, $url, $attempt, $e);
                    usleep($this->retryDelay * 1000);
                } else {
                    $this->logFailure($method, $url, $e);
                }
            } catch (\Exception $e) {
                $this->logError($method, $url, $e);
                return null;
            }
        }

        return null;
    }

    /**
     * Build full URL with query parameters.
     */
    protected function buildUrl(string $endpoint, array $query = []): string
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        
        return $url;
    }

    /**
     * Build request options with authentication.
     */
    protected function buildRequestOptions(?array $data = null): array
    {
        $options = [];

        if ($data !== null) {
            $options['json'] = $data;
        }

        if ($this->jwtToken) {
            $options['headers']['Authorization'] = "Bearer {$this->jwtToken}";
        }

        return $options;
    }

    /**
     * Parse HTTP response and return data.
     */
    protected function parseResponse(ResponseInterface $response): ?array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError('PARSE_ERROR', $body, new \Exception('JSON decode error: ' . json_last_error_msg()));
            return null;
        }

        // Log response for debugging
        $this->logResponse($statusCode, $data);

        return $data;
    }

    /**
     * Log retry attempt.
     */
    protected function logRetry(string $method, string $url, int $attempt, \Exception $e): void
    {
        Log::warning("Service client retry attempt {$attempt}", [
            'service' => static::class,
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'retry_delay' => $this->retryDelay,
        ]);
    }

    /**
     * Log failed request after all retries.
     */
    protected function logFailure(string $method, string $url, \Exception $e): void
    {
        Log::error("Service client request failed after {$this->maxRetries} attempts", [
            'service' => static::class,
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'max_retries' => $this->maxRetries,
        ]);
    }

    /**
     * Log general error.
     */
    protected function logError(string $type, string $context, \Exception $e): void
    {
        Log::error("Service client error: {$type}", [
            'service' => static::class,
            'context' => $context,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Log successful response.
     */
    protected function logResponse(int $statusCode, array $data): void
    {
        Log::debug("Service client response", [
            'service' => static::class,
            'status_code' => $statusCode,
            'data_keys' => array_keys($data),
        ]);
    }

    /**
     * Set timeout for requests.
     */
    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Set retry configuration.
     */
    public function setRetryConfig(int $maxRetries, int $retryDelay): void
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }
}

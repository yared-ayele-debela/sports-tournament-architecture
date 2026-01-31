<?php

namespace App\Services\Clients;

use App\Exceptions\ServiceRequestException;
use App\Exceptions\ServiceUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

abstract class ServiceClient
{
    protected Client $client;
    protected string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @return array
     * @throws ServiceRequestException
     * @throws ServiceUnavailableException
     */
    protected function get(string $endpoint, array $query = [])
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            // Add authorization header if token is available in current request
            if (request()->bearerToken()) {
                $headers['Authorization'] = 'Bearer ' . request()->bearerToken();
            }

            $response = $this->client->get($endpoint, [
                'query' => $query,
                'headers' => $headers,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (is_array($data) && isset($data['success']) && !$data['success']) {
                throw new ServiceRequestException(
                    $data['message'] ?? 'Service request failed',
                    class_basename($this),
                    $response->getStatusCode(),
                    ['endpoint' => $endpoint, 'query' => $query, 'response' => $data]
                );
            }

            return $data;
        } catch (ConnectException $e) {
            Log::error("Service connection failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => $endpoint,
            ]);

            throw new ServiceUnavailableException(
                "Unable to connect to service: {$e->getMessage()}",
                class_basename($this),
                ['endpoint' => $endpoint, 'query' => $query],
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            
            Log::error("Service request failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
            ]);

            if ($statusCode >= 500 || $statusCode === null) {
                throw new ServiceUnavailableException(
                    "Service unavailable: {$e->getMessage()}",
                    class_basename($this),
                    ['endpoint' => $endpoint, 'status_code' => $statusCode],
                    $e
                );
            }

            throw new ServiceRequestException(
                "Service request failed: {$e->getMessage()}",
                class_basename($this),
                $statusCode,
                ['endpoint' => $endpoint, 'query' => $query],
                $e
            );
        }
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws ServiceRequestException
     * @throws ServiceUnavailableException
     */
    protected function post(string $endpoint, array $data = [])
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            if (request()->bearerToken()) {
                $headers['Authorization'] = 'Bearer ' . request()->bearerToken();
            }

            $response = $this->client->post($endpoint, [
                'json' => $data,
                'headers' => $headers,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (is_array($responseData) && isset($responseData['success']) && !$responseData['success']) {
                throw new ServiceRequestException(
                    $responseData['message'] ?? 'Service request failed',
                    class_basename($this),
                    $response->getStatusCode(),
                    ['endpoint' => $endpoint, 'response' => $responseData]
                );
            }

            return $responseData;
        } catch (ConnectException $e) {
            throw new ServiceUnavailableException(
                "Unable to connect to service: {$e->getMessage()}",
                class_basename($this),
                ['endpoint' => $endpoint],
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            
            if ($statusCode >= 500 || $statusCode === null) {
                throw new ServiceUnavailableException(
                    "Service unavailable: {$e->getMessage()}",
                    class_basename($this),
                    ['endpoint' => $endpoint, 'status_code' => $statusCode],
                    $e
                );
            }

            throw new ServiceRequestException(
                "Service request failed: {$e->getMessage()}",
                class_basename($this),
                $statusCode,
                ['endpoint' => $endpoint],
                $e
            );
        }
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws ServiceRequestException
     * @throws ServiceUnavailableException
     */
    protected function put(string $endpoint, array $data = [])
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            if (request()->bearerToken()) {
                $headers['Authorization'] = 'Bearer ' . request()->bearerToken();
            }

            $response = $this->client->put($endpoint, [
                'json' => $data,
                'headers' => $headers,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (is_array($responseData) && isset($responseData['success']) && !$responseData['success']) {
                throw new ServiceRequestException(
                    $responseData['message'] ?? 'Service request failed',
                    class_basename($this),
                    $response->getStatusCode(),
                    ['endpoint' => $endpoint, 'response' => $responseData]
                );
            }

            return $responseData;
        } catch (ConnectException $e) {
            throw new ServiceUnavailableException(
                "Unable to connect to service: {$e->getMessage()}",
                class_basename($this),
                ['endpoint' => $endpoint],
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            
            if ($statusCode >= 500 || $statusCode === null) {
                throw new ServiceUnavailableException(
                    "Service unavailable: {$e->getMessage()}",
                    class_basename($this),
                    ['endpoint' => $endpoint, 'status_code' => $statusCode],
                    $e
                );
            }

            throw new ServiceRequestException(
                "Service request failed: {$e->getMessage()}",
                class_basename($this),
                $statusCode,
                ['endpoint' => $endpoint],
                $e
            );
        }
    }

    /**
     * @param string $endpoint
     * @return array
     * @throws ServiceRequestException
     * @throws ServiceUnavailableException
     */
    protected function delete(string $endpoint)
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            if (request()->bearerToken()) {
                $headers['Authorization'] = 'Bearer ' . request()->bearerToken();
            }

            $response = $this->client->delete($endpoint, [
                'headers' => $headers,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (is_array($responseData) && isset($responseData['success']) && !$responseData['success']) {
                throw new ServiceRequestException(
                    $responseData['message'] ?? 'Service request failed',
                    class_basename($this),
                    $response->getStatusCode(),
                    ['endpoint' => $endpoint, 'response' => $responseData]
                );
            }

            return $responseData;
        } catch (ConnectException $e) {
            throw new ServiceUnavailableException(
                "Unable to connect to service: {$e->getMessage()}",
                class_basename($this),
                ['endpoint' => $endpoint],
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            
            if ($statusCode >= 500 || $statusCode === null) {
                throw new ServiceUnavailableException(
                    "Service unavailable: {$e->getMessage()}",
                    class_basename($this),
                    ['endpoint' => $endpoint, 'status_code' => $statusCode],
                    $e
                );
            }

            throw new ServiceRequestException(
                "Service request failed: {$e->getMessage()}",
                class_basename($this),
                $statusCode,
                ['endpoint' => $endpoint],
                $e
            );
        }
    }
}

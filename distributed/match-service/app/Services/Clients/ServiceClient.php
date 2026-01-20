<?php

namespace App\Services\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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

    protected function get(string $endpoint, array $query = [])
    {
        try {
            $response = $this->client->get($endpoint, [
                'query' => $query,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error("Service request failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => $endpoint,
                'query' => $query,
            ]);

            return null;
        }
    }

    protected function post(string $endpoint, array $data = [])
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error("Service request failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => $endpoint,
                'data' => $data,
            ]);

            return null;
        }
    }

    protected function put(string $endpoint, array $data = [])
    {
        try {
            $response = $this->client->put($endpoint, [
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error("Service request failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => $endpoint,
                'data' => $data,
            ]);

            return null;
        }
    }

    protected function delete(string $endpoint)
    {
        try {
            $response = $this->client->delete($endpoint);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error("Service request failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => $endpoint,
            ]);

            return null;
        }
    }
}

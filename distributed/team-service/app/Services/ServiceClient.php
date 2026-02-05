<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ServiceClient
{
    protected $client;
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = $this->getBaseUrl();
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function getBaseUrl()
    {
        return '';
    }

    protected function request($method, $endpoint, $data = [])
    {
        try {
            $options = [];
            
            if (!empty($data)) {
                $options['json'] = $data;
            }

            // Initialize headers array
            $options['headers'] = [];

            // Forward correlation ID from incoming request
            if (request()->header('X-Request-ID')) {
                $options['headers']['X-Request-ID'] = request()->header('X-Request-ID');
            }

            // Forward Authorization header from incoming request
            $incomingToken = request()->bearerToken();
            if ($incomingToken) {
                $options['headers']['Authorization'] = 'Bearer ' . $incomingToken;
            }

            $response = $this->client->request($method, $endpoint, $options);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error("Service request failed: " . $e->getMessage());
            
            if ($e->hasResponse()) {
                return json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            
            throw $e;
        }
    }
}

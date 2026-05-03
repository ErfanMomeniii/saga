<?php

namespace Shared\Http;

use Exception;

class Client
{
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $baseUrl = '', int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function get(string $endpoint, array $headers = []): Response
    {
        return $this->request('GET', $endpoint, [], $headers);
    }

    public function post(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    public function put(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('PUT', $endpoint, $data, $headers);
    }

    public function delete(string $endpoint, array $headers = []): Response
    {
        return $this->request('DELETE', $endpoint, [], $headers);
    }

    private function request(string $method, string $endpoint, array $data, array $headers): Response
    {
        $url = $this->baseUrl . $endpoint;
        $headers['Content-Type'] = 'application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->formatHeaders($headers));

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("HTTP request failed: " . $error);
        }

        $responseData = json_decode($body, true) ?? [];
        return new Response($responseData, $httpCode);
    }

    private function formatHeaders(array $headers): array
    {
        return array_map(fn($v, $k) => "$k: $v", $headers, array_keys($headers));
    }
}
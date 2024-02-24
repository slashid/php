<?php

namespace SlashId\Php;

use GuzzleHttp\Client;

class SlashIdSdk
{

    const ENVIRONMENT_PRODUCTION = 'production';
    const ENVIRONMENT_SANDBOX = 'sandbox';

    const ENVIRONMENT_ENDPOINTS = [
        self::ENVIRONMENT_PRODUCTION => 'https://api.slashid.com/',
        self::ENVIRONMENT_SANDBOX => 'https://api.sandbox.slashid.com/',
    ];

    protected Client $client;

    public function __construct(
        protected string $environment,
        protected string $organizationId,
        protected string $apiKey,
    )
    {
        if (!isset(self::ENVIRONMENT_ENDPOINTS[$this->environment])) {
            // @todo create custom exception class.
            throw new \Exception('Invalid environment.');
        }
    }

    public function get(string $endpoint, array $query = NULL)
    {
        return $this->request('GET', $endpoint, $query, NULL);
    }

    public function post(string $endpoint, array $body = NULL)
    {
        return $this->request('POST', $endpoint, NULL, $body);
    }

    public function patch(string $endpoint, array $body = NULL)
    {
        return $this->request('PATCH', $endpoint, NULL, $body);
    }

    public function put(string $endpoint, array $body = NULL)
    {
        return $this->request('PUT', $endpoint, NULL, $body);
    }

    public function delete(string $endpoint, array $query = NULL)
    {
        return $this->request('DELETE', $endpoint, $query, NULL);
    }

    protected function request(string $method, string $endpoint, ?array $query, ?array $body) {
        $options = [];

        if (!empty($query)) {
            $options['query'] = $query;
        }

        if (!is_null($body)) {
            $options['body'] = \json_encode($body);
        }

        $response = $this->getClient()->request($method, $endpoint, $options);
        $parsedResponse = \json_decode((string) $response->getBody(), TRUE);
        return $parsedResponse['result'] ?? NULL;
    }

    protected function getClient(): Client {
        if (!isset($this->client)) {
            $this->client = new Client([
                'base_uri' => self::ENVIRONMENT_ENDPOINTS[$this->environment],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'SlashID-OrgID' => $this->organizationId,
                    'SlashID-API-Key' => $this->apiKey,
                ],
            ]);
        }

        return $this->client;
    }

}

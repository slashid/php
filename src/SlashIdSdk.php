<?php

namespace SlashId\Php;

use GuzzleHttp\Client;
use SlashId\Php\Abstraction\WebhookAbstraction;

class SlashIdSdk
{
    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_SANDBOX = 'sandbox';
    public const ENVIRONMENT_ENDPOINTS = [
        self::ENVIRONMENT_PRODUCTION => 'https://api.slashid.com/',
        self::ENVIRONMENT_SANDBOX => 'https://api.sandbox.slashid.com/',
    ];

    protected Client $client;
    protected string $apiUrl;
    protected WebhookAbstraction $webhook;

    public function __construct(
        protected string $environment,
        protected string $organizationId,
        protected string $apiKey,
    ) {
        if (!isset(self::ENVIRONMENT_ENDPOINTS[$this->environment])) {
            // @todo create custom exception class.
            throw new \Exception('Invalid environment.');
        }

        $this->apiUrl = self::ENVIRONMENT_ENDPOINTS[$this->environment];
    }

    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function webhook(): WebhookAbstraction
    {
        if (!isset($this->webhook)) {
            $this->webhook = new WebhookAbstraction($this);
        }

        return $this->webhook;
    }

    public function get(string $endpoint, array $query = null)
    {
        return $this->request('GET', $endpoint, $query, null);
    }

    public function post(string $endpoint, array $body = null)
    {
        return $this->request('POST', $endpoint, null, $body);
    }

    public function patch(string $endpoint, array $body = null)
    {
        return $this->request('PATCH', $endpoint, null, $body);
    }

    public function put(string $endpoint, array $body = null)
    {
        return $this->request('PUT', $endpoint, null, $body);
    }

    public function delete(string $endpoint, array $query = null)
    {
        return $this->request('DELETE', $endpoint, $query, null);
    }

    protected function request(string $method, string $endpoint, ?array $query, ?array $body)
    {
        $options = [];

        if (!empty($query)) {
            $options['query'] = $query;
        }

        if (!is_null($body)) {
            $options['body'] = \json_encode($body);
        }

        $response = $this->getClient()->request($method, $endpoint, $options);
        $parsedResponse = \json_decode((string) $response->getBody(), true);
        return $parsedResponse['result'] ?? null;
    }

    protected function getClient(): Client
    {
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

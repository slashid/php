<?php

namespace SlashId\Php;

use GuzzleHttp\Client;
use SlashId\Php\Abstraction\WebhookAbstraction;

class SlashIdSdk
{
    public const ENVIRONMENT_PRODUCTION = 'production';
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    /**
     * List of URL.
     *
     * To get the API URL for a given environment, use the method getApiUrl().
     *
     * @see SlashIdSdk::getApiUrl()
     */
    protected const ENVIRONMENT_URLS = [
        self::ENVIRONMENT_PRODUCTION => 'https://api.slashid.com/',
        self::ENVIRONMENT_SANDBOX => 'https://api.sandbox.slashid.com/',
    ];

    /**
     * The Guzzle client, lazy-instantiated when required.
     */
    protected Client $client;

    /**
     * The API URL, defined from the constant self::ENVIRONMENT_URLS.
     *
     * @see SlashIdSdk::getApiUrl()
     */
    protected string $apiUrl;
    protected WebhookAbstraction $webhook;

    public function __construct(
        protected string $environment,
        protected string $organizationId,
        protected string $apiKey,
    ) {
        if (!isset(self::ENVIRONMENT_URLS[$this->environment])) {
            // @todo create custom exception class.
            throw new \Exception('Invalid environment.');
        }

        $this->apiUrl = self::ENVIRONMENT_URLS[$this->environment];
    }

    /**
     * Gets the organization ID, as informed to the constructor.
     */
    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    /**
     * Gets the API URL, based on the environment informed to the constructor.
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Instantiates a webhook abstraction, to handle webhook requests to the API.
     */
    public function webhook(): WebhookAbstraction
    {
        if (!isset($this->webhook)) {
            $this->webhook = new WebhookAbstraction($this);
        }

        return $this->webhook;
    }

    /**
     * Perfoms a GET request to the API.
     *
     * @param string $endpoint
     *   The endpoint to the API, e.g. "/persons". If the endpoint requires an
     *   ID in the path, do include it, e.g.:
     *   "/persons/903c1ff9-f2cc-435c-b242-9d8a690fcf0a".
     * @param array
     */
    public function get(string $endpoint, array $query = null)
    {
        return $this->request('GET', $endpoint, $query, null);
    }

    /**
     * Performs a POST request to the API.
     */
    public function post(string $endpoint, ?array $body = null)
    {
        return $this->request('POST', $endpoint, null, $body);
    }

    public function patch(string $endpoint, ?array $body = null)
    {
        return $this->request('PATCH', $endpoint, null, $body);
    }

    public function put(string $endpoint, ?array $body = null)
    {
        return $this->request('PUT', $endpoint, null, $body);
    }

    public function delete(string $endpoint, ?array $query = null)
    {
        return $this->request('DELETE', $endpoint, $query, null);
    }

    protected function request(string $method, string $endpoint, ?array $query, ?array $body)
    {
        $options = [];

        if (!empty($query)) {
            $options['query'] = array_map(fn($item) => is_array($item) ? implode(',', $item) : $item, $query);
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

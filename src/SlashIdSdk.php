<?php

namespace SlashId\Php;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
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
        self::ENVIRONMENT_PRODUCTION => 'https://api.slashid.com',
        self::ENVIRONMENT_SANDBOX => 'https://api.sandbox.slashid.com',
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
        protected ?HandlerStack $handlerStack = null,
    ) {
        if (!isset(self::ENVIRONMENT_URLS[$this->environment])) {
            throw new \InvalidArgumentException('Invalid environment "' . $this->environment . '". Valid options are: SlashIdSdk::ENVIRONMENT_PRODUCTION or SlashIdSdk::ENVIRONMENT_SANDBOX.');
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
            $this->webhook = new WebhookAbstraction($this, $this->getClient());
        }

        return $this->webhook;
    }

    /**
     * Perfoms a GET request to the API.
     *
     * @param string       $endpoint The endpoint to the API, e.g. "/persons". If the endpoint requires an ID in the
     *                               path, include it in the parameter, e.g.:
     *                               "/persons/903c1ff9-f2cc-435c-b242-9d8a690fcf0a".
     *                               The $endpoint MUST ALWAYS start with "/".
     * @param mixed[]|null $query    The query to be included in the request. E.g. for
     *                               "/persons/903c...f0a?fields=handles,groups", pass this a parameter:
     *                               ['fields' => ['handles', 'groups']].
     *
     * @return mixed[] the "result" part of the response, decoded as an array
     */
    public function get(string $endpoint, ?array $query = null): ?array
    {
        return $this->request('GET', $endpoint, $query, null);
    }

    /**
     * Performs a POST request to the API.
     *
     * @param string       $endpoint The endpoint to the API, e.g. "/persons". If the endpoint requires an ID in the
     *                               path, include it in the parameter, e.g.:
     *                               "/persons/903c1ff9-f2cc-435c-b242-9d8a690fcf0a".
     * @param mixed[]|null $body     the body of the request, as an array that will be encoded as JSON
     *
     * @return mixed[] the "result" part of the response, decoded as an array
     */
    public function post(string $endpoint, ?array $body = null): ?array
    {
        return $this->request('POST', $endpoint, null, $body);
    }

    /**
     * Performs a PATCH request to the API.
     *
     * @param string       $endpoint The endpoint to the API, e.g. "/persons". If the endpoint requires an ID in the
     *                               path, include it in the parameter, e.g.:
     *                               "/persons/903c1ff9-f2cc-435c-b242-9d8a690fcf0a".
     * @param mixed[]|null $body     the body of the request, as an array that will be encoded as JSON
     *
     * @return mixed[] the "result" part of the response, decoded as an array
     */
    public function patch(string $endpoint, ?array $body = null): ?array
    {
        return $this->request('PATCH', $endpoint, null, $body);
    }

    /**
     * Performs a PUT request to the API.
     *
     * @param string       $endpoint The endpoint to the API, e.g. "/persons". If the endpoint requires an ID in the
     *                               path, include it in the parameter, e.g.:
     *                               "/persons/903c1ff9-f2cc-435c-b242-9d8a690fcf0a".
     * @param mixed[]|null $body     the body of the request, as an array that will be encoded as JSON
     *
     * @return mixed[] the "result" part of the response, decoded as an array
     */
    public function put(string $endpoint, ?array $body = null): ?array
    {
        return $this->request('PUT', $endpoint, null, $body);
    }

    /**
     * Performs a DELETE request to the API.
     *
     * @param string       $endpoint The endpoint to the API, e.g. "/persons". If the endpoint requires an ID in the
     *                               path, include it in the parameter, e.g.:
     *                               "/persons/903c1ff9-f2cc-435c-b242-9d8a690fcf0a".
     * @param mixed[]|null $query    The query to be included in the request. E.g. for
     *                               "/persons/903c...f0a?fields=handles,groups", pass this a parameter:
     *                               ['fields' => ['handles', 'groups']].
     */
    public function delete(string $endpoint, ?array $query = null): void
    {
        $this->request('DELETE', $endpoint, $query, null);
    }

    /**
     * Performs a request.
     *
     * @param string       $method   either "GET", "POST", "PATCH", "PUT" or "DELETE"
     * @param string       $endpoint the endpoint, without the base URL
     * @param mixed[]|null $query    the (optional) query for the URL
     * @param mixed[]|null $body     the body for POST, PATCH and PUT requests
     *
     * @return mixed[] the "result" part of the response, decoded as an array
     */
    protected function request(string $method, string $endpoint, ?array $query, ?array $body): ?array
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

    /**
     * Gets the GuzzlePHP client, instantiating it if needed.
     */
    protected function getClient(): Client
    {
        if (!isset($this->client)) {
            $options = [
                'base_uri' => $this->apiUrl,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'SlashID-OrgID' => $this->organizationId,
                    'SlashID-API-Key' => $this->apiKey,
                ],
            ];

            if ($this->handlerStack) {
                $options['handler'] = $this->handlerStack;
            }

            $this->client = new Client($options);
        }

        return $this->client;
    }
}

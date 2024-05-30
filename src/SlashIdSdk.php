<?php

namespace SlashId\Php;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use SlashId\Php\Abstraction\MigrationAbstraction;
use SlashId\Php\Abstraction\TokenAbstraction;
use SlashId\Php\Abstraction\WebhookAbstraction;
use SlashId\Php\Exception\AccessDeniedException;
use SlashId\Php\Exception\BadRequestException;
use SlashId\Php\Exception\ConflictException;
use SlashId\Php\Exception\IdNotFoundException;
use SlashId\Php\Exception\InvalidEndpointException;
use SlashId\Php\Exception\UnauthorizedException;

/**
 * @phpstan-type WebserviceReturn array{result:mixed[]}
 */
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
    protected MigrationAbstraction $migration;
    protected TokenAbstraction $token;
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
     * Gets the environment.
     *
     * @return string the environment, SlashIdSdk::ENVIRONMENT_PRODUCTION or SlashIdSdk::ENVIRONMENT_SANDBOX
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Gets the organization ID, as informed to the constructor.
     */
    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    /**
     * Gets the API Key, as informed to the constructor.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Gets the API URL, based on the environment informed to the constructor.
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Instantiates a migration abstraction, to handle user migrations.
     */
    public function migration(): MigrationAbstraction
    {
        if (!isset($this->migration)) {
            $this->migration = new MigrationAbstraction($this);
        }

        return $this->migration;
    }

    /**
     * Instantiates a token abstraction, to handle authentication tokens.
     */
    public function token(): TokenAbstraction
    {
        if (!isset($this->token)) {
            $this->token = new TokenAbstraction($this);
        }

        return $this->token;
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
     * Gets the GuzzlePHP client, instantiating it if needed.
     */
    public function getClient(): Client
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

        try {
            $response = $this->getClient()->request($method, $endpoint, $options);
        } catch (ClientException $clientException) {
            throw $this->convertClientException($clientException);
        }
        /** @var WebserviceReturn|null */
        $parsedResponse = \json_decode((string) $response->getBody(), true);

        return $parsedResponse['result'] ?? null;
    }

    /**
     * Process 4xx errors, converting into custom exceptions.
     */
    protected function convertClientException(ClientException $clientException): BadResponseException
    {
        $request = $clientException->getRequest();
        $response = $clientException->getResponse();
        $parsedResponse = \json_decode((string) $response->getBody(), true);

        // 404 errors when then endpoint is invalid do NOT return a valid JSON.
        $errorMessage = is_array($parsedResponse) ? ($parsedResponse['errors'][0]['message'] ?? null) : null;

        switch ($response->getStatusCode()) {
            case 400:
                return new BadRequestException($errorMessage, $clientException);

            case 401:
                return new UnauthorizedException('Unauthorized, please check the API Key and the Organization ID', $clientException);

            case 403:
                return new AccessDeniedException("Access has been denied: $errorMessage", $clientException);

            case 404:
                // If there is a valid response, it means this is a valid endpoint.
                if ($errorMessage) {
                    return new IdNotFoundException($errorMessage, $clientException);
                }

                return new InvalidEndpointException('Could not find endpoint', $clientException);

            case 409:
                return new ConflictException($errorMessage, $clientException);
        }

        // If we could not convert the exception, throw the original exception.
        return $clientException;
    }
}

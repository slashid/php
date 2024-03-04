<?php

namespace SlashId\Php\Abstraction;

use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @phpstan-type WebhookArray array{id: string, name: string, description: string, target_url: string, custom_headers: string[][], timeout: string}
 */
class WebhookAbstraction extends AbstractionBase
{
    /**
     * Finds all existing webhooks in the organization.
     *
     * @return WebhookArray[] A list of webhook definitions, each as an array, e.g.:
     *
     *                        @code
     *                         [
     *                             [
     *                                 'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
     *                                 'name' => 'prod_webhook',
     *                                 'description' => 'Some description...',
     *                                 'target_url' => 'https://example.com/slashid/webhook',
     *                                 'custom_headers' => [
     *                                     'X-Extra-Check' => ['Value for the header'],
     *                                 ],
     *                                 'timeout' => '30s',
     *                             ],
     *                             [
     *                                 'id' => ...
     *                             ],
     *                        ]
     *
     *                        @endcode
     */
    public function findAll(): array
    {
        /** @var WebhookArray[] */
        $response = $this->sdk->get('/organizations/webhooks');

        return $response;
    }

    /**
     * Finds a webhook by ID.
     *
     * @param string $id a webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d"
     *
     * @return WebhookArray A webhook definition, e.g.:
     *
     *                      @code
     *                         [
     *                             'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
     *                             'name' => 'prod_webhook',
     *                             'description' => 'Some description...',
     *                             'target_url' => 'https://example.com/slashid/webhook',
     *                             'custom_headers' => [
     *                                 'X-Extra-Check' => ['Value for the header'],
     *                             ],
     *                             timeout' => '30s',
     *                         ]
     *
     *                        @endcode
     */
    public function findById(string $id): array
    {
        /** @var WebhookArray */
        $response = $this->sdk->get('/organizations/webhooks/' . $id);

        return $response;
    }

    /**
     * Finds a webhook by its URL.
     *
     * @param string $url A webhook URL, e.g. as "https://example.com/slashid/webhook".
     *
     * @return WebhookArray A webhook definition, e.g.:
     *
     *                      @code
     *                      [
     *                          'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
     *                          'name' => 'prod_webhook',
     *                          'description' => 'Some description...',
     *                          'target_url' => 'https://example.com/slashid/webhook',
     *                          'custom_headers' => [
     *                              'X-Extra-Check' => ['Value for the header'],
     *                          ],
     *                          'timeout' => '30s',
     *                      ]
     *
     *                      @endcode
     */
    public function findByUrl(string $url): ?array
    {
        $webhooks = $this->findAll();
        foreach ($webhooks as $webhook) {
            if ($webhook['target_url'] === $url) {
                return $webhook;
            }
        }

        return null;
    }

    /**
     * Creates or updates a webhook.
     *
     * If a webhook with $url already exists, it will be updated (witha a PATCH request). If if doesn't exist, it will
     * be created with a POST request.
     *
     * @param string                   $url      a webhook URL, e.g. as "https://example.com/slashid/webhook"
     * @param string                   $name     a name for the webhook, e.g. "prod_webhook"
     * @param string[]                 $triggers a list of triggers, one of events listed on
     *                                           https://developer.slashid.dev/docs/access/guides/webhooks
     * @param array<string|string[][]> $options  Optional fields in the Webhook. Please note that if the webhook already
     *                                           exists, these fields will NOT be overridden unless specified:
     *
     *                                           @code
     *                                           [
     *                                               'description' => 'Some description...',
     *                                               'custom_headers' => [
     *                                                   'X-Extra-Check' => ['Value for the header'],
     *                                               ],
     *                                               'timeout' => '30s',
     *                                           ]
     *
     *                                           @endcode
     */
    public function register(string $url, string $name, array $triggers, array $options = []): void
    {
        $payload = [
            'target_url' => $url,
            'name' => $name,
        ] + $options;

        if ($webhook = $this->findByUrl($url)) {
            $this->sdk->patch('/organizations/webhooks/' . $webhook['id'], $payload);
        } else {
            /** @var WebhookArray */
            $webhook = $this->sdk->post('/organizations/webhooks', $payload);
        }

        $this->setWebhookTriggers($webhook['id'], $triggers);
    }

    /**
     * Delete a webhook given its ID.
     *
     * @param string $id a webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d"
     */
    public function deleteById(string $id): void
    {
        $this->sdk->delete('/organizations/webhooks/' . $id);
    }

    /**
     * Deletes a webhook by its URL.
     *
     * @param string $url A webhook URL, e.g. as "https://example.com/slashid/webhook".
     */
    public function deleteByUrl(string $url): void
    {
        if ($webhook = $this->findByUrl($url)) {
            $this->deleteById($webhook['id']);
        } else {
            // @todo Create custom Exceptions.
            $organizationId = $this->sdk->getOrganizationId();
            throw new \Exception("There is no webhook in organization $organizationId for the URL \"$url\".");
        }
    }

    /**
     * Lists triggers of a given webhook.
     *
     * @return string[] the list of webhook triggers
     */
    public function getWebhookTriggers(string $id): array
    {
        /** @var string[][] */
        $response = $this->sdk->get('/organizations/webhooks/' . $id . '/triggers');

        return array_map(
            fn($trigger) => $trigger['trigger_name'],
            $response,
        );
    }

    /**
     * Overrides the triggers of a given webhook.
     *
     * Please note that existing triggers that are not in the $triggers list will be deleted from the webservice.
     *
     * @param string   $id       a webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d"
     * @param string[] $triggers a list of triggers, e.g. ['PersonCreated_v1', 'PersonDeleted_v1']
     */
    public function setWebhookTriggers(string $id, array $triggers): void
    {
        $existingTriggers = $this->getWebhookTriggers($id);

        // Delete existing triggers taht are not in the list.
        foreach (array_diff($existingTriggers, $triggers) as $triggerToDelete) {
            $this->deleteWebhookTrigger($id, $triggerToDelete);
        }

        // Adds triggers that don't exist yet.
        foreach (array_diff($triggers, $existingTriggers) as $triggerToAdd) {
            $this->addWebhookTrigger($id, $triggerToAdd);
        }
    }

    /**
     * Add a trigger to a webhook (without removing existing ones).
     *
     * @param string $id      a webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d"
     * @param string $trigger A trigger, e.g. "PersonCreated_v1".
     */
    public function addWebhookTrigger(string $id, string $trigger): void
    {
        $this->sdk->post('/organizations/webhooks/' . $id . '/triggers', [
            'trigger_type' => $this->getWebhookTriggerType($trigger),
            'trigger_name' => $trigger,
        ]);
    }

    /**
     * Removes a trigger from a webhook.
     *
     * @param string $id      a webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d"
     * @param string $trigger A trigger, e.g. "PersonCreated_v1".
     */
    public function deleteWebhookTrigger(string $id, string $trigger): void
    {
        $this->sdk->delete('/organizations/webhooks/' . $id . '/triggers', [
            'trigger_type' => $this->getWebhookTriggerType($trigger),
            'trigger_name' => $trigger,
        ]);
    }

    /**
     * Validates and decodes the JWT sent to a webhook listener.
     *
     * The JWT is checked using the JSON Web Signature with a public keyset provided by the
     * /organizations/webhooks/verification-jwks API endpoint. The keys requested to the API are cached, so that we are
     * not always making requests to the web service.
     *
     * To accomplish that, the library checking the JWT (firebase/php-jwt) requires a cache item pool compatible with
     * PSR-6, please check how your framework's documentation to learn how to obtain it.
     *
     * @param string                 $jwt          a JWT sent from SlashID servers to a local webhook listener
     * @param CacheItemPoolInterface $cache        a cache pool to cache the JWKS
     * @param int                    $expiresAfter the number of seconds to keep the keys in the cache
     * @param bool                   $rateLimit    whether to enable rate limit of 10 request per seconds on lookup of
     *                                             invalid keys
     *
     * @return mixed[] the decoded and validated JWT, as an array
     *
     * @see https://developer.slashid.dev/docs/access/guides/webhooks/introduction
     * @see https://developer.slashid.dev/docs/api/get-organizations-webhooks-verification-jwks
     * @see https://en.wikipedia.org/wiki/JSON_Web_Signature
     * @see https://github.com/firebase/php-jwt?tab=readme-ov-file#using-cached-key-sets
     */
    public function decodeWebhookCall(
        string $jwt,
        CacheItemPoolInterface $cache,
        int $expiresAfter = 3600,
        bool $rateLimit = true
    ): array {
        $keySet = new CachedKeySet(
            $this->sdk->getApiUrl() . '/organizations/webhooks/verification-jwks',
            $this->client,
            new HttpFactory(),
            $cache,
            $expiresAfter,
            $rateLimit,
        );

        $decoded = JWT::decode($jwt, $keySet);

        // Convert to array.
        /** @var mixed[] */
        $decodedAsArray = \json_decode((string) \json_encode($decoded), true);

        return $decodedAsArray;
    }

    /**
     * Checks the type of the webhook trigger.
     */
    protected function getWebhookTriggerType(string $trigger): string
    {
        return 'token_minted' === $trigger ? 'sync_hook' : 'event';
    }
}

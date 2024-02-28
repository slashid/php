<?php

namespace SlashId\Php\Abstraction;

use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Cache\CacheItemPoolInterface;

class WebhookAbstraction extends AbstractionBase
{
    /**
     * Finds all existing webhooks in the organization.
     *
     * @return array[] A list of webhook definitions, each as an array, e.g.:
     *
     *                 @code
     *                  [
     *                      [
     *                          'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
     *                          'name' => 'prod_webhook',
     *                          'description' => 'Some description...',
     *                          'target_url' => 'https://example.com/slashid/webhook',
     *                          'custom_headers' => [
     *                              'X-Extra-Check' => ['Value for the header'],
     *                          ],
     *                          'timeout' => '30s',
     *                      ],
     *                      [
     *                          'id' => ...
     *                      ],
     *                 ]
     *
     *                @endcode
     */
    public function findAll(): array
    {
        return $this->sdk->get('/organizations/webhooks');
    }

    /**
     * Finds a webhook by ID.
     *
     * @param string $id A webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d".
     *
     * @return array A webhook definition, e.g.:
     *
     *                   @code
     *                      [
     *                          'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
     *                          'name' => 'prod_webhook',
     *                          'description' => 'Some description...',
     *                          'target_url' => 'https://example.com/slashid/webhook',
     *                          'custom_headers' => [
     *                              'X-Extra-Check' => ['Value for the header'],
     *                          ],
     *                          timeout' => '30s',
     *                      ]
     *
     *                     @endcode
     */
    public function findById(string $id): array
    {
        return $this->sdk->get('/organizations/webhooks/' . $id);
    }

    /**
     * Finds a webhook by its URL.
     *
     * @param string $url A webhook URL, e.g. as "https://example.com/slashid/webhook".
     *
     * @return array A webhook definition, e.g.:
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
     * @param string   $url      A webhook URL, e.g. as "https://example.com/slashid/webhook".
     * @param string   $name     A name for the webhook, e.g. "prod_webhook".
     * @param string[] $triggers A list of triggers, one of events listed on
     *                           https://developer.slashid.dev/docs/access/guides/webhooks
     * @param array    $options  Optional fields in the Webhook. Please note that if the webhook already exists, these
     *                           fields will NOT be overridden unless specifically informed.
     *
     *                              @code
     *                              [
     *                                  'description' => 'Some description...',
     *                                  'custom_headers' => [
     *                                      'X-Extra-Check' => ['Value for the header'],
     *                                  ],
     *                                  'timeout' => '30s',
     *                              ]
     *
     *                              @endcode
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
            $webhook = $this->sdk->post('/organizations/webhooks', $payload);
        }

        $this->setWebhookTriggers($webhook['id'], $triggers);
    }

    /**
     * Delete a webhook given its ID.
     *
     * @param string $id A webhook ID, e.g. "065de68b-cce0-7285-ab00-6f34a56b585d".
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
        }

        // @todo Create custom Exceptions.
        throw new \Exception('There is no webhook in organization ' . $this->sdk->getOrganizationId() . ' for the URL "' . $url . '".');
    }

    /**
     * Lists triggers of a given webhook.
     *
     * @return string[] The list of webhook triggers.
     */
    public function getWebhookTriggers(string $id): array
    {
        return array_map(
            fn($trigger) => $trigger['trigger_name'],
            $this->sdk->get('/organizations/webhooks/' . $id . '/triggers')
        );
    }

    public function setWebhookTriggers(string $id, array $triggers): void {
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

    public function addWebhookTrigger(string $id, string $trigger): void {
        $this->sdk->post('/organizations/webhooks/' . $id . '/triggers', [
            'trigger_type' => $this->getWebhookTriggerType($trigger),
            'trigger_name' => $trigger,
        ]);
    }

    public function deleteWebhookTrigger(string $id, string $trigger): void {
        $this->sdk->delete('/organizations/webhooks/' . $id . '/triggers', [
            'trigger_type' => $this->getWebhookTriggerType($trigger),
            'trigger_name' => $trigger,
        ]);
    }

    public function decodeWebhookCall(string $jwt, CacheItemPoolInterface $cache, $expiresAfter = 3600, $rateLimit = true)
    {
        $httpClient = new Client([
            'headers' => [
                'SlashID-OrgID' => $this->sdk->getOrganizationId(),
            ],
        ]);

        $keySet = new CachedKeySet(
            $this->sdk->getApiUrl() . '/organizations/webhooks/verification-jwks',
            $httpClient,
            new HttpFactory(),
            $cache,
            $expiresAfter,
            $rateLimit,
        );

        $decoded = JWT::decode($jwt, $keySet);

        // Convert to array.
        return \json_decode(\json_encode($decoded), true);
    }

    protected function getWebhookTriggerType(string $trigger): string {
        return $trigger === 'token_minted' ? 'sync_hook' : 'event';
    }
}

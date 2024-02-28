<?php

namespace SlashId\Php\Abstraction;

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
            if ($webhook['target_id'] ?? null === $url) {
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
    public function register(string $url, string $name, array $triggers, array $options): void
    {
        $payload = [
            'target_url' => $url,
            'name' => $name,
        ] + $options;

        if ($webhook = $this->findByUrl($url)) {
            $this->sdk->patch('/organizations/webhooks/' . $webhook['id'], $payload);
        } else {
            $this->sdk->post('/organizations/webhooks', $payload);
        }
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
}

<?php

namespace SlashId\Php\Abstraction;

use PHPUnit\Framework\TestCase;
use SlashId\Test\Php\TestHelper\SdkInstantiationTrait;

/**
 * @covers \SlashId\Php\Abstraction\WebhookAbstraction
 */
class WebhookAbstractionTest extends TestCase
{
    use SdkInstantiationTrait;

    /**
     * Actual response from the sandbox webservice to GET /organizations/webhooks.
     */
    protected const INDEX_RAW_RESPONSE = '{"result":[{"description":"","id":"065e3c7b-3480-7c16-8700-26b32cf383fe","name":"example","target_url":"https://example.com/slashid/webhook","timeout":"0s"},{"description":"","id":"065e3c7b-3b53-7fd7-9800-19bf7ba6250e","name":"second_example","target_url":"https://example.com/slashid/second_webhook","timeout":"0s"}]}';

    /**
     * Actual response from the sandbox webservice to GET /organizations/webhooks/{id}.
     */
    protected const GET_ONE_RAW_RESPONSE = '{"result":{"description":"","id":"065e3c7b-3480-7c16-8700-26b32cf383fe","name":"example","target_url":"https://example.com/slashid/webhook","timeout":"0s"}}';

    /**
     * Tests findAll().
     */
    public function testFindAll(): void
    {
        $historyContainer = [];
        $results = $this->webhook($historyContainer, [
            [200, self::INDEX_RAW_RESPONSE],
        ])->findAll();
        $this->assertEquals([
            [
                'description' => '',
                'id' => '065e3c7b-3480-7c16-8700-26b32cf383fe',
                'name' => 'example',
                'target_url' => 'https://example.com/slashid/webhook',
                'timeout' => '0s',
            ],
            [
                'description' => '',
                'id' => '065e3c7b-3b53-7fd7-9800-19bf7ba6250e',
                'name' => 'second_example',
                'target_url' => 'https://example.com/slashid/second_webhook',
                'timeout' => '0s',
            ],
        ], $results);
        $this->assertEquals('/organizations/webhooks', $historyContainer[0]['request']->getRequestTarget());
    }

    /**
     * Tests findById().
     */
    public function testFindById(): void
    {
        $historyContainer = [];
        $results = $this->webhook($historyContainer, [
            [200, self::GET_ONE_RAW_RESPONSE],
        ])->findById('065e3c7b-3480-7c16-8700-26b32cf383fe');
        $this->assertEquals([
            'description' => '',
            'id' => '065e3c7b-3480-7c16-8700-26b32cf383fe',
            'name' => 'example',
            'target_url' => 'https://example.com/slashid/webhook',
            'timeout' => '0s',
        ], $results);
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe', $historyContainer[0]['request']->getRequestTarget());
    }

    /**
     * Tests findByUrl().
     */
    public function testFindByUrl(): void
    {
        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, self::INDEX_RAW_RESPONSE],
            [200, self::INDEX_RAW_RESPONSE],
        ]);

        $results = $webhook->findByUrl('https://example.com/slashid/webhook');
        $this->assertEquals([
            'description' => '',
            'id' => '065e3c7b-3480-7c16-8700-26b32cf383fe',
            'name' => 'example',
            'target_url' => 'https://example.com/slashid/webhook',
            'timeout' => '0s',
        ], $results);
        $this->assertEquals('/organizations/webhooks', $historyContainer[0]['request']->getRequestTarget());

        $emptyResults = $webhook->findByUrl('https://example.com/slashid/invalid_webhook');
        $this->assertNull($emptyResults);
        $this->assertEquals('/organizations/webhooks', $historyContainer[1]['request']->getRequestTarget());
    }

    /**
     * Tests register() when the URL is not yet an existing webhook.
     */
    public function testRegisterNewWebhook(): void
    {
        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, self::INDEX_RAW_RESPONSE],
            [201, '{"result":{"custom_headers":{"X-Extra-Check":["Value for the header"]},"description":"","id":"065e3c7b-9999-7c16-8700-26b32cf383fe","name":"new_example","target_url":"https://example.com/slashid/new_webhook","timeout":"0s"}}'],
            [200, '{"result":[]}'],
        ]);
        $webhook->register('https://example.com/slashid/new_webhook', 'new_example', [], [
            'custom_headers' => [
                'X-Extra-Check' => ['Value for the header'],
            ],
        ]);

        // Checks the request to check if the webhook exists.
        $this->assertEquals('GET', $historyContainer[0]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks', $historyContainer[0]['request']->getRequestTarget());

        // Checks the request to create the webhook.
        $this->assertEquals('POST', $historyContainer[1]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks', $historyContainer[1]['request']->getRequestTarget());
        $this->assertEquals(
            '{"target_url":"https:\/\/example.com\/slashid\/new_webhook","name":"new_example","custom_headers":{"X-Extra-Check":["Value for the header"]}}',
            (string) $historyContainer[1]['request']->getBody(),
        );

        // Checks the resquest to the list of triggers.
        $this->assertEquals('GET', $historyContainer[2]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-9999-7c16-8700-26b32cf383fe/triggers', $historyContainer[2]['request']->getRequestTarget());
    }

    /**
     * Tests register() when the URL is an already existing webhook.
     */
    public function testRegisterUpdateWebhook(): void
    {
        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, self::INDEX_RAW_RESPONSE],
            [201, self::GET_ONE_RAW_RESPONSE],
            [200, '{"result":[]}'],
        ]);
        $webhook->register('https://example.com/slashid/webhook', 'new_example', [], [
            'custom_headers' => [
                'X-Extra-Check' => ['Value for the header'],
            ],
        ]);

        // Checks the request to check if the webhook exists.
        $this->assertEquals('GET', $historyContainer[0]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks', $historyContainer[0]['request']->getRequestTarget());

        // Checks the request to create the webhook.
        $this->assertEquals('PATCH', $historyContainer[1]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe', $historyContainer[1]['request']->getRequestTarget());
        $this->assertEquals(
            '{"target_url":"https:\/\/example.com\/slashid\/webhook","name":"new_example","custom_headers":{"X-Extra-Check":["Value for the header"]}}',
            (string) $historyContainer[1]['request']->getBody(),
        );

        // Checks the resquest to the list of triggers.
        $this->assertEquals('GET', $historyContainer[2]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe/triggers', $historyContainer[2]['request']->getRequestTarget());
    }

    /**
     * Tests deleteById().
     */
    public function testDeleteById(): void
    {
        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [204, null],
        ]);
        $webhook->deleteById('065e3c7b-3480-7c16-8700-26b32cf383fe');

        // Checks the request to remove the webhook.
        $this->assertEquals('DELETE', $historyContainer[0]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe', $historyContainer[0]['request']->getRequestTarget());
    }

    /**
     * Tests deleteByUrl().
     */
    public function testDeleteByUrl(): void
    {
        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, self::INDEX_RAW_RESPONSE],
            [204, null],
        ]);
        $webhook->deleteByUrl('https://example.com/slashid/webhook');

        // Checks the request to remove the webhook.
        $this->assertEquals('DELETE', $historyContainer[1]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe', $historyContainer[1]['request']->getRequestTarget());
    }

    /**
     * Tests setWebhookTriggers().
     */
    public function testSetWebhookTriggers(): void
    {
        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, '{"result":[{"trigger_name":"SlashIDSDKLoaded_v1","trigger_type":"event"}]}'],
            [204, null],
            [201, '{}'],
        ]);

        $webhook->setWebhookTriggers('065e3c7b-3480-7c16-8700-26b32cf383fe', ['PersonCreated_v1']);

        // Checks the request to list a trigger.
        $this->assertEquals('GET', $historyContainer[0]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe/triggers', $historyContainer[0]['request']->getRequestTarget());

        // Checks the request to remove the old trigger.
        $this->assertEquals('DELETE', $historyContainer[1]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe/triggers?trigger_type=event&trigger_name=SlashIDSDKLoaded_v1', $historyContainer[1]['request']->getRequestTarget());

        // Checks the request to add the new trigger.
        $this->assertEquals('POST', $historyContainer[2]['request']->getMethod());
        $this->assertEquals('/organizations/webhooks/065e3c7b-3480-7c16-8700-26b32cf383fe/triggers', $historyContainer[0]['request']->getRequestTarget());
        $this->assertEquals(
            '{"trigger_type":"event","trigger_name":"PersonCreated_v1"}',
            (string) $historyContainer[2]['request']->getBody(),
        );
    }

    /**
     * Instantiates a WebhookAbstraction.
     */
    protected function webhook(array &$historyContainer, array $mockCalls): WebhookAbstraction
    {
        return new WebhookAbstraction($this->sdk($historyContainer, $mockCalls));
    }
}

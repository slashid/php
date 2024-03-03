<?php

namespace SlashId\Php\Abstraction;

use Beste\Cache\CacheItem;
use Beste\Cache\CacheKey;
use Beste\Cache\InMemoryCache;
use Crutch\DevClock\ClockWaited;
use Firebase\JWT\JWT;
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
     * Tests deleteByUrl() when the URL does not exist.
     */
    public function testDeleteByUrlNotExisting(): void
    {
        $this->expectExceptionMessage('There is no webhook in organization org_id for the URL "https://example.com/slashid/non_existing_webhook".');

        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, self::INDEX_RAW_RESPONSE],
        ]);
        $webhook->deleteByUrl('https://example.com/slashid/non_existing_webhook');
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
     * Tests decodeWebhookCall().
     */
    public function testDecodeWebhookCall(): void
    {
        $jwt = 'eyJhbGciOiJFUzI1NiIsICJraWQiOiJuTGtxV1EifQ.eyJhdWQiOiI0MTJlZGI1Ny1hZTI2LWYyYWEtMDY5OC03NzAwMjFlZDUyZDEiLCAiZXhwIjoxNzA5NDMzMTIwLCAiaWF0IjoxNzA5NDMxOTIwLCAiaXNzIjoiaHR0cHM6Ly9hcGkuc2FuZGJveC5zbGFzaGlkLmNvbSIsICJqdGkiOiIwMDAzYWVjNy0wYmNmLTQyMDQtOWJlYS0yZTJjMzcwY2E2MzkiLCAic3ViIjoiZWMxYzEzYTYtMWQ0MC00N2VmLWIyZmYtZTEzNTVjNTc1MGQwIiwgInRhcmdldF91cmwiOiJodHRwczovLzIxOWMtMjgwNC0xNGMtNDgzLTk4M2YtMjJjNi1mNjllLTZkMDYtZTNmZi5uZ3Jvay1mcmVlLmFwcC9zbGFzaGlkL3dlYmhvb2siLCAidHJpZ2dlcl9jb250ZW50Ijp7ImFuYWx5dGljc19tZXRhZGF0YSI6eyJhbmFseXRpY3NfY29ycmVsYXRpb25faWQiOiIwMDAwMDAwMC0wMDAwLTAwMDAtMDAwMC0wMDAwMDAwMDAwMDAiLCAiY2xpZW50X2lwX2FkZHJlc3MiOiIxODcuMTA2LjMzLjExNyJ9LCAiYnJvd3Nlcl9tZXRhZGF0YSI6eyJ1c2VyX2FnZW50IjoiTW96aWxsYS81LjAgKFgxMTsgVWJ1bnR1OyBMaW51eCB4ODZfNjQ7IHJ2OjEyMi4wKSBHZWNrby8yMDEwMDEwMSBGaXJlZm94LzEyMi4wIiwgIndpbmRvd19sb2NhdGlvbiI6Imh0dHA6Ly9sb2NhbGhvc3QvbG9naW4ifSwgImV2ZW50X21ldGFkYXRhIjp7ImV2ZW50X2lkIjoiZWMxYzEzYTYtMWQ0MC00N2VmLWIyZmYtZTEzNTVjNTc1MGQwIiwgImV2ZW50X25hbWUiOiJTbGFzaElEU0RLTG9hZGVkX3YxIiwgImV2ZW50X3R5cGUiOiJTbGFzaElEU0RLTG9hZGVkIiwgImV2ZW50X3ZlcnNpb24iOjEsICJvcmdhbml6YXRpb25faWQiOiI0MTJlZGI1Ny1hZTI2LWYyYWEtMDY5OC03NzAwMjFlZDUyZDEiLCAic291cmNlIjoyLCAidGltZXN0YW1wIjoiMjAyNC0wMy0wM1QwMjoxMjowMC4wNzQzNjY1NzJaIn19LCAidHJpZ2dlcl9uYW1lIjoiU2xhc2hJRFNES0xvYWRlZF92MSIsICJ0cmlnZ2VyX3R5cGUiOiJldmVudCIsICJ3ZWJob29rX2lkIjoiMDY1ZTNkYzUtYzViMi03ZTNmLWIxMDAtNjFmZGE4NzMyYjA3In0.YzOCFJfkniwCdagojfRUduiG-iGMrfx-Fq_F9X-XHuxAMqfcvY_O9B_mW1YIyJ5CAdcyMTWZVQLT_IMm8BVoyA';
        $keys = '{"keys":[{"alg":"ES256", "crv":"P-256", "key_ops":["verify"], "kid":"nLkqWQ", "kty":"EC", "use":"sig", "x":"vnt1e8sfnhzj_DhV-F-nSMm0UknhiwBdfkFE-VaCuUY", "y":"8XPZ4mdsUkMMTqSVh2UoCtJ_E-IkpmbYtyqgmk-MjfI"}]}';

        $historyContainer = [];
        $webhook = $this->webhook($historyContainer, [
            [200, $keys],
        ]);

        JWT::$timestamp = 1709433100;
        $cache = new InMemoryCache(new ClockWaited());
        $decoded = $webhook->decodeWebhookCall($jwt, $cache);

        // Tests one arbitrary value.
        $this->assertEquals('ec1c13a6-1d40-47ef-b2ff-e1355c5750d0', $decoded['trigger_content']['event_metadata']['event_id']);
    }

    /**
     * Instantiates a WebhookAbstraction.
     */
    protected function webhook(array &$historyContainer, array $mockCalls): WebhookAbstraction
    {
        return $this->sdk($historyContainer, $mockCalls)->webhook();
    }
}

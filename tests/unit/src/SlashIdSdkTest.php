<?php

namespace SlashId\Php;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SlashId\Php\Abstraction\WebhookAbstraction;

class SlashIdSdkTest extends TestCase
{
    /**
     * Tests invalid environment on __construct().
     */
    public function testEnvironmentOnConstruct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid environment "invalid_env". Valid options are: SlashIdSdk::ENVIRONMENT_PRODUCTION or SlashIdSdk::ENVIRONMENT_SANDBOX.');
        $this->sdk(null, null, 'invalid_env');
    }

    /**
     * Tests getOrganizationId().
     */
    public function testGetOrganizationId(): void
    {
        $sdk = new SlashIdSdk(SlashIdSdk::ENVIRONMENT_PRODUCTION, 'org_id', 'api_key');
        $this->assertEquals('org_id', $sdk->getOrganizationId());
    }

    /**
     * Tests getApiUrl().
     */
    public function testGetApiUrl(): void
    {
        $this->assertEquals('https://api.slashid.com', $this->sdk()->getApiUrl());
        $this->assertEquals('https://api.sandbox.slashid.com', $this->sdk(null, null, SlashIdSdk::ENVIRONMENT_SANDBOX)->getApiUrl());
    }

    /**
     * Tests webhook().
     */
    public function testWebhook(): void
    {
        $sdk = $this->sdk();
        $webhook = $sdk->webhook();
        $this->assertInstanceOf(WebhookAbstraction::class, $webhook);

        // Tests that the class is instantiated just once.
        $resultOfSecondCall = $sdk->webhook();
        $this->assertEquals(spl_object_hash($webhook), spl_object_hash($resultOfSecondCall));
    }

    /**
     * Data provider for testRequests().
     */
    public static function dataProviderTestRequests(): array
    {
        // Real data from the sandbox web service.
        $person = '{"result":{"active":true,"person_id":"0659dd31-7e38-7d1e-8704-e3b8b6966176","region":"us-iowa","roles":[]}}';
        $personData = [
            'active' => true,
            'person_id' => '0659dd31-7e38-7d1e-8704-e3b8b6966176',
            'region' => 'us-iowa',
            'roles' => [],
        ];
        $fullPerson = '{"result":{"active":true,"attributes":{},"groups":["Editor"],"handles":[{"type":"email_address","value":"test@example.com"}],"person_id":"0659dd31-7e38-7d1e-8704-e3b8b6966176","region":"us-iowa","roles":[]}}';
        $fullPersonData = [
            'active' => true,
            'attributes' => [],
            'groups' => ['Editor'],
            'handles' => [
                [
                    'type' => 'email_address',
                    'value' => 'test@example.com',
                ],
            ],
            'person_id' => '0659dd31-7e38-7d1e-8704-e3b8b6966176',
            'region' => 'us-iowa',
            'roles' => [],
        ];
        $webhook = '{"result":{"description":"","id":"065e3a24-20c9-782c-9100-6bb43827c7ba","name":"example","target_url":"https://example.com/slashid/webhook","timeout":"0s"}}';
        $webhookRequest = [
            'name' => 'example',
            'target_url' => 'https://example.com/slashid/webhook',
        ];
        $webhookData = [
            'description' => '',
            'id' => '065e3a24-20c9-782c-9100-6bb43827c7ba',
            'name' => 'example',
            'target_url' => 'https://example.com/slashid/webhook',
            'timeout' => '0s',
        ];
        $consents = '{"result":{"consents":[{"consent_level":"none","created_at":"2024-03-02T22:17:31.97481Z"}]}}';
        $consentsData = [
            'consents' => [
                [
                    'consent_level' => 'none',
                    'created_at' => '2024-03-02T22:17:31.97481Z',
                ],
            ],
        ];

        return [
            'successful GET without query' => [
                'get', '/persons/065e39a0-1796-7531-9204-400cf0ba82c6', null, 200, $person, '/persons/065e39a0-1796-7531-9204-400cf0ba82c6', $personData,
            ],
            'successful GET with query' => [
                'get', '/persons/065e39a0-1796-7531-9204-400cf0ba82c6', ['fields' => ['handles', 'groups', 'attributes']], 200, $fullPerson, '/persons/065e39a0-1796-7531-9204-400cf0ba82c6?fields=handles%2Cgroups%2Cattributes', $fullPersonData,
            ],
            'successful POST' => [
                'post', '/organization/webhooks', $webhookRequest, 201, $webhook, '/organization/webhooks', $webhookData,
            ],
            'successful PATCH' => [
                'patch', '/organization/webhooks', $webhookRequest, 201, $webhook, '/organization/webhooks', $webhookData,
            ],
            'successful PUT' => [
                'put', '/persons/065e39a0-1796-7531-9204-400cf0ba82c6/consent/gdpr', ['consent_levels' => ['none']], 200, $consents, '/persons/065e39a0-1796-7531-9204-400cf0ba82c6/consent/gdpr', $consentsData,
            ],
            'successful DELETE' => [
                'delete', '/person/065e39a0-1796-7531-9204-400cf0ba82c6', null, 204, '', '/person/065e39a0-1796-7531-9204-400cf0ba82c6', null,
            ],
        ];
    }

    /**
     * Tests get(), post(), patch(), put(), delete().
     *
     * @dataProvider dataProviderTestRequests
     */
    public function testRequests(string $method, string $targetUrl, ?array $queryOrBody, int $responseHttpCode, string $responseBody, string $expectedRequestUrl, ?array $expectedResult): void
    {
        $mockHandler = $this->mockHandler($responseHttpCode, $responseBody);
        $historyContainer = [];
        $history = Middleware::history($historyContainer);
        $sdk = $this->sdk($mockHandler, $history);

        $result = $sdk->{$method}($targetUrl, $queryOrBody);

        /** @var \GuzzleHttp\Psr7\Request */
        $request = $historyContainer[0]['request'];

        // Checks results.
        $this->assertEquals($expectedRequestUrl, $request->getRequestTarget());
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Instantiates a SlashIdSdk class.
     */
    protected function sdk(?MockHandler $mockHandler = null, ?callable $history = null, string $environment = SlashIdSdk::ENVIRONMENT_PRODUCTION): SlashIdSdk
    {
        $handlerStack = HandlerStack::create($mockHandler);
        if ($history) {
            $handlerStack->push($history);
        }

        return new SlashIdSdk($environment, 'org_id', 'api_key', $handlerStack);
    }

    /**
     * Instantiates a MockHandler.
     */
    protected function mockHandler(int $httpCode, ?string $data): MockHandler
    {
        return new MockHandler([
            new Response(
                $httpCode,
                [
                    'Content-Type' => 'application/json',
                ],
                $data,
            ),
        ]);
    }
}

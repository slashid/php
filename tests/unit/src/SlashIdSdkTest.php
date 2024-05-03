<?php

namespace SlashId\Test\Php;

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use SlashId\Php\Abstraction\MigrationAbstraction;
use SlashId\Php\Abstraction\TokenAbstraction;
use SlashId\Php\Abstraction\WebhookAbstraction;
use SlashId\Php\Exception\AccessDeniedException;
use SlashId\Php\Exception\BadRequestException;
use SlashId\Php\Exception\ConflictException;
use SlashId\Php\Exception\IdNotFoundException;
use SlashId\Php\Exception\InvalidEndpointException;
use SlashId\Php\Exception\UnauthorizedException;
use SlashId\Php\SlashIdSdk;
use SlashId\Test\Php\TestHelper\SdkInstantiationTrait;

class SlashIdSdkTest extends TestCase
{
    use SdkInstantiationTrait;

    /**
     * Tests invalid environment on __construct().
     */
    public function testEnvironmentOnConstruct(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid environment "invalid_env". Valid options are: SlashIdSdk::ENVIRONMENT_PRODUCTION or SlashIdSdk::ENVIRONMENT_SANDBOX.');
        $this->sdk(environment: 'invalid_env');
    }

    /**
     * Tests getEnvironment().
     */
    public function testGetEnvironment(): void
    {
        $this->assertEquals(SlashIdSdk::ENVIRONMENT_PRODUCTION, $this->sdk()->getEnvironment());
        $this->assertEquals(SlashIdSdk::ENVIRONMENT_SANDBOX, $this->sdk(environment: SlashIdSdk::ENVIRONMENT_SANDBOX)->getEnvironment());
    }

    /**
     * Tests getOrganizationId().
     */
    public function testGetOrganizationId(): void
    {
        $this->assertEquals('org_id', $this->sdk()->getOrganizationId());
    }

    /**
     * Tests getApiKey().
     */
    public function testGetApiKey(): void
    {
        $this->assertEquals('api_key', $this->sdk()->getApiKey());
    }

    /**
     * Tests getApiUrl().
     */
    public function testGetApiUrl(): void
    {
        $this->assertEquals('https://api.slashid.com', $this->sdk()->getApiUrl());
        $this->assertEquals('https://api.sandbox.slashid.com', $this->sdk(environment: SlashIdSdk::ENVIRONMENT_SANDBOX)->getApiUrl());
    }

    /**
     * Tests migration().
     */
    public function testMigration(): void
    {
        $sdk = $this->sdk();
        $migration = $sdk->migration();
        $this->assertInstanceOf(MigrationAbstraction::class, $migration);

        // Tests that the class is instantiated just once.
        $resultOfSecondCall = $sdk->migration();
        $this->assertEquals(spl_object_hash($migration), spl_object_hash($resultOfSecondCall));
    }

    /**
     * Tests token().
     */
    public function testToken(): void
    {
        $sdk = $this->sdk();
        $token = $sdk->token();
        $this->assertInstanceOf(TokenAbstraction::class, $token);

        // Tests that the class is instantiated just once.
        $resultOfSecondCall = $sdk->token();
        $this->assertEquals(spl_object_hash($token), spl_object_hash($resultOfSecondCall));
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
     * Data provider for testRequest().
     */
    public static function dataProviderTestRequest(): array
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
     * @dataProvider dataProviderTestRequest
     */
    public function testRequest(string $method, string $targetUrl, ?array $queryOrBody, int $responseHttpCode, string $responseBody, string $expectedRequestUrl, ?array $expectedResult): void
    {
        $historyContainer = [];
        $sdk = $this->sdk($historyContainer, [[$responseHttpCode, $responseBody]]);

        $result = $sdk->{$method}($targetUrl, $queryOrBody);

        /** @var \GuzzleHttp\Psr7\Request */
        $request = $historyContainer[0]['request'];

        // Checks results.
        $this->assertEquals($expectedRequestUrl, $request->getRequestTarget());
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Data provider for testRequestException().
     */
    public static function dataProviderTestRequestException(): array
    {
        return [
            [
                '/persons/aaa',
                400,
                '{"errors":[{"httpcode":400,"message":"invalid person_id"}]}',
                BadRequestException::class,
                'invalid person_id at GET /persons/aaa',
            ],
            [
                '/persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
                401,
                '',
                UnauthorizedException::class,
                'Unauthorized, please check the API Key and the Organization ID at GET /persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
            ],
            [
                '/persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
                403,
                '{"errors":[{"httpcode":403,"message":"access denied"}]}',
                AccessDeniedException::class,
                'Access has been denied: access denied at GET /persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
            ],
            [
                '/persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
                404,
                '{"errors":[{"httpcode":404,"message":"could not find person 065e3eb6-ee23-7e63-a104-ea0c0824a1a4"}]}',
                IdNotFoundException::class,
                'could not find person 065e3eb6-ee23-7e63-a104-ea0c0824a1a4 at GET /persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
            ],
            [
                '/invalid/endpoint',
                404,
                '404 page not found',
                InvalidEndpointException::class,
                'Could not find endpoint at GET /invalid/endpoint',
            ],
            // In real life, the 409 error will happen only in a POST.
            [
                '/persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
                409,
                '{"errors":[{"httpcode":409,"message":"person exists"}]}',
                ConflictException::class,
                'person exists at GET /persons/065e3eb6-ee23-7e63-a104-ea0c0824a1a4',
            ],
            // Tests an exception that does not actually exist.
            [
                '/unknown/exception',
                402,
                '',
                ClientException::class,
                'Client error: `GET https://api.slashid.com/unknown/exception` resulted in a `402 Payment Required` response',
            ],
        ];
    }

    /**
     * Tests exceptions in request().
     *
     * @dataProvider dataProviderTestRequestException
     */
    public function testRequestException(string $targetUrl, int $responseHttpCode, string $responseBody, string $expectedException, string $expectedExceptionMessage): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $historyContainer = [];
        $sdk = $this->sdk($historyContainer, [[$responseHttpCode, $responseBody]]);

        $sdk->get($targetUrl);
    }
}

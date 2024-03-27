<?php

namespace SlashId\Test\Php\Abstraction;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SlashId\Php\Abstraction\MigrationAbstraction;
use SlashId\Php\PersonInterface;
use SlashId\Php\SlashIdSdk;

/**
 * @covers \SlashId\Php\Abstraction\MigrationAbstraction
 */
class MigrationAbstractionTest extends TestCase
{
    public function testMigrateUsers(): void
    {
        $client = $this->createMock(Client::class);
        $sdk = $this->createConfiguredStub(SlashIdSdk::class, [
            'getClient' => $client,
        ]);
        $migration = new MigrationAbstraction($sdk);
        $person = $this->createConfiguredStub(PersonInterface::class, [
            'getEmailAddresses' => ['test@example.com'],
            'getPhoneNumbers' => ['+5511999888777'],
            'getRegion' => 'us-iowa',
            'getGroups' => ['Admin', 'Editor'],
            'getAllAttributes' => [
                PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS => ['property' => 'value'],
            ],
            'getLegacyPasswordToMigate' => '$PP$AAAAA',
        ]);

        $csv = '"slashid:emails","slashid:phone_numbers","slashid:region","slashid:roles","slashid:groups","slashid:attributes","slashid:password"
"test@example.com","+5511999888777","us-iowa","","Admin,Editor","{""end_user_no_access"":{""property"":""value""}}","$PP$AAAAA"
';

        $result = [
            'failed_csv' => 'CSV_CONTENTS',
            'successful_imports' => 10,
            'failed_imports' => 5,
        ];

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->identicalTo('POST'),
                $this->identicalTo('/persons/bulk-import'),
                $this->identicalTo([
                    'multipart' => [
                        [
                            'name' => 'persons',
                            'contents' => $csv,
                            'filename' => 'persons.csv',
                        ],
                    ],
                ]),
            )
            ->willReturn(new Response(body: json_encode([
                'result' => $result,
            ])));

        $response = $migration->migratePersons([$person]);

        $this->assertEquals($result, $response);
    }
}

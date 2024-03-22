<?php

namespace SlashId\Test\Php\Abstraction;

use GuzzleHttp\ClientInterface;
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
        $client = $this->createMock(ClientInterface::class);
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
            );

        $migration->migratePersons([$person]);
    }
}

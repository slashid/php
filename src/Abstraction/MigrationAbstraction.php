<?php

namespace SlashId\Php\Abstraction;

use GuzzleHttp\Psr7\Response;
use SlashId\Php\PersonInterface;

class MigrationAbstraction extends AbstractionBase
{
    public function migrateUsers(array $users): Response
    {
        // Write to CSV.
        $csvLines = array_merge(
            [
                [
                    'slashid:emails',
                    'slashid:phone_numbers',
                    'slashid:region',
                    'slashid:roles',
                    'slashid:groups',
                    'slashid:attributes',
                    // 'slashid:password',
                ],
            ],
            array_map(
                fn(PersonInterface $person) => [
                    implode(',', $person->getEmailAddresses()),
                    implode(',', $person->getPhoneNumbers()),
                    $person->getRegion() ?? '',
                    '',
                    implode(',', $person->getGroups()),
                    json_encode($person->getAllAttributes()) ?: '',
                    // $person->getLegacyPasswordToMigate() ?? '',
                ],
                $users,
            ),
        );

        $csv = implode(
            "\n",
            array_map(
                fn($line) => '"' . implode('","', array_map(fn($column) => str_replace('"', '""', $column), $line)) . '"',
                $csvLines,
            ),
        ) . "\n";

        return $this->sdk->getClient()->request('POST', '/persons/bulk-import', [
            'multipart' => [
                [
                    'name' => 'persons',
                    'contents' => $csv,
                    'filename' => 'persons.csv',
                ],
            ],
        ]);
    }
}

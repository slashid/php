<?php

namespace SlashId\Test\Php;

use PHPUnit\Framework\TestCase;
use SlashId\Php\Person;
use SlashId\Php\PersonInterface;

/**
 * @covers \SlashId\Php\Person
 */
class PersonTest extends TestCase
{
    public static function dataProviderTestFromValues(): array
    {
        $emailHandle = [
            'type' => 'email_address',
            'value' => 'test@example.com',
        ];
        $phoneHandle = [
            'type' => 'phone_number',
            'value' => '+5511999999999',
        ];

        return [
            [[], false, false],
            [[$emailHandle], true, false],
            [[$phoneHandle], false, true],
            [[$emailHandle, $phoneHandle], true, true],
        ];
    }

    /**
     * Tests fromValues().
     *
     * @dataProvider dataProviderTestFromValues
     */
    public function testFromValues(array $handles, bool $hasEmail, bool $hasPhone): void
    {
        $values = [
            'active' => true,
            'attributes' => [PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY => ['name' => 'John']],
            'groups' => ['Admin', 'Editor'],
            'handles' => $handles,
            'person_id' => '0659dd31-7e38-7d1e-8704-e3b8b6966176',
            'region' => 'us-iowa',
            'roles' => [],
        ];

        $person = Person::fromValues($values);
        $this->assertEquals('0659dd31-7e38-7d1e-8704-e3b8b6966176', $person->getPersonId());
        $this->assertTrue($person->isActive());
        $this->assertEquals('us-iowa', $person->getRegion());
        $this->assertEquals(['name' => 'John'], $person->getBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY));
        $this->assertEquals(['Admin', 'Editor'], $person->getGroups());
        if ($hasEmail) {
            $this->assertEquals(['test@example.com'], $person->getEmailAddresses());
        } else {
            $this->assertEmpty($person->getEmailAddresses());
        }
        if ($hasPhone) {
            $this->assertEquals(['+5511999999999'], $person->getPhoneNumbers());
        } else {
            $this->assertEmpty($person->getPhoneNumbers());
        }
    }

    /**
     * Tests isActive()/setActive().
     */
    public function testActive(): void
    {
        $firstUser = new Person();
        $this->assertTrue($firstUser->isActive());

        $secondUser = new Person(null, false);
        $this->assertFalse($secondUser->isActive());

        $firstUser->setActive(false);
        $this->assertFalse($firstUser->isActive());

        $secondUser->setActive(true);
        $this->assertTrue($secondUser->isActive());
    }

    /**
     * Tests getRegion()/setRegion().
     */
    public function testRegion(): void
    {
        $person = new Person();
        $this->assertNull($person->getRegion());

        $person = new Person(region: 'us-iowa');
        $this->assertEquals('us-iowa', $person->getRegion());
        $person->setRegion('europe-belgium');
        $this->assertEquals('europe-belgium', $person->getRegion());
    }

    /**
     * Tests getEmailAddress()/setEmailAddress().
     */
    public function testEmailAddress(): void
    {
        $person = new Person();
        $this->assertEmpty($person->getEmailAddresses());
        $person->setEmailAddresses(['test@example.com']);
        $this->assertEquals(['test@example.com'], $person->getEmailAddresses());
        $person->addEmailAddress('test@example.com');
        $person->addEmailAddress('test2@example.com');
        $this->assertEquals(['test@example.com', 'test2@example.com'], $person->getEmailAddresses());
    }

    /**
     * Tests getPhoneNumber()/setPhoneNumber().
     */
    public function testPhoneNumber(): void
    {
        $person = new Person();
        $this->assertEmpty($person->getPhoneNumbers());
        $person->setPhoneNumbers(['+5511999999999']);
        $this->assertEquals(['+5511999999999'], $person->getPhoneNumbers());
        $person->addPhoneNumber('+5511999999999');
        $person->addPhoneNumber('+5511999999998');
        $this->assertEquals(['+5511999999999', '+5511999999998'], $person->getPhoneNumbers());
    }

    /**
     * Tests getLegacyPasswordToMigate()/setLegacyPasswordToMigate().
     */
    public function testLegacyPasswordToMigate(): void
    {
        $person = new Person();
        $this->assertEmpty($person->getPhoneNumbers());
        $person->setLegacyPasswordToMigate('$PP$AAAA');
        $this->assertEquals('$PP$AAAA', $person->getLegacyPasswordToMigate());
    }

    /**
     * Tests getAllAttributes()/setAllAttributes()/getBucketAttributes()/setBucketAttributes()/getAttribute()/setAttribute().
     */
    public function testAttributes(): void
    {
        $person = new Person();
        $this->assertEmpty($person->getAllAttributes());
        $person->setAllAttributes([PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY => ['name' => 'John']]);
        $this->assertEquals(['name' => 'John'], $person->getBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY));
        $person->setBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE, ['nickname' => 'Johnny']);
        $this->assertEquals([
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY => ['name' => 'John'],
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE => ['nickname' => 'Johnny'],
        ], $person->getAllAttributes());
        $person->setAttribute(PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY, 'middle_name', 'Charles');
        $person->setAttribute(PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY, 'last_name', 'Smith');
        $this->assertEquals('Smith', $person->getAttribute(PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY, 'last_name'));
        $this->assertEquals([
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY => ['name' => 'John'],
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE => ['nickname' => 'Johnny'],
            PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY => [
                'middle_name' => 'Charles',
                'last_name' => 'Smith',
            ],
        ], $person->getAllAttributes());
        $person->deleteAttribute(PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY, 'middle_name');
        $this->assertEquals([
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY => ['name' => 'John'],
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE => ['nickname' => 'Johnny'],
            PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY => [
                'last_name' => 'Smith',
            ],
        ], $person->getAllAttributes());
        $person->deleteBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE);
        $this->assertEquals([
            PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY => ['name' => 'John'],
            PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY => [
                'last_name' => 'Smith',
            ],
        ], $person->getAllAttributes());
    }

    /**
     * Data provider for testAttributesException().
     */
    public static function dataProviderTestAttributesException(): array
    {
        $bucketNameMessage = 'The parameter "invalid_bucket" is not a valid bucket name. Valid bucket names are: end_user_no_access, end_user_read_only, end_user_read_write, person_pool-end_user_no_access, person_pool-end_user_read_only, person_pool-end_user_read_write.';
        $attributeNamesMessage = 'The attributes array must be indexed by strings.';

        return [
            ['setAllAttributes', [['invalid_bucket' => ['name' => 'John']]], $bucketNameMessage],
            ['setAllAttributes', [[PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS => [123]]], $attributeNamesMessage],
            ['getBucketAttributes', ['invalid_bucket'], $bucketNameMessage],
            ['setBucketAttributes', ['invalid_bucket', []], $bucketNameMessage],
            ['setBucketAttributes', [PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS, [123]], $attributeNamesMessage],
            ['deleteBucketAttributes', ['invalid_bucket'], $bucketNameMessage],
            ['getAttribute', ['invalid_bucket', 'attribute'], $bucketNameMessage],
            ['setAttribute', ['invalid_bucket', 'attribute', 123], $bucketNameMessage],
            ['deleteAttribute', ['invalid_bucket', 'attribute'], $bucketNameMessage],
        ];
    }

    /**
     * Tests invalid bucket names.
     *
     * @dataProvider dataProviderTestAttributesException
     */
    public function testAttributesException(string $methodName, array $parameters): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $person = new Person();
        $person->$methodName(...$parameters);
    }

    /**
     * Tests getGroups()/setGroups().
     */
    public function testGroups(): void
    {
        $person = new Person();
        $this->assertEmpty($person->getGroups());
        $person->setGroups(['Admin', 'Editor']);
        $this->assertEquals(['Admin', 'Editor'], $person->getGroups());

        $person->setGroups(['indexes_will_be_ignored' => 'Admin']);
        $this->assertEquals(['Admin'], $person->getGroups());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $groups parameter must be a list of strings.');
        $person->setGroups([123]);
    }

    /**
     * Tests group-checking methods.
     */
    public function testGroupChecking(): void
    {
        $person = new Person();
        $person->setGroups(['Admin', 'Editor']);
        $this->assertTrue($person->hasGroup('Editor'));
        $this->assertFalse($person->hasGroup('Manager'));
        $this->assertTrue($person->hasAnyGroup(['Editor', 'Manager']));
        $this->assertTrue($person->hasAnyGroup(['Editor', 'Admin']));
        $this->assertTrue($person->hasAnyGroup(['Editor']));
        $this->assertFalse($person->hasAnyGroup(['Manager', 'Reviewer']));
        $this->assertFalse($person->hasAnyGroup(['Manager']));
        $this->assertTrue($person->hasAllGroups(['Admin']));
        $this->assertTrue($person->hasAllGroups(['Admin', 'Editor']));
        $this->assertTrue($person->hasAllGroups(['Editor', 'Admin']));
        $this->assertTrue($person->hasAllGroups(['Admin']));
        $this->assertFalse($person->hasAllGroups(['Editor', 'Manager']));
        $this->assertFalse($person->hasAllGroups(['Admin', 'Manager']));
        $this->assertFalse($person->hasAllGroups(['Manager']));
    }
}

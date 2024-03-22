<?php

namespace SlashId\Php;

class Person implements PersonInterface
{
    /**
     * The email address, if it exists.
     *
     * In an API response or a token, the phone number will look something like this:
     * {"handles":[{"type":"email_address","value":"user@example.com"}]}
     *
     * @var string[]
     */
    protected array $emailAddresses = [];

    /**
     * The phone number, if it exists.
     *
     * In an API response or a token, the phone number will look something like this:
     * {"handles":[{"type":"phone_number","value":"+5519999999999"}]}
     *
     * @var string[]
     */
    protected array $phoneNumbers = [];

    /**
     * The attributes of a user, indexed by bucket.
     *
     * @var array<string, array<string, string|int|mixed[]|null>>
     */
    protected array $attributes = [];

    /**
     * Password hash used. The property is used for user migrations only.
     */
    protected ?string $legacyPasswordToMigate;

    /**
     * The groups of the user.
     *
     * @var string[]
     */
    protected array $groups = [];

    public function __construct(
        protected ?string $personId = null,
        protected bool $isActive = true,
        protected ?string $region = null,
    ) {}

    public static function fromValues(array $values): static
    {
        $user = new static($values['person_id'], $values['active'], $values['region']);

        $user
            ->setGroups($values['groups'])
            ->setAllAttributes($values['attributes']);

        foreach ($values['handles'] as $handle) {
            if ('email_address' === $handle['type']) {
                $user->addEmailAddress($handle['value']);
            }
            if ('phone_number' === $handle['type']) {
                $user->addPhoneNumber($handle['value']);
            }
        }

        return $user;
    }

    // **********************
    // ** Get/Set methods. **
    // **********************

    public function getPersonId(): ?string
    {
        return $this->personId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getEmailAddresses(): array
    {
        return $this->emailAddresses;
    }

    public function addEmailAddress(string $emailAddress): static
    {
        $this->emailAddresses[] = $emailAddress;
        $this->emailAddresses = array_unique($this->emailAddresses);

        return $this;
    }

    public function setEmailAddresses(array $emailAddresses): static
    {
        $this->assertStringArray('$emailAddresses', $emailAddresses);
        $this->emailAddresses = $emailAddresses;

        return $this;
    }

    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }

    public function addPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumbers[] = $phoneNumber;
        $this->phoneNumbers = array_unique($this->phoneNumbers);

        return $this;
    }

    public function setPhoneNumbers(array $phoneNumbers): static
    {
        $this->assertStringArray('$phoneNumbers', $phoneNumbers);
        $this->phoneNumbers = $phoneNumbers;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): static
    {
        $this->assertStringArray('$groups', $groups);
        $this->groups = array_values($groups);

        return $this;
    }

    public function getLegacyPasswordToMigate(): ?string
    {
        return $this->legacyPasswordToMigate ?? null;
    }

    public function setLegacyPasswordToMigate(?string $legacyPasswordToMigate): static
    {
        $this->legacyPasswordToMigate = $legacyPasswordToMigate;

        return $this;
    }

    // ********************************
    // ** Attribute-related methods. **
    // ********************************

    public function getAllAttributes(): array
    {
        return $this->attributes;
    }

    public function setAllAttributes(array $attributes): static
    {
        foreach ($attributes as $bucket => $bucketAttributes) {
            $this->assertBucketName($bucket);
            $this->assertAttributeNames($bucketAttributes);
        }

        $this->attributes = $attributes;

        return $this;
    }

    public function getBucketAttributes(string $bucket): ?array
    {
        $this->assertBucketName($bucket);

        return $this->attributes[$bucket] ?? null;
    }

    public function setBucketAttributes(string $bucket, array $attributes): static
    {
        $this->assertBucketName($bucket);
        $this->assertAttributeNames($attributes);
        $this->attributes[$bucket] = $attributes;

        return $this;
    }

    public function deleteBucketAttributes(string $bucket): static
    {
        $this->assertBucketName($bucket);
        unset($this->attributes[$bucket]);

        return $this;
    }

    public function getAttribute(string $bucket, string $attribute): string|int|array|null
    {
        $this->assertBucketName($bucket);

        return $this->attributes[$bucket][$attribute] ?? null;
    }

    public function setAttribute(string $bucket, string $attribute, string|int|array $value): static
    {
        $this->assertBucketName($bucket);
        $this->attributes[$bucket][$attribute] = $value;

        return $this;
    }

    public function deleteAttribute(string $bucket, string $attribute): static
    {
        $this->assertBucketName($bucket);
        unset($this->attributes[$bucket][$attribute]);

        return $this;
    }

    // *****************************
    // ** Group-checking methods. **
    // *****************************

    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->getGroups());
    }

    public function hasAnyGroup(array $groups): bool
    {
        return (bool) count(array_intersect($groups, $this->getGroups()));
    }

    public function hasAllGroups(array $groups): bool
    {
        return !count(array_diff($groups, $this->getGroups()));
    }

    // ************************
    // ** Protected methods. **
    // ************************

    /**
     * @param string  $parameterName the name of the parameter to use in the exception
     * @param mixed[] $strings       the strings to check
     */
    protected function assertStringArray(string $parameterName, array $strings): void
    {
        foreach ($strings as $string) {
            if (!is_string($string)) {
                throw new \InvalidArgumentException("The $parameterName parameter must be a list of strings.");
            }
        }
    }

    /**
     * @param array-key $bucket the bucket name to check
     */
    protected function assertBucketName(mixed $bucket): void
    {
        if (!is_string($bucket) || !in_array($bucket, self::BUCKET_NAMES)) {
            throw new \InvalidArgumentException("The parameter \"$bucket\" is not a valid bucket name. Valid bucket names are: " . implode(', ', self::BUCKET_NAMES) . '.');
        }
    }

    /**
     * @param mixed[] $attributes the list of attributes to check the keys
     */
    protected function assertAttributeNames(array $attributes): void
    {
        foreach (array_keys($attributes) as $attributeName) {
            if (!is_string($attributeName)) {
                throw new \InvalidArgumentException('The attributes array must be indexed by strings.');
            }
        }
    }
}

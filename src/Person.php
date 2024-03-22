<?php

namespace SlashId\Php;

class Person
{
    const BUCKET_ORGANIZATION_END_USER_NO_ACCESS = 'end_user_no_access';
    const BUCKET_ORGANIZATION_END_USER_READ_ONLY = 'end_user_read_only';
    const BUCKET_ORGANIZATION_END_USER_READ_WRITE = 'end_user_read_write';
    const BUCKET_PERSON_POOL_END_USER_NO_ACCESS = 'person_pool-end_user_no_access';
    const BUCKET_PERSON_POOL_END_USER_READ_ONLY = 'person_pool-end_user_read_only';
    const BUCKET_PERSON_POOL_END_USER_READ_WRITE = 'person_pool-end_user_read_write';

    const BUCKET_NAMES = [
        self::BUCKET_ORGANIZATION_END_USER_NO_ACCESS,
        self::BUCKET_ORGANIZATION_END_USER_READ_ONLY,
        self::BUCKET_ORGANIZATION_END_USER_READ_WRITE,
        self::BUCKET_PERSON_POOL_END_USER_NO_ACCESS,
        self::BUCKET_PERSON_POOL_END_USER_READ_ONLY,
        self::BUCKET_PERSON_POOL_END_USER_READ_WRITE,
    ];

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
     * @var array<string, mixed[]>
     */
    protected array $attributes = [];

    /**
     * The groups of the user.
     *
     * @var string[]
     */
    protected array $groups = [];

    /**
     * @param  string|null  $id  The Person ID. In an API response or a token it will look like: {"person_id": "af5fbd30-7ce7-4548-8b30-4cd59cb2aba1"}.
     * @param  bool  $isActive  Whether the user is active. In an API response or a token it will look like: {"active": true}.
     * @param  string|null  $region  The Region. In an API response or a token it will look like: {"region": "us-iowa"}.
     */
    public function __construct(
        public ?string $id = null,
        protected bool $isActive = true,
        protected ?string $region = null,
    ) {
    }

    /**
     * @param  array{active: bool, person_id: string, roles: string[], attributes: mixed[], region: string, handles: array{type: string, value: string}[], groups: string[]}  $values
     */
    public static function fromValues(array $values): static
    {
        $user = new self($values['person_id'], $values['active'], $values['region']);

        $user
            ->setGroups($values['groups'])
            ->setAttributes($values['attributes']);

        foreach ($values['handles'] as $handle) {
            if (($handle['type'] === 'email_address')) {
                $user->addEmailAddress($handle['value']);
            }
            if (($handle['type'] === 'phone_number')) {
                $user->addPhoneNumber($handle['value']);
            }
        }

        return $user;
    }

    // **********************
    // ** Get/Set methods. **
    // **********************

    /**
     * @return bool Whether the user is to be active.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive Whether the user is to be active.
     *
     * @return static The class itself.
     */
    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return string[] A list of email addresses associated to the account.
     */
    public function getEmailAddresses(): array
    {
        return $this->emailAddresses;
    }

    /**
     * @return string $emailAddress An email addresses to add to the  list of emails associated to the account.
     *
     * @return static The class itself.
     */
    public function addEmailAddress(string $emailAddress): static
    {
        $this->emailAddresses[] = $emailAddress;
        $this->emailAddresses = array_unique($this->emailAddresses);

        return $this;
    }

    /**
     * @param  string[]  $emailAddresses A list of email addresses associated to the account.
     *
     * @return static The class itself.
     */
    public function setEmailAddresses(array $emailAddresses): static
    {
        $this->assertStringArray('$emailAddresses', $emailAddresses);
        $this->emailAddresses = $emailAddresses;

        return $this;
    }

    /**
     * @return string[] A list of phone numbers associated to the account.
     */
    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }

    /**
     * @param string $phoneNumber A phone number to add to the list of numbers associated to the account.
     *
     * @return static The class itself.
     */
    public function addPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumbers[] = $phoneNumber;
        $this->phoneNumbers = array_unique($this->phoneNumbers);

        return $this;
    }

    /**
     * @param  string[]  $phoneNumbers A list of phone numbers associated to the account.
     *
     * @return static The class itself.
     */
    public function setPhoneNumbers(array $phoneNumbers): static
    {
        $this->assertStringArray('$phoneNumbers', $phoneNumbers);
        $this->phoneNumbers = $phoneNumbers;

        return $this;
    }

    /**
     * @return string The region, one of us-iowa, europe-belgium, asia-japan, europe-england, australia-sydney.
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @param string $region The region, one of us-iowa, europe-belgium, asia-japan, europe-england, australia-sydney.
     *
     * @return static The class itself.
     */
    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string[] A list of groups, e.g. ['Editor', 'Admin'].
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param  string[]  $groups  A list of groups, e.g. ['Editor', 'Admin'].
     *
     * @return static The class itself.
     */
    public function setGroups(array $groups): static
    {
        $this->assertStringArray('$groups', $groups);
        $this->groups = array_values($groups);

        return $this;
    }


    // ********************************
    // ** Attribute-related methods. **
    // ********************************

    /**
     * @return array<string, array<string, mixed>> $attributes The user attributes, indexed by bucket.
     */
    public function getAllAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, array<string, mixed>> $attributes  The user attributes, indexed by bucket.
     *
     * @return static The class itself.
     */
    public function setAllAttributes(array $attributes): static
    {
        foreach (array_keys($attributes) as $bucket) {
            $this->assertBucketName($bucket);
        }

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param string $bucket The name of the bucket, one of self::BUCKET_*.
     *
     * @return array<string, mixed>|null $attributes The user attributes in $bucket.
     */
    public function getBucketAttributes(string $bucket): ?array
    {
        $this->assertBucketName($bucket);

        return $this->attributes[$bucket] ?? null;
    }

    /**
     * @param string $bucket The name of the bucket, one of self::BUCKET_*.
     * @param  array<string, mixed>  $attributes  The user attributes  in a given bucket.
     *
     * @return static The class itself.
     */
    public function setBucketAttributes(string $bucket, array $attributes): static
    {
        $this->assertBucketName($bucket);
        $this->attributes[$bucket] = $attributes;

        return $this;
    }

    /**
     * @param string $bucket The name of the bucket, one of self::BUCKET_*.
     *
     * @return static The class itself.
     */
    public function deleteBucketAttributes(string $bucket): static
    {
        $this->assertBucketName($bucket);
        unset($this->attributes[$bucket]);

        return $this;
    }

    /**
     * @param string $bucket The name of the bucket, one of self::BUCKET_*.
     * @param  array<string, mixed> $attributes  The user attributes.
     *
     * @return string|int|mixed[]|null The value of the attribute (or null if it doesn't exist).
     */
    public function getAttribute(string $bucket, string $attribute): string|int|array|null
    {
        $this->assertBucketName($bucket);
        return $this->attributes[$bucket][$attribute] ?? NULL;
    }

    /**
     * @param string $bucket The name of the bucket, one of self::BUCKET_*.
     * @param string $attribute The attribute name.
     * @param string|int|mixed[] The value of the attribute.
     *
     * @return static The class itself.
     */
    public function setAttribute(string $bucket, string $attribute, string|int|array $value): static
    {
        $this->assertBucketName($bucket);
        $this->attributes[$bucket][$attribute] = $value;

        return $this;
    }

    /**
     * @param string $bucket The name of the bucket, one of self::BUCKET_*.
     * @param string $attribute The attribute name.
     *
     * @return static The class itself.
     */
    public function deleteAttribute(string $bucket, string $attribute): static
    {
        $this->assertBucketName($bucket);
        unset($this->attributes[$bucket][$attribute]);

        return $this;
    }

    // *****************************
    // ** Group-checking methods. **
    // *****************************

    /**
     * Checks if the user is in a group.
     *
     * @return bool Whether the user is in a group or not.
     */
    public function hasGroup(string $group): bool
    {
        return in_array($group, $this->getGroups());
    }

    /**
     * Checks if the user is in ANY of the groups listed.
     *
     * @param  string[]  $groups  The list of groups to check, e.g. ['Editor', 'Admin'].
     * @return bool Whether the user is in ANY of the groups.
     */
    public function hasAnyGroup(array $groups): bool
    {
        return (bool) count(array_intersect($groups, $this->getGroups()));
    }

    /**
     * Checks if the user is in ALL of the groups listed.
     *
     * @param  string[]  $groups  The list of groups to check, e.g. ['Editor', 'Admin'].
     * @return bool Whether the user is in ALL of the groups.
     */
    public function hasAllGroups(array $groups): bool
    {
        return ! count(array_diff($groups, $this->getGroups()));
    }

    // ************************
    // ** Protected methods. **
    // ************************

    /**
     * @param  string  $parameterName  The name of the parameter to use in the exception.
     * @param  mixed[]  $strings  The strings to check.
     */
    protected function assertStringArray(string $parameterName, array $strings): void
    {
        foreach ($strings as $string) {
            if (! is_string($string)) {
                throw new \InvalidArgumentException("The $parameterName parameter must be a list of strings.");
            }
        }
    }

    /**
     * @param mixed $bucket The bucket name to check.
     */
    protected function assertBucketName(mixed $bucket): void
    {
        if (!is_string($bucket) || !in_array($bucket, self::))
    }
}

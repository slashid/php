<?php

namespace SlashId\Php;

interface PersonInterface
{
    public const BUCKET_ORGANIZATION_END_USER_NO_ACCESS = 'end_user_no_access';
    public const BUCKET_ORGANIZATION_END_USER_READ_ONLY = 'end_user_read_only';
    public const BUCKET_ORGANIZATION_END_USER_READ_WRITE = 'end_user_read_write';
    public const BUCKET_PERSON_POOL_END_USER_NO_ACCESS = 'person_pool-end_user_no_access';
    public const BUCKET_PERSON_POOL_END_USER_READ_ONLY = 'person_pool-end_user_read_only';
    public const BUCKET_PERSON_POOL_END_USER_READ_WRITE = 'person_pool-end_user_read_write';

    public const BUCKET_NAMES = [
        self::BUCKET_ORGANIZATION_END_USER_NO_ACCESS,
        self::BUCKET_ORGANIZATION_END_USER_READ_ONLY,
        self::BUCKET_ORGANIZATION_END_USER_READ_WRITE,
        self::BUCKET_PERSON_POOL_END_USER_NO_ACCESS,
        self::BUCKET_PERSON_POOL_END_USER_READ_ONLY,
        self::BUCKET_PERSON_POOL_END_USER_READ_WRITE,
    ];

    /**
     * @param string|null $personId The Person ID. In an API response or a token it will look like: {"person_id": "af5fbd30-7ce7-4548-8b30-4cd59cb2aba1"}.
     * @param bool        $isActive Whether the user is active. In an API response or a token it will look like: {"active": true}.
     * @param string|null $region   The Region. In an API response or a token it will look like: {"region": "us-iowa"}.
     */
    public function __construct(?string $personId = null, bool $isActive = true, ?string $region = null);

    /**
     * @param array{active: bool, person_id: string, roles: string[], attributes: array<string, array<string, string|int|mixed[]|null>>, region: string, handles: array{type: string, value: string}[], groups: string[]} $values
     */
    public static function fromValues(array $values): static;

    // **********************
    // ** Get/Set methods. **
    // **********************

    /**
     * @return string the person ID, such as af5fbd30-7ce7-4548-8b30-4cd59cb2aba1
     */
    public function getPersonId(): ?string;

    /**
     * @return bool whether the user is to be active
     */
    public function isActive(): bool;

    /**
     * @param bool $isActive whether the user is to be active
     *
     * @return static the class itself
     */
    public function setActive(bool $isActive): static;

    /**
     * @return string[] a list of email addresses associated to the account
     */
    public function getEmailAddresses(): array;

    /**
     * @return string $emailAddress an email addresses to add to the  list of emails associated to the account
     * @return static the class itself
     */
    public function addEmailAddress(string $emailAddress): static;

    /**
     * @param string[] $emailAddresses a list of email addresses associated to the account
     *
     * @return static the class itself
     */
    public function setEmailAddresses(array $emailAddresses): static;

    /**
     * @return string[] a list of phone numbers associated to the account
     */
    public function getPhoneNumbers(): array;

    /**
     * @param string $phoneNumber a phone number to add to the list of numbers associated to the account
     *
     * @return static the class itself
     */
    public function addPhoneNumber(string $phoneNumber): static;

    /**
     * @param string[] $phoneNumbers a list of phone numbers associated to the account
     *
     * @return static the class itself
     */
    public function setPhoneNumbers(array $phoneNumbers): static;

    /**
     * @return string the region, one of us-iowa, europe-belgium, asia-japan, europe-england, australia-sydney
     */
    public function getRegion(): ?string;

    /**
     * @param string $region the region, one of us-iowa, europe-belgium, asia-japan, europe-england, australia-sydney
     *
     * @return static the class itself
     */
    public function setRegion(string $region): static;

    /**
     * @return string[] A list of groups, e.g. ['Editor', 'Admin'].
     */
    public function getGroups(): array;

    /**
     * @param string[] $groups A list of groups, e.g. ['Editor', 'Admin'].
     *
     * @return static the class itself
     */
    public function setGroups(array $groups): static;

    // ********************************
    // ** Attribute-related methods. **
    // ********************************

    /**
     * @return array<string, array<string, string|int|mixed[]|null>> $attributes the user attributes, indexed by bucket
     */
    public function getAllAttributes(): array;

    /**
     * @param array<string, array<string, string|int|mixed[]|null>> $attributes the user attributes, indexed by bucket
     *
     * @return static the class itself
     */
    public function setAllAttributes(array $attributes): static;

    /**
     * @param string $bucket the name of the bucket, one of self::BUCKET_*
     *
     * @return array<string, string|int|mixed[]|null>|null $attributes the user attributes in $bucket
     */
    public function getBucketAttributes(string $bucket): ?array;

    /**
     * @param string                                 $bucket     the name of the bucket, one of self::BUCKET_*
     * @param array<string, string|int|mixed[]|null> $attributes the user attributes  in a given bucket
     *
     * @return static the class itself
     */
    public function setBucketAttributes(string $bucket, array $attributes): static;

    /**
     * @param string $bucket the name of the bucket, one of self::BUCKET_*
     *
     * @return static the class itself
     */
    public function deleteBucketAttributes(string $bucket): static;

    /**
     * @param string $bucket    the name of the bucket, one of self::BUCKET_*
     * @param string $attribute the attribute name
     *
     * @return string|int|mixed[]|null the value of the attribute (or null if it doesn't exist)
     */
    public function getAttribute(string $bucket, string $attribute): string|int|array|null;

    /**
     * @param string             $bucket    the name of the bucket, one of self::BUCKET_*
     * @param string             $attribute the attribute name
     * @param string|int|mixed[] $value     the value of the attribute
     *
     * @return static the class itself
     */
    public function setAttribute(string $bucket, string $attribute, string|int|array $value): static;

    /**
     * @param string $bucket    the name of the bucket, one of self::BUCKET_*
     * @param string $attribute the attribute name
     *
     * @return static the class itself
     */
    public function deleteAttribute(string $bucket, string $attribute): static;

    // *****************************
    // ** Group-checking methods. **
    // *****************************

    /**
     * Checks if the user is in a group.
     *
     * @return bool whether the user is in a group or not
     */
    public function hasGroup(string $group): bool;

    /**
     * Checks if the user is in ANY of the groups listed.
     *
     * @param string[] $groups The list of groups to check, e.g. ['Editor', 'Admin'].
     *
     * @return bool whether the user is in ANY of the groups
     */
    public function hasAnyGroup(array $groups): bool;

    /**
     * Checks if the user is in ALL of the groups listed.
     *
     * @param string[] $groups The list of groups to check, e.g. ['Editor', 'Admin'].
     *
     * @return bool whether the user is in ALL of the groups
     */
    public function hasAllGroups(array $groups): bool;
}

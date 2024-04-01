# SlashID SDK

## Installation

Install this library with composer:

```
composer require slashid/slashid-php
```

## Usage

### Instantiating the SDK

First, create a SDK instance, informing the following:

* `$environment`, either `sandbox` or `production`
* `$organizationId`, your organization's ID. You'll find it in your SlashID console (https://console.slashid.dev/ for production, https://console.sandbox.slashid.dev/ for sandbox), in the "Settings" tab, on the top of the page.
* `$apiKey`, your organization's ID. You'll also find it in your SlashID console, in the "Settings" tab, at the very bottom of the page.

```php
use SlashId\Php\SlashIdSdk;

$sdk = new SlashIdSdk(SlashIdSdk::ENVIRONMENT_PRODUCTION, '412edb57-ae26-f2aa-9999-770021ed52d1', 'z0dlY-nluiq8mcvm8YTolSkJV6e9');
```

### Making requests

You can do direct web service requests with the methods `->get`, `->post`, `->patch`, `->put`, `->delete`.

#### GET request

The `get` method can be used for both index-style endpoints and get-one endpoints.

```php
// GET https://api.slashid.com/persons
$response = $sdk->get('/persons');

// $response:
[
    ['person_id' => '1111-1111-1111', 'handles' => [...]],
    ['person_id' => '2222-2222-2222', 'handles' => [...]],
]
```

You can use the second argument to specify a query to the endpoint:

```php
// GET https://api.slashid.com/persons/1111-1111-1111?fields=handles,groups,attributes
$response = $sdk->get('/persons/1111-1111-1111', [
    'fields' => ['handles', 'groups', 'attributes'],
]);

// $response:
['person_id' => '1111-1111-1111', 'handles' => [...]]
```

#### POST, PATCH, PUT requests

For POST, PATCH, PUT requests, you need to pass a body as a second parameter:

```php
// POST https://api.slashid.com/persons
$response = $sdk->post('/persons', [
    'active' => true,
    'handles' => [
        [
            'type' => 'email_address',
            'value' => 'user@user.com'
        ],
    ],
]);

// $response:
['person_id' => '1111-1111-1111', 'handles' => [...]]
```

```php
// PATCH https://api.slashid.com/persons/1111-1111-1111
$response = $sdk->patch('/persons/1111-1111-1111', [
    'active' => true,
    'handles' => [
        [
            'type' => 'email_address',
            'value' => 'user@user.com'
        ],
    ],
]);

// $response:
['person_id' => '1111-1111-1111', 'handles' => [...]]
```

```php
// PUT https://api.slashid.com/persons/1111-1111-1111
$response = $sdk->put('/persons', [
    'active' => true,
    'handles' => [
        [
            'type' => 'email_address',
            'value' => 'user@user.com'
        ],
    ],
]);

// $response:
['person_id' => '1111-1111-1111', 'handles' => [...]]
```

#### DELETE requests

DELETE requests don't return anything:

```php
// DELETE https://api.slashid.com/persons/1111-1111-1111
$sdk->delete('/persons/1111-1111-1111');
```

Some endpoints require a query as well:

```php
$sdk->delete('/organizations/webhooks/99999-99999-99999/triggers', [
    'trigger_type' => 'event',
    'trigger_name' => 'PersonCreated_v1',
]);
```

#### `getClient()`

The methods `$sdk->get()`, `$sdk->post()`, `$sdk->patch()`, `$sdk->put()` and `$sdk->delete()` expect sending and receiving JSON, but some endpoints have special requirements, e.g. `GET /persons/bulk-import` ([Fetch the import CSV template](https://developer.slashid.dev/docs/api/get-persons-bulk-import)) will return a CSV and requires a `Accept: text/csv` header.

For those cases, you can use `$sdk->getClient()` to retrieve the underlying [GuzzlePHP](https://docs.guzzlephp.org) client with the proper credentials preset, for instance:

```php
$response = $sdk->getClient()->request('GET', '/persons/bulk-import', [
    'headers' => [
        'Accept' => 'text/csv',
    ],
]);

$csvContents = (string) $response->getBody();
```

### Exceptions

The following exceptions may be thrown in case of errors during the connection:

* `\SlashId\Php\Exception\BadRequestException` when the API returns a **400** error, meaning that the data you've sent to the request is malformed or missing the required information.
* `\SlashId\Php\Exception\UnauthorizedException` when the API returns a **401** error, meaning that either the Organization ID or API key is wrong. In this case, check your credentials.
* `\SlashId\Php\Exception\AccessDeniedException` when the API returns a **403** error, meaning you are not allowed to access a resource.
* `\SlashId\Php\Exception\InvalidEndpointException` when the API returns a **404** error due to you requesting an endpoint that does not exist. In this case, check the [API reference](https://developer.slashid.dev/docs/api).
* `\SlashId\Php\Exception\IdNotFoundException` when the API returns a **404** error on a valid endpoint, meaning the ID you've requested does not exist. This exception will happen on requests that include an ID in the URL, such as `/persons/1111-1111-1111`.
* `\SlashId\Php\Exception\ConflictException` when the API returns a **409** error, usually meaning you are trying to create a duplicated entity (e.g. a person with an email already belonging to an existing person). In this case, check the [API reference](https://developer.slashid.dev/docs/api) to see if there is an idempotent version of the endpoint.
* `\GuzzleHttp\Exception\ClientException` when the API returns any other **4xx** error.
* `\GuzzleHttp\Exception\BadResponseException` when the API returns any **5xx** error.
* Some implementation of `\GuzzleHttp\Exception\GuzzleException` if there is any other kind of error during the connection with the API server.

All of `\SlashId\Php\Exception` exceptions are descendants of `\GuzzleHttp\Exception\BadResponseException`, which means that you can use the following methods to learn about the causes of the error:

```php
// Gets an informative message about the error.
$exception->getMessage();

// Gets the request object, with information about the endpoint and the data in the request.
$request = $exception->getRequest();

// Gets the response object, with HTTP response code and the response contents.
$response = $exception->getResponse();

// Gets the response as text.
$responseText = (string) $exception->getResponse()->getBody();

// Gets the response as a parsed array.
$responseData = \json_decode((string) $exception->getResponse()->getBody(), true);
```

### `\SlashId\Php\PersonInterface` / `\SlashId\Php\Person`

The `Person` class is very useful for representing Person information coming from the `/persons/{id}` endpoint.

You can instantiate a class with `Person::fromValues`, using the response from the endpoint.

```php
use SlashId\Php\Exception\IdNotFoundException;
use SlashId\Php\Person;
use SlashId\Php\PersonInterface;

function getPerson($identifier): ?PersonInterface
    try {
        $response = $this->sdk->get('/persons/' . $identifier, [
            'fields' => ['handles', 'groups', 'attributes'],
        ]);

        return Person::fromValues($response);
    } catch (IdNotFoundException $exception) {
        return null;
    }
}
```

With that, you have several functions to read the person's data:

```php
// The ID, such as 9999-9999-9999. It can be null if the $person is created with `new Person()`.
$person->getPersonId();

// Whether the person is active.
$person->isActive();

// The email addresses associated with the account, such as ['email@example.com', 'email2@example.com'].
$person->getEmailAddresses();

// The phone numbers associated with the account, such as ['+199999999', '+44999999999'].
$person->getPhoneNumbers();

// The region, one of "us-iowa", "europe-belgium", "asia-japan", "europe-england", "australia-sydney".
$person->getRegion();

// The groups of the person, e.g. ['Admin', 'Editor'].
$person->getGroups();
```

We also have the respective setters:

```php
// Overrides whether the user is active.
$person->setActive(false);

// Adds one email address to the list.
$person->addEmailAddress(string $emailAddress): static

// Overrides the full list of email addresses.
$person->setEmailAddresses(array $emailAddresses): static

// Adds one phone number to the list.
$person->addPhoneNumber(string $phoneNumber): static

// Overrides the full list of phone numbers.
$person->setPhoneNumbers(array $phoneNumbers): static

// Overrides the region.
$person->setRegion(string $region): static

// Overrides the list of groups.
$person->setGroups(array $groups): static
```

:warning: Note that the methods in this class will *NOT* update the data in SlashID servers. To do that, you must make a request [`PATCH /persons/:person_id`](https://developer.slashid.dev/docs/api/patch-persons-person-id) or a request [`PUT /persons`](https://developer.slashid.dev/docs/api/put-persons).

#### Attributes

The person attributes in SlashID are [stored in buckets](https://developer.slashid.dev/docs/access/concepts/attribute_buckets). They are represented in this SDK by the following constants.

| Constant                                                   | Bucket name                         | Scope        | End-user access |
|------------------------------------------------------------|-------------------------------------|--------------|-----------------|
| `PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS`  | `'end_user_no_access'`              | Organization | No access       |
| `PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_ONLY`  | `'end_user_read_only'`              | Organization | Read-only       |
| `PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE` | `'end_user_read_write'`             | Organization | Read-write      |
| `PersonInterface::BUCKET_PERSON_POOL_END_USER_NO_ACCESS`   | `'person_pool-end_user_no_access'`  | Person-pool  | No access       |
| `PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_ONLY`   | `'person_pool-end_user_read_only'`  | Person-pool  | Read-only       |
| `PersonInterface::BUCKET_PERSON_POOL_END_USER_READ_WRITE`  | `'person_pool-end_user_read_write'` | Person-pool  | Read-write      |

:warning: Be careful not to expose "NO_ACCESS" attributes to the end-user.

In the `Person`, the attributes are accessible with the following methods:

```php
// Lists all attributes, grouped by bucket name.
$person->getAllAttributes();

// Response:
[
    PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE => ['first_name' => 'John'],
    PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS => ['secret_key' => 'aaa-aaa-aaa'],
];

// Gets attributes in a bucket.
$person->getBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE);

// Response:
['first_name' => 'John'];

// Gets one specific attribute.
$person->getAttribute(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE, 'first_name');

// Response:
'John';
```

The attributes can also be set:

```php
// Overrides ALL attributes.
$person->setAllAttributes([
    PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE => ['first_name' => 'John'],
    PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS => ['secret_key' => 'aaa-aaa-aaa'],
]);

// Overrides attributes in a bucket.
$person->setBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS, ['secret_key' => 'aaa-aaa-aaa']);

// Deletes the attributes in a bucket.
$person->deleteBucketAttributes(PersonInterface::BUCKET_ORGANIZATION_END_USER_NO_ACCESS);

// Overrides one attribute.
$person->setAttribute(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE, 'last_name', 'Smith');

// Deletes one attribute.
$person->deleteAttribute(PersonInterface::BUCKET_ORGANIZATION_END_USER_READ_WRITE, 'first_name');
```

:warning: Note that the methods in this class will *NOT* update the data in SlashID servers. To do that, you must do a request [`PUT /persons/:person_id/attributes`](https://developer.slashid.dev/docs/api/put-persons-person-id-attributes) or [`/persons/:person_id/attributes/:bucket_name`](https://developer.slashid.dev/docs/api/put-persons-person-id-attributes-bucket-name).

#### Groups

The `Person` class also has three useful methods to test the person's groups:

```php
if ($person->hasGroup('Editor')) {
    // Do things that only an "Editor" user can do.
}

if ($person->hasAnyGroup(['Admin', 'Editor', 'Reviewer'])) {
    // Do things that someone in the group "Admin", OR in the group "Editor", OR
    // in the group "Reviewer" can do.
}

if ($person->hasAllGroups(['Admin', 'Editor'])) {
    // Do things that only someone that is in *both* "Admin" and "Editor" groups
    // can do.
}
```

### Migration Abstraction

The migration abstraction is a class to [bulk importing users](https://developer.slashid.dev/docs/api/post-persons-bulk-import).

To import users, you must first create an array of `\SlashId\Php\PersonInterface` and then call `$sdk->migration()->migratePersons($persons);`, for instance:

```php
$person = (new \SlashId\Php\Person())
    ->setRegion('europe-england')
    ->addEmailAddress('user@example.com')
    ->addPhoneNumber('+33999999999')
    ->setGroups(['Admin', 'Editor'])
    // Define a password hash with one of the supported encryptions.
    ->setLegacyPasswordToMigrate('$2y$12$YKpfgBJpginFYyUfdAcAHumQKfJsEzJJz9d0oQgg0zoEsRSz6sXty');

$persons = [$person];

$response = $sdk->migration()->migratePersons($persons);
```

The `$response` will have the response of the endpoint [`POST /persons/bulk-import`](https://developer.slashid.dev/docs/api/post-persons-bulk-import), i.e., an array with three keys:

* `successful_imports` - the number of persons that have successfully imported
* `failed_imports` - the number of failures during the import
* `failed_csv` - a CSV that reports the users that failed to import and the error reason for each line

### Webhook Abstraction

The webhook abstraction is a class to help work with [webhooks](https://developer.slashid.dev/docs/access/guides/webhooks/introduction), for creating, listing, and deleting them, and also adding and removing triggers.

You can access it with:

```php
$sdk->webhook();
```

#### Listing webhooks

The method `findAll()` lists all existing webhook listeners in the organization, including listeners in other applications and environments:

```php
foreach ($sdk->webhook()->findAll() as $webhook) {
    // each $webhook:
    [
        'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
        'name' => 'prod_webhook',
        'description' => 'Some description...',
        'target_url' => 'https://example.com/slashid/webhook',
        'custom_headers' => [
            'X-Extra-Check' => ['Value for the header'],
        ],
        'timeout' => '30s',
    ]
}
```

The method `findById($id)` fetches one single webhook when you have its ID.

```php
$webhook = $sdk->webhook()->findById('065de68b-cce0-7285-ab00-6f34a56b585d');

// $webhook:
[
    'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
    'name' => 'prod_webhook',
    'description' => 'Some description...',
    'target_url' => 'https://example.com/slashid/webhook',
    'custom_headers' => [
        'X-Extra-Check' => ['Value for the header'],
    ],
    'timeout' => '30s',
]
```

The method `findByUrl($url)` fetches one single webhook when you have its URL.

```php
$webhook = $sdk->webhook()->findByUrl('https://example.com/slashid/webhook');

// $webhook:
[
    'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
    'name' => 'prod_webhook',
    'description' => 'Some description...',
    'target_url' => 'https://example.com/slashid/webhook',
    'custom_headers' => [
        'X-Extra-Check' => ['Value for the header'],
    ],
    'timeout' => '30s',
]
```

#### Creating webhooks and setting triggers

The `register($url, $name, $triggers, $options)` method is idempotent, i.e., it will either create a webhook if it doesn't exist yet or update it if there is already a webhook with that URL. After creating or updating the webhook, it will also register triggers for it. Thus, this method combines calls to `/organizations/webhooks` and `/organizations/webhooks/:webhook_id/triggers` endpoints.

```php
$webhook = $sdk->webhook()->register('https://example.com/slashid/webhook', 'a_unique_name_for_the_webhook', [
    'PersonCreated_v1',
    'PersonDeleted_v1',
]);

// $webhook:
[
    'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
    'name' => 'a_unique_name_for_the_webhook',
    'description' => '',
    'target_url' => 'https://example.com/slashid/webhook',
    'custom_headers' => [],
    'timeout' => '0s',
]
```

A few notes:

1. The `$triggers` argument is a list of triggers the webhook will have. For the full list see: https://developer.slashid.dev/docs/access/guides/webhooks/introduction
2. If the webhook is being updated, the `$triggers` will override existing triggers.
3. The `$options` argument is a list of additional fields the `/organizations/webhooks` endpoint receives (see https://developer.slashid.dev/docs/api/post-organizations-webhooks), e.g.:

```php
[
    'description' => 'Some description...',
    'custom_headers' => [
        'X-Extra-Check' => ['Value for the header'],
    ],
    'timeout' => '30s',
]
```

4. If the webhook is being updated, the `$options` will NOT override existing values, unlike `$triggers`.

A few examples:

```php
// Creates a new webhook.
$webhook = $sdk->webhook()->register('https://example.com/slashid/webhook', 'a_unique_name_for_the_webhook', [
    'PersonCreated_v1',
    'PersonDeleted_v1',
], [
    'timeout' => '18s',
]);

// $webhook:
[
    'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
    'name' => 'a_unique_name_for_the_webhook',
    'description' => '',
    'target_url' => 'https://example.com/slashid/webhook',
    'custom_headers' => [],
    'timeout' => '18s',
]

// Lists triggers the webhook has.
$triggers = $sdk->webhook()->getWebhookTriggers($webhook['id']);

// $triggers:
[
    'PersonCreated_v1',
    'PersonDeleted_v1',
]

// Now we update the webhook, with a different option AND different triggers.
$webhook = $sdk->webhook()->register('https://example.com/slashid/webhook', 'a_unique_name_for_the_webhook', [
    'AuthenticationSucceeded_v1',
    'PersonCreated_v1',
], [
    'custom_headers' => [
        'X-Custom-Header' => ['Value for the header'],
    ],
]);

// Note that the "custom_header" has been updated, but the value for the "timeout" is unchanged.
// $webhook:
[
    'id' => '065de68b-cce0-7285-ab00-6f34a56b585d',
    'name' => 'a_unique_name_for_the_webhook',
    'description' => '',
    'target_url' => 'https://example.com/slashid/webhook',
    'custom_headers' => [
        'X-Custom-Header' => ['Value for the header'],
    ],
    'timeout' => '18s',
]

// As for the triggers, note that "PersonDeleted_v1" is no longer a trigger.
$triggers = $sdk->webhook()->getWebhookTriggers($webhook['id']);

// $triggers:
[
    'AuthenticationSucceeded_v1',
    'PersonCreated_v1',
]
```

#### Setting triggers

You can also handle triggers directly.

The method `setTriggers($id, $triggers)` will override existing triggers, deleting triggers that are not in the `$triggers` list and adding new ones that are not.

The method `addWebhookTrigger($id, $trigger)` adds one single trigger from a webhook.

The method `deleteWebhookTrigger($id, $trigger)` removes one single trigger from a webhook.

```php
$sdk->webhook()->setWebhookTriggers('065de68b-cce0-7285-ab00-6f34a56b585d', [
    'PersonCreated_v1',
    'PersonDeleted_v1',
]);

// Triggers in the webhook: PersonCreated_v1, PersonDeleted_v1

$sdk->webhook()->addWebhookTrigger('065de68b-cce0-7285-ab00-6f34a56b585d', 'VirtualPageLoaded_v1');

// Triggers in the webhook: PersonCreated_v1, PersonDeleted_v1, VirtualPageLoaded_v1

$sdk->webhook()->deleteWebhookTrigger('065de68b-cce0-7285-ab00-6f34a56b585d', 'PersonDeleted_v1');

// Triggers in the webhook: PersonCreated_v1, VirtualPageLoaded_v1

$sdk->webhook()->setWebhookTriggers('065de68b-cce0-7285-ab00-6f34a56b585d', []);

// Triggers in the webhook: none
```

#### Deleting webhooks

You can delete a webhook with either the `deleteById($id)` or `deleteByUrl($url) methods`.

```php
$sdk->webhook()->deleteById('065de68b-cce0-7285-ab00-6f34a56b585d');
$sdk->webhook()->deleteByUrl('https://example.com/slashid/webhook');
```

#### Webhook callback

After you register a webhook and add triggers, SlashID servers will start sending requests to your endpoint. The request will be a JWT that you will have to decode and validate, using keys provided by an API endpoint using the JSON Web Signature standard.

Since the JWT Key Set (JWKS) must be downloaded remotely, it's important to cache the keys in order not to keep making remote requests all the time. So, when calling `decodeWebhookCall`, you have to a PSR-6-compatible `\Psr\Cache\CacheItemPoolInterface` object.

Some frameworks provide PSR-6 implementations by default:

* Laravel: `app('cache.psr6')`
* Symfony: package [symfony/cache](https://github.com/symfony/cache)

For other frameworks, check this list: https://packagist.org/providers/psr/cache-implementation

To implement a webhook listener, fetch the encoded JWT from the body of the remote request, then call `decodeWebhookCall` to have it validated and decoded. Here's an example of a Laravel implementation that dispatches an Event after receiving the webhook.

```php
// The JWT from the request.
$encodedJwt = $request->getContent();

// $encodedJwt:
// eyJhbGciOiJFUzI1NiIsICJraWQiOiJuTGtxV1EifQ.eyJhdWQiOiI0MTJlZGI1Ny1hZTI2LWYyYWEtMDY5OC03NzAwMjFlZDUyZDEiLCAiZXhwIjx...

// Note the use of `app('cache.psr6')` to fetch the cache backend.
$decoded = $sdk->webhook()->decodeWebhookCall($encodedJwt, app('cache.psr6'));

// Dispatch an event using Laravel's API.
WebhookEvent::dispatch(
    $decoded['trigger_content']['event_metadata']['event_name'],
    $decoded['trigger_content']['event_metadata']['event_id'],
    $decoded['trigger_content'],
);
```

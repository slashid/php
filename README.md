# SlashID SDK

## Installation

Install the this library with composer:

```
composer require slashid/slashid-php
```

## Usage

### Instantiating the SDK

First, create a SDK instance, informing the following:

* `$environment`, either `sandbox` or `production`
* `$organizationId`, your organization's ID. You'll find it your SlashID console (https://console.slashid.dev/ for production, https://console.sandbox.slashid.dev/ for sandbox), in the "Settings" tab, on the top of the page.
* `$apiKey`, your organization's ID. You'll also find it your SlashID console, in the "Settings" tab, on the very bottom of the page.

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

* `\SlashId\Php\Exception\BadRequestException` when the API returns a **400** error, meaning that the data you've sent to the request is malformed or missing required information.
* `\SlashId\Php\Exception\BadRequestException` when the API returns a **401** error, meaning that either the Organization ID, API key or environment are wrong. In this case, check your credentials.
* `\SlashId\Php\Exception\AccessDeniedException` when the API returns a **403** error, meaning you are not allowed to perform an operation.
* `\SlashId\Php\Exception\InvalidEndpointException` when the API returns a **404** error due to you requesting an endpoint that does not exist. In this case, check the [API reference](https://developer.slashid.dev/docs/api).
* `\SlashId\Php\Exception\BadRequestException` when the API returns a **404** error on a valid endpoint, meaning the ID you've requested does not exist. This exception will happen on requests that include an ID in the URL, such as `/persons/1111-1111-1111`.
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

### Webhook Abstraction

The webhook abstraction is a class to help working with webhooks, for creating, listing and deleting them, and also adding and removing triggers.

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
$webhook = $sdk->webhook()->findById('https://example.com/slashid/webhook');

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

The `register($url, $name, $triggers, $options)` method is idempotent, i.e., it will either create a webhook if it doesn't exist yet, or update it if there is already a webhook with that URL. After creating or updating the webhook, it will also register triggers for it. Thus, this method combines calls to `/organizations/webhooks` and `/organizations/webhooks/:webhook_id/triggers` endpoints.

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

The method `addWebhookTrigger($id, $trigger)` add one single trigger from a webhook.

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

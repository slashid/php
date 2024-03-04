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

// response:
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

// response:
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

// response:
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

// response:
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

### Abstractions

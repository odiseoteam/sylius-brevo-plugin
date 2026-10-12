# Testing your integration

Tests should never reach Brevo. The plugin ships a fake HTTP transport, the one its own test suites
use: replace the real one in the test environment, queue its answers and inspect what it got.

```php
// config/services_test.php
$services->set('odiseo_brevo.client.http.transport', \Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient::class)
    ->args(['%kernel.cache_dir%/brevo_fake_client.data'])
    ->public();
```

It keeps its state in that file, so a Behat context and the kernel that serves the browser share the
same requests and answers.

```php
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;

$brevo = static::getContainer()->get('odiseo_brevo.client.http.transport');
$brevo->reset();
$brevo->queue('POST', '/contacts', new BrevoResponse(201, ['id' => 42]));

// ... act ...

$request = $brevo->lastRequest();     // apiKey, method, path, query, json
$events = $brevo->requests('POST', '/events');
```

| Method | Does |
| --- | --- |
| `queue($method, $path, $response)` | Answers the next matching request (a `BrevoResponse` or a `BrevoException`) |
| `respondAlways($method, $path, $response)` | Answers every matching request |
| `failAll($method, $response)` | Answers every request of that method, e.g. with a 500 |
| `requests($method, $path)` / `lastRequest()` | What the plugin sent |
| `reset()` | Forgets requests and answers |

Without queued answers it replies with an empty success, so the shop works as if Brevo accepted
everything. Queue a failure to test what happens when Brevo is down: the request still succeeds, and
the message is retried or lands in `odiseo_brevo_failed`.

Messages created during a request are sent on `kernel.terminate`. In tests that don't terminate the
kernel, flush them yourself through the `odiseo_brevo.messenger.dispatcher` service before asserting.

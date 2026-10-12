# Background processing

A Brevo failure never breaks a shop request or a checkout. Every Brevo call runs through Symfony
Messenger on its own bus (`odiseo_brevo.bus`) and transport (`odiseo_brevo`). Messages created during
a request are sent after the response, so Brevo never slows the shop down.

Changes are collected while Doctrine flushes and dispatched after it, once per request and subject.

## Transports

By default the transport is `sync://`: calls run in the same PHP process, right after the response.
For production, use an async transport and a worker:

```dotenv
ODISEO_BREVO_MESSENGER_TRANSPORT_DSN=doctrine://default?queue_name=odiseo_brevo
# Optional, defaults to doctrine://default?queue_name=odiseo_brevo_failed
ODISEO_BREVO_MESSENGER_FAILED_TRANSPORT_DSN=doctrine://default?queue_name=odiseo_brevo_failed
```

```bash
bin/console messenger:consume odiseo_brevo
```

Run the worker under a process manager (Supervisor, systemd) like any other Messenger worker.

## Retries

- **Rate limits** (429) wait what Brevo asks.
- **Server and network errors** are retried with backoff, 3 times.
- **Rejected requests** (invalid payload or key) go straight to `odiseo_brevo_failed`.

```bash
bin/console messenger:failed:show --transport=odiseo_brevo_failed
bin/console messenger:failed:retry --transport=odiseo_brevo_failed
```

In-process retries of the HTTP client itself are set with `api.max_retries` (2 by default).

## Logging

Brevo requests are logged to the `brevo` Monolog channel. API keys and payloads are never logged and
emails are masked.

```yaml
# config/packages/monolog.yaml
monolog:
    handlers:
        brevo:
            type: rotating_file
            path: '%kernel.logs_dir%/brevo.log'
            channels: [brevo]
```

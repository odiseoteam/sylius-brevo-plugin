# Sylius Brevo Plugin

Brevo integration for Sylius 2.x.

> Work in progress. See [ROADMAP.md](ROADMAP.md) for scope and status.

## Description

Connects each Sylius channel to a Brevo account and keeps both in sync:

- **Contacts and newsletter**: customers, addresses and subscriptions as Brevo contacts and lists.
- **Ecommerce**: catalog (products, categories) and orders synced to Brevo Ecommerce.
- **Tracking and events**: shop behaviour (cart, checkout, product views) for Brevo automations.

Each feature is a module enabled per channel. **Odiseo Brevo Pro** builds on this plugin with Sylius
emails designed in the Brevo editor, cart recovery, coupons, SMS, WhatsApp, loyalty and more.

Brevo is always called in the background: a Brevo failure never breaks a shop request or a checkout.

## Requirements

| Package | Version |
| --- | --- |
| PHP | ^8.2 |
| Sylius | ^2.0 |
| Symfony | ^6.4 \|\| ^7.4 |

## Installation

1. Require the package:

    ```bash
    composer require odiseoteam/sylius-brevo-plugin
    ```

2. Register the bundle in `config/bundles.php`:

    ```php
    Odiseo\SyliusBrevoPlugin\OdiseoSyliusBrevoPlugin::class => ['all' => true],
    ```

3. Import the configuration in `config/packages/odiseo_sylius_brevo.yaml`:

    ```yaml
    imports:
        - { resource: "@OdiseoSyliusBrevoPlugin/config/config.yaml" }
    ```

4. Import the admin routes in `config/routes/odiseo_sylius_brevo.yaml`:

    ```yaml
    odiseo_sylius_brevo_admin:
        resource: "@OdiseoSyliusBrevoPlugin/config/routes/admin.yaml"
        prefix: '/%sylius_admin.path_name%'
    ```

5. Run the migrations:

    ```bash
    bin/console doctrine:migrations:migrate
    ```

6. Make sure the Sylius encryption key exists. API keys are encrypted with it, like payment
   gateway credentials. Sylius creates it on install; otherwise:

    ```bash
    bin/console sylius:payment:generate-key
    ```

7. Set the host used for URLs generated outside a request (workers, CLI) in
   `config/packages/routing.yaml`:

    ```yaml
    framework:
        router:
            default_uri: '%env(DEFAULT_URI)%'
    ```

Then go to **Brevo > Configuration** in the admin, add a configuration for each channel with its
Brevo API key (Brevo > SMTP & API > API keys) and use **Test connection**.

## Configuration

All options are optional:

```yaml
odiseo_sylius_brevo:
    api:
        key: ~               # fallback API key for channels without their own, e.g. '%env(BREVO_API_KEY)%'
        base_url: 'https://api.brevo.com/v3'
        timeout: 10          # seconds
        max_retries: 2       # in-process retries for transient failures
    phone:
        default_region: ~    # e.g. AR; fallback country for phone numbers
    contacts:
        attributes: {}       # contact data key => Brevo attribute name, or false to skip it
    url:
        image_filter: 'sylius_shop_product_large_thumbnail'   # Liip Imagine filter for images sent to Brevo
```

### Channels

Each channel has its own configuration in the admin: API key, default sender and enabled
modules. A disabled configuration, or one without an API key (own or fallback), turns Brevo off
for that channel.

### Contacts

With the **Contacts** module on (Brevo > Configuration), customers become Brevo contacts. They're
sent after the response whenever the customer, its default address or one of its orders changes
(billing address, checkout, payment or cancellation), from the shop, the admin, the API or the CLI.
Guests are contacts from the checkout addressing step on.

- The contact's `ext_id` is the customer id, so an email change updates the same contact. Contacts
  that already existed in Brevo are linked by email.
- Customers without an account (guest checkouts) are synced too, unless the channel turns it off.
- Deleting a customer deletes its contact only when the channel says so.
- A customer is sent once per Brevo account: channels sharing an API key share contacts.
- Names, phone and address come from the customer, then its default address, then the billing
  address of its latest order (guests have no other).
- Unknown values are not sent, so Brevo keeps what it had.
- With a **customers list** chosen in the channel configuration, every synced contact joins it.
- With a **newsletter list**, subscribers join it too (see [Newsletter](#newsletter)).

Attributes sent:

| Key | Brevo attribute | Type |
| --- | --- | --- |
| `first_name`, `last_name` | `FIRSTNAME`, `LASTNAME` | text |
| `phone` | `SMS` (E.164) | text |
| `gender`, `customer_group`, `channel`, `locale` | same key uppercase | text |
| `birthday` | `BIRTHDAY` | date |
| `city`, `province`, `country`, `postcode` | `CITY`, `PROVINCE`, `COUNTRY`, `ZIP_CODE` | text |
| `orders_count`, `total_spent`, `average_order_value` | same key uppercase | number |
| `first_order_date`, `last_order_date` | same key uppercase | date |

Only the attributes the account has are sent (the rest are logged as missing). Create them
once per account:

```bash
bin/console odiseo:brevo:attributes:setup --dry-run
bin/console odiseo:brevo:attributes:setup
```

Import the customers you already have (once after installing, or to catch up):

```bash
bin/console odiseo:brevo:contacts:sync --dry-run
bin/console odiseo:brevo:contacts:sync [--channel=WEB] [--since=2026-01-01] [--only-subscribed]
```

It uses Brevo's bulk import into the channel's customers list, plus the newsletter list for
subscribers (or everyone into `--list=ID`), in batches of `--batch-size` (1000), and waits for Brevo to
process them unless `--no-wait` is given. In an import every contact takes the account's first
channel as `CHANNEL`; later changes set the right one.

Rename or skip attributes:

```yaml
odiseo_sylius_brevo:
    contacts:
        attributes:
            first_name: NOMBRE
            birthday: false
```

Add your own with a service implementing `ContactAttributeProviderInterface`, tagged
`odiseo_brevo.contact_attribute_provider`.

To apply customer changes that come from Brevo without syncing them back, run them (flush included)
inside `ContactSyncPauseInterface::pause()`.

### Newsletter

Turn on the **Newsletter** module (it needs Contacts) and choose a **newsletter list** in the channel
configuration. Then:

- Customers subscribed to the newsletter (registration, profile, admin) join the list; unsubscribing
  in Sylius removes them from it. Contacts added to the list from Brevo are never removed.
- Subscribers are synced even when the channel doesn't sync guests.
- Turning the module off stops the list sync, the form and the double opt-in; the chosen list is kept.
  It can't be turned on without Contacts.
- Every shop page (the checkout has no footer) ends with a subscription section right before the
  footer. Visitors without an account become customers without one, subscribed to the newsletter.
  It's a Live Component: it subscribes without leaving the page and shows the result in place
  (without JavaScript the form posts and comes back to the section). A hidden field keeps bots out,
  there's no CSRF token so pages stay cacheable, and customers already subscribed don't see it.
- Its texts are translation keys (`odiseo_brevo.ui.newsletter.title`, `subtitle`, `email`,
  `subscribe`, `consent`); the template is `@OdiseoSyliusBrevoPlugin/shop/newsletter/form.html.twig`
  (override it in `templates/bundles/OdiseoSyliusBrevoPlugin/`). To remove the section, or move it
  (e.g. to the homepage only), disable its hook and render the `odiseo_brevo:shop:newsletter_form`
  component where you want:

    ```yaml
    sylius_twig_hooks:
        hooks:
            'sylius_shop.base.footer':
                odiseo_brevo_newsletter:
                    enabled: false
    ```

- Headless shops subscribe through the API (always `202`, without an email it takes the logged-in
  customer's):

    ```http
    POST /api/v2/shop/newsletter-subscriptions
    Content-Type: application/ld+json

    {"email": "jane@example.com"}
    ```

**Double opt-in**: with a Brevo template ID in the channel configuration (a double opt-in template with
the `{{ doubleoptin }}` link), subscriptions from the form or the API wait for the confirmation: Brevo
emails the link and, once followed, the visitor lands on a signed shop URL (valid 30 days) that
subscribes them. The logged-in customer's own email is subscribed right away.

Unsubscriptions made in Brevo (email footer links) don't reach Sylius.

### Phone numbers

Brevo requires phone numbers in E.164 (`+5491122334455`). Numbers without an international prefix
take their country from the address, then from the channel when it has a single country, then from
`phone.default_region`. Numbers that can't be resolved are not sent.

### URLs

Links and images sent to Brevo use the channel hostname over `https`. When the hostname matches the
`default_uri` host (e.g. `http://localhost:8090` locally), its scheme and port are kept.

### Background processing

Every Brevo call runs through Symfony Messenger on its own bus (`odiseo_brevo.bus`) and transport
(`odiseo_brevo`). Messages created during a request are sent after the response, so Brevo never
slows down or breaks the shop.

By default the transport is `sync://`: calls run in the same PHP process, right after the
response. For production, use an async transport and a worker:

```dotenv
ODISEO_BREVO_MESSENGER_TRANSPORT_DSN=doctrine://default?queue_name=odiseo_brevo
# Optional, defaults to doctrine://default?queue_name=odiseo_brevo_failed
ODISEO_BREVO_MESSENGER_FAILED_TRANSPORT_DSN=doctrine://default?queue_name=odiseo_brevo_failed
```

```bash
bin/console messenger:consume odiseo_brevo
```

Rate limits (429) wait what Brevo asks, server and network errors are retried with backoff
(3 times), and rejected requests (invalid payload or key) go straight to `odiseo_brevo_failed`:

```bash
bin/console messenger:failed:show --transport=odiseo_brevo_failed
bin/console messenger:failed:retry --transport=odiseo_brevo_failed
```

### Logging

Brevo requests are logged to the `brevo` Monolog channel. API keys and payloads are never logged
and emails are masked.

## Testing your integration

Tests should never reach Brevo. In the test environment, replace the HTTP transport with the fake one,
queue its answers and inspect what it got:

```php
// config/services_test.php
$services->set('odiseo_brevo.client.http.transport', \Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient::class)
    ->args(['%kernel.cache_dir%/brevo_fake_client.data'])
    ->public();
```

## Development

Everything runs in Docker:

```bash
make init           # install dependencies and start the stack
make phpunit        # PHPUnit
make behat          # Behat
make phpstan ecs    # static analysis and coding standard
```

## License

MIT. See [LICENSE](LICENSE).

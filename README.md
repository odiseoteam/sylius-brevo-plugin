# Sylius Brevo Plugin

Brevo integration for Sylius 2.x.

> Work in progress. See [ROADMAP.md](ROADMAP.md) for scope and status.

## Description

Connects each Sylius channel to a Brevo account and keeps both in sync:

- **Contacts and newsletter**: customers, addresses and subscriptions as Brevo contacts and lists.
- **Transactional emails**: Sylius emails sent through Brevo templates designed in the Brevo editor,
  with the Twig templates as fallback.
- **Ecommerce**: catalog (products, categories) and orders synced to Brevo Ecommerce.
- **Tracking and events**: shop behaviour (cart, checkout, product views) for Brevo automations.
- **Coupons, SMS, WhatsApp, Loyalty, CRM** and other Brevo modules, each one enabled per channel.

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

## Development

Everything runs in Docker:

```bash
make init           # install dependencies and start the stack
make phpunit        # PHPUnit
make behat          # Behat
make phpstan ecs    # static analysis and coding standard
```

## License

Proprietary. See [LICENSE](LICENSE).

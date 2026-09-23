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
    url:
        image_filter: 'sylius_shop_product_large_thumbnail'   # Liip Imagine filter for images sent to Brevo
```

### Channels

Each channel has its own configuration in the admin: API key, default sender and enabled
modules. A disabled configuration, or one without an API key (own or fallback), turns Brevo off
for that channel.

### Phone numbers

Brevo requires phone numbers in E.164 (`+5491122334455`). Numbers without an international prefix
take their country from the address, then from the channel when it has a single country, then from
`phone.default_region`. Numbers that can't be resolved are not sent.

### URLs

Links and images sent to Brevo use the channel hostname over `https`. When the hostname matches the
`default_uri` host (e.g. `http://localhost:8090` locally), its scheme and port are kept.

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

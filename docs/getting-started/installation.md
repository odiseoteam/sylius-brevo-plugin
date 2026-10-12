# Installation

## 1. Require the plugin

```bash
composer require odiseoteam/sylius-brevo-plugin
```

With Symfony Flex and contrib recipes allowed (`extra.symfony.allow-contrib`, on in Sylius-Standard),
the recipe registers the bundle, imports its configuration and routes and adds
`ODISEO_BREVO_MESSENGER_TRANSPORT_DSN` to your `.env`. **Skip to step 5.**

## 2. Register the bundle

```php
// config/bundles.php
return [
    // ...
    Odiseo\SyliusBrevoPlugin\OdiseoSyliusBrevoPlugin::class => ['all' => true],
];
```

## 3. Import the configuration

```yaml
# config/packages/odiseo_sylius_brevo.yaml
imports:
    - { resource: "@OdiseoSyliusBrevoPlugin/config/config.yaml" }
```

## 4. Import the routes

```yaml
# config/routes/odiseo_sylius_brevo.yaml
odiseo_sylius_brevo_admin:
    resource: "@OdiseoSyliusBrevoPlugin/config/routes/admin.yaml"
    prefix: '/%sylius_admin.path_name%'

odiseo_sylius_brevo_shop:
    resource: "@OdiseoSyliusBrevoPlugin/config/routes/shop.yaml"
    prefix: '/{_locale}'
    requirements:
        _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$
```

## 5. Run the migrations

```bash
bin/console doctrine:migrations:migrate
```

Migrations ship for MySQL and PostgreSQL.

## 6. Check the encryption key

API keys are stored encrypted with the Sylius encryption key, like payment gateway credentials, and
only decrypted to call Brevo. Sylius creates the key on install; if yours has none:

```bash
bin/console sylius:payment:generate-key
```

## 7. Set the default URI

Links and images sent from workers and commands are built without a request. Give the router the
host to use:

```yaml
# config/packages/routing.yaml
framework:
    router:
        default_uri: '%env(DEFAULT_URI)%'
```

## 8. Run a worker in production

By default every Brevo call runs right after the response, in the same PHP process. In production use
an async transport and a worker:

```dotenv
ODISEO_BREVO_MESSENGER_TRANSPORT_DSN=doctrine://default?queue_name=odiseo_brevo
```

```bash
bin/console messenger:consume odiseo_brevo
```

See [Background processing](/operations/background-processing) for retries and the failed queue.

::: tip Check it
`bin/console odiseo:brevo:debug` prints what each channel ends up with and tests its connection.
:::

Next: [Your first channel](./first-channel).

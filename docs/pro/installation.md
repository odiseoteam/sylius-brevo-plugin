# Installing Pro <span class="pro-badge">Pro</span>

<ProNotice />

## 0. Install the free plugin

Pro runs on top of it: follow [Installation](/getting-started/installation) first.

## 1. Add Odiseo's private repository

Pro is served from Odiseo's own Composer repository, with the credentials of your license:

```bash
composer config repositories.odiseo composer https://distribution.odiseo.io
composer config http-basic.distribution.odiseo.io <your-username> <your-password>
```

The first command adds a `repositories` entry to `composer.json` (no secret in it). The second writes
the credentials to `auth.json`: **add `/auth.json` to your `.gitignore`** and never commit it.

## 2. Require the package

```bash
composer require odiseoteam/sylius-brevo-pro-plugin
```

## 3. Check the bundle

Symfony Flex registers it. Check it comes after the Brevo plugin:

```php
// config/bundles.php
return [
    // ...
    Odiseo\SyliusBrevoPlugin\OdiseoSyliusBrevoPlugin::class => ['all' => true],
    Odiseo\SyliusBrevoProPlugin\OdiseoSyliusBrevoProPlugin::class => ['all' => true],
];
```

The package has no Flex recipe, so the next steps are manual.

## 4. Import the configuration

```yaml
# config/packages/odiseo_sylius_brevo_pro.yaml
imports:
    - { resource: "@OdiseoSyliusBrevoProPlugin/config/config.yaml" }
```

## 5. Import the routes

```yaml
# config/routes/odiseo_sylius_brevo_pro.yaml
odiseo_sylius_brevo_pro_admin:
    resource: "@OdiseoSyliusBrevoProPlugin/config/routes/admin.yaml"
    prefix: '/%sylius_admin.path_name%'

odiseo_sylius_brevo_pro_shop:
    resource: "@OdiseoSyliusBrevoProPlugin/config/routes/shop.yaml"
    prefix: '/{_locale}'
    requirements:
        _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$

odiseo_sylius_brevo_pro_webhook:
    resource: "@OdiseoSyliusBrevoProPlugin/config/routes/webhook.yaml"
```

## 6. Run the migrations

```bash
bin/console doctrine:migrations:migrate
```

Pro modules and tabs show up in **Brevo > Configuration**, next to the free ones, and the admin menu
gets **Brevo > Emails** and **Brevo > Sent emails**.

## Next

- [Transactional emails](./transactional-emails): design the Sylius emails in Brevo.
- [Webhooks](./webhooks): register them once per channel.
- [Past orders](./past-orders): import the order history.

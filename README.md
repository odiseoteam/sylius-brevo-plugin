# Sylius Brevo Plugin

Brevo integration for Sylius 2.x.

> Work in progress.

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
| PHP | 8.2 · 8.3 · 8.4 (Sylius 2.3 needs 8.3) |
| Sylius | 2.0 · 2.1 · 2.2 · 2.3 |
| Symfony | 6.4 · 7.4 |
| Database | MySQL · PostgreSQL |

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

6. Make sure the Sylius encryption key exists. API keys are stored encrypted with it, like payment
   gateway credentials, and only decrypted to call Brevo. Sylius creates it on install; otherwise:

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
    tracking:
        consent_cookie: { name: ~, value: ~ }   # cookie set by the consent banner; empty: always allowed
        events: {}           # event code => { enabled: false } or { name: other-name }
    orders:
        statuses: {}         # pending, paid, shipped, fulfilled, cancelled, refunded => Brevo status, e.g. { fulfilled: completed }
    url:
        image_filter: 'odiseo_brevo_product'   # Liip Imagine filter for images sent to Brevo (600px JPEG)
```

### Channels

Each channel has its own configuration in the admin: API key, default sender and enabled
modules. A disabled configuration, or one without an API key (own or fallback), turns Brevo off
for that channel.

The form is split in tabs (General, Contacts, Newsletter); a module's tab shows once it is saved on.
Plugins add a tab with the `side_navigation/tab.html.twig` and `sections/tab.html.twig` templates
(`tab`, `label` and optional `module` in the hookable configuration) on the
`odiseo_brevo.admin.channel_configuration.{create,update}.content.form.side_navigation` and `.sections`
hooks, and cards in `.sections.<tab>`.

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

To map per channel, decorate `odiseo_brevo.contact.attribute_mapping` (`AttributeMappingInterface`).

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

### Catalog

With the **Catalog** module on, the channel's taxons become Brevo Ecommerce categories (id = taxon
code, name with its path such as `T-shirts > Men`, and URL in the channel's default locale). Only the taxons under the channel's menu taxon
are sent (every non-root taxon without one); a disabled taxon, one moved out, or a deleted one is sent
as deleted, since Brevo can't delete categories.

Saving the configuration with the module on activates Brevo Ecommerce on the account and shows amounts
in the account's currency (see [Currencies](#currencies)). The first activation takes Brevo a few minutes; then send the
existing taxons:

```bash
bin/console odiseo:brevo:ecommerce:activate --channel=WEB  # same as saving, from the CLI
bin/console odiseo:brevo:categories:sync --dry-run
bin/console odiseo:brevo:categories:sync
```

Later changes are sent as taxons are created, edited, moved or deleted. Channels sharing a Brevo
account share its catalog and its currency. Decorate
`odiseo_brevo.catalog.category_payload_builder` to change what is sent.

Product variants become Brevo products, grouped under their product (`parentId`, left out when the
only variant shares the product code):

| Field | Value |
| --- | --- |
| `id`, `sku` | Variant code |
| `name` | Product name in the channel's default locale |
| `url` | Product page |
| `imageUrl` | Variant image, else the product's (a `main` one first), through the `url.image_filter` Liip filter: 600px JPEG by default, since Brevo drops big images and email clients don't read WebP |
| `price`, `alternativePrice` | Channel price, and the original price when it's higher |
| `stock` | On hand minus on hold, for tracked variants |
| `categories` | Product taxons in the channel, with their parents |
| `description` | Short description, else the description, as plain text |
| `metaInfo` | Option values (`size: M`) and the variant name when the product has several |

A disabled variant or product, or one the channel doesn't sell (not in the channel or without a
price), is sent as deleted. Changes to products, variants, prices, stock, images or taxons are sent
after saving; a variant is only sent again when its payload changed. Send the existing ones with:

```bash
bin/console odiseo:brevo:products:sync --dry-run
bin/console odiseo:brevo:products:sync              # --after-id=123 resumes, --force resends unchanged ones
```

Add fields (brand, attributes...) with a service implementing `ProductPayloadProviderInterface`,
tagged `odiseo_brevo.product_payload_provider`; `metaInfo` is merged by key.

### Orders

With the **Orders** module on, completed orders (never carts) are sent to Brevo Ecommerce when their
checkout, order, payment or shipping state changes, from the shop, the admin, the API or the CLI.
Brevo links them to the contact by email and `ext_id`, creating it (unsubscribed) when missing, and
fills the ecommerce dashboard with them. Saving the configuration with the module on activates Brevo
Ecommerce, as with the catalog.

| Field | Value |
| --- | --- |
| `id` | Order number |
| `status` | `cancelled`, `refunded`, `fulfilled`, `shipped`, `paid` or `pending`, the first that applies |
| `amount` | Order total |
| `products` | Variant code (the Brevo product id), quantity and unit price after discounts |
| `billing` | Billing address, phone in E.164 and payment method |
| `coupons` | Promotion coupon |
| `metaInfo` | Currency, items, shipping, tax and discount totals, shipping method |

Brevo takes any status: choose the ones counted as revenue in Brevo > Settings > E-commerce > Order
statuses, or rename them with `orders.statuses`. Decorate `OrderStatusMapperInterface`
(`odiseo_brevo.order.status_mapper`) for other rules, and add fields with a service implementing
`OrderPayloadProviderInterface`, tagged `odiseo_brevo.order_payload_provider` (`metaInfo` and
`billing` are merged by key).

### Currencies

Brevo shows amounts in a single currency per account: the base currency of the first configured
channel of that account (catalog or orders module). Prices and order amounts of channels sharing the
account in another currency are converted with the Sylius exchange rates; the original order amount
stays in `metaInfo`. Without an exchange rate they're sent unconverted and a warning is logged, and the
configuration page lists the missing rates. A product sold in several channels is read from one in the
account's currency when possible.

### Tracking

With the **Tracking** module on, the Brevo tracker loads on every shop page with the channel's client
key. Saving the configuration fills the key from the Brevo account (Automation > Settings); type
another one in the Tracking tab, or leave it blank to fetch it again.

- **Page views**: one per page, with the page title, its path and its canonical URL
  (`<link rel="canonical">`, else the URL without query string) keeping the `utm_*` parameters. The
  tracker's own page view is turned off.
- **Identify**: the visitor is identified by email (and `ext_id`, the customer id) on the first page
  after signing in, registering or giving an email in the checkout, so guests are identified too.

Without consent nothing is loaded. By default tracking is allowed; to wait for the site's consent
banner, set the cookie it writes when the visitor accepts:

```yaml
odiseo_sylius_brevo:
    tracking:
        consent_cookie:
            name: cookie_consent
            value: ~          # any value; or the one meaning "accepted"
```

Until then the tracker waits for a `brevo:consent` event, so the banner can start it without a reload:

```js
document.dispatchEvent(new Event('brevo:consent'));
```

For other rules, decorate `TrackingConsentCheckerInterface` (`odiseo_brevo.tracking.consent_checker`).
With a Content Security Policy, allow scripts from `cdn.brevo.com` and `sibautomation.com` and
connections to `in-automate.brevo.com`.

#### Ecommerce events

The module also sends events to use as automation triggers (browse and cart abandonment, post-purchase):

| Event | Sent | When | Properties |
| --- | --- | --- | --- |
| `product_viewed` | browser | a product page is shown | `product_id`, `name`, `price`, `currency`, `url`, `image` |
| `category_viewed` | browser | a taxon's listing is shown | `category_id`, `name`, `url` |
| `cart_updated` | server | the cart gets items, changes, or gets the customer's email | `cart_id`, `total`, `currency`, `url` (the cart), `items` |
| `cart_deleted` | server | the last item leaves the cart | same as `cart_updated` |
| `order_completed` | server | the checkout is completed | `order_id`, `total`, `items_total`, `shipping_total`, `tax_total`, `discount_total`, `currency`, `coupon`, `items` |
| `order_paid` | server | the order gets fully paid | same as `order_completed` |
| `customer_registered` | server | a customer registers in the shop | `first_name`, `last_name`, `subscribed_to_newsletter` |

`items` lists `product_id` (variant code), `name`, `variant_name`, `quantity`, `price` (unit price after
discounts), `url` and `image`. Browser events are pushed by the tracker, so they wait for consent like the
rest; server events go through the Events API and identify the contact by email and `ext_id`. Cart events
start once the cart has an email (signed-in customer or the checkout's address step) and are sent once per
request with the cart's final state. Amounts are in the order's currency (the channel's for products).

With the catalog module on, the tracker also marks the product (its default variant) or the category as
viewed in Brevo Ecommerce (`viewProduct`, `viewCategory`), linked to the synced catalog.

Turn an event off or rename it in Brevo (letters, digits, `-` and `_`):

```yaml
odiseo_sylius_brevo:
    tracking:
        events:
            category_viewed: { enabled: false }
            cart_updated: { name: cart-updated }
```

Add properties with a service implementing `EventPropertiesProviderInterface`, tagged
`odiseo_brevo.event_payload_provider`, and new events with a `TrackingEventInterface` tagged
`odiseo_brevo.tracking_event` (one with the same code replaces the plugin's). To decide per channel,
decorate `TrackingEventSettingsInterface` (`odiseo_brevo.tracking.event_settings`). Send your own
event of a placed order or of a customer by dispatching `TrackOrderEvent` or `TrackCustomerEvent` with
its code through `BrevoMessageDispatcherInterface`; for any other subject, call
`TrackingEventSenderInterface` from your own message handler.

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

### Diagnostics

See what each channel ends up with (configuration, modules, queue transport) and check its connection:

```bash
bin/console odiseo:brevo:debug [--channel=WEB] [--no-connection]
```

It fails when Brevo rejects a channel's API key or can't be reached, so it works as a health check.
API keys are shown masked. Add rows with a service implementing `DebugInfoProviderInterface`, tagged
`odiseo_brevo.debug_info_provider`.

Run every sync of the modules that are on, in order (categories, products, contacts), for an initial
load or a catch-up:

```bash
bin/console odiseo:brevo:sync --dry-run
bin/console odiseo:brevo:sync [--channel=WEB] [--step=products --step=contacts]
```

Each step is one of the commands above, run with `--channel` and `--dry-run` when it takes them; a
failed step doesn't stop the next ones and the command fails at the end. Add a step with a `SyncStep`
service (name, module, command) tagged `odiseo_brevo.sync_step`; the higher priority runs first
(categories 300, products 200, contacts 100).

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

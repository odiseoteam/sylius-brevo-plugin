# Your first channel

Each Sylius channel has its own Brevo configuration: its API key, its default sender and the modules
it turns on. A channel without a configuration, with a disabled one or without an API key sends
nothing to Brevo.

![Brevo configurations, one per channel](/images/configurations.png){.screenshot}

## Add the configuration

Go to **Brevo > Configuration** in the admin and create one for the channel:

1. Paste the **API key** of the Brevo account (Brevo > SMTP & API > API keys). It's stored encrypted
   and never shown again: leave the field blank to keep it.
2. Set the **default sender**, for the emails Brevo sends on behalf of the channel ([Pro transactional emails](/pro/transactional-emails)). Optional otherwise.
3. Turn on the **modules** you need.
4. Save, then click **Test connection**.

![The General tab of a channel configuration](/images/configuration.png){.screenshot}

A module's tab (Contacts, Newsletter, Tracking…) shows up once the module is saved on.

| Module | What it does | Needs |
| --- | --- | --- |
| [Contacts](/features/contacts) | Customers and guests as Brevo contacts | |
| [Newsletter](/features/newsletter) | A Brevo list, a shop form and an API for subscriptions | Contacts |
| [Catalog](/features/catalog) | Taxons and product variants in Brevo Ecommerce | |
| [Orders](/features/orders) | Completed orders in Brevo Ecommerce | |
| [Tracking](/features/tracking) | The Brevo tracker and the shop [events](/features/events) | |
| [Transactional emails](/pro/transactional-emails) <span class="pro-badge">Pro</span> | Sylius emails with Brevo templates | |

A fallback API key for every channel without its own can come from the configuration:

```yaml
odiseo_sylius_brevo:
    api:
        key: '%env(BREVO_API_KEY)%'
```

## Load what you already have

Changes are sent as they happen. To send what the store had before installing, run every sync of the
modules that are on, in order (categories, products, contacts):

```bash
bin/console odiseo:brevo:sync --dry-run
bin/console odiseo:brevo:sync
```

With Pro and the Orders module on, the [past orders](/pro/past-orders) go last.

## Several channels, one account

Channels can share a Brevo account (the same API key). They then share its contacts, its catalog and
its currency: a customer is sent once per account, and amounts are shown in the base currency of the
first channel configured with the catalog or orders module. See [Currencies](/operations/currencies).

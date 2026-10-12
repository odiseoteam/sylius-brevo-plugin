# Requirements

| | Versions |
| --- | --- |
| PHP | 8.2 · 8.3 · 8.4 (Sylius 2.3 needs 8.3) |
| Sylius | 2.0 · 2.1 · 2.2 · 2.3 |
| Symfony | 6.4 · 7.4 |
| Database | MySQL · PostgreSQL |

Every combination runs in CI, plus a job that installs the plugin on a clean Sylius-Standard with its
Flex recipe.

## On the Brevo side

- A Brevo account per channel, or one shared by several channels. Each one needs an **API key**
  (Brevo > SMTP & API > API keys).
- **Brevo Ecommerce** for the catalog and orders. The plugin activates it on the account the first
  time you save a channel with one of those modules on.
- **Automations** for the tracker and the events: the tracker client key is read from the account.

## In your application

- The Sylius **encryption key**, used to store API keys encrypted (Sylius creates it on install).
- A `default_uri` for the router, so links built in workers and commands point to your shop.
- In production, a **Messenger worker** for the `odiseo_brevo` transport. Without one, calls run right
  after each response in the same PHP process.

Next: [Installation](./installation).

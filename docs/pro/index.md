# Brevo Pro <span class="pro-badge">Pro</span>

The free plugin takes the store's data to Brevo, one way, set up by a developer. It's enough to run
campaigns.

**Brevo Pro** is the paid add-on that builds on it: it automates, goes both ways, takes care of
checkout emails and money, and gives the marketing team screens of their own. It concentrates the
features where a bug costs sales, tested and kept up to date across Sylius and Brevo API versions.

<div class="pro-notice">
  <span class="pro-badge">Pro</span>
  <p>One price, perpetual license, every Pro module included, with optional yearly renewal for
  support and updates. <a href="https://odiseo.io/en/products/sylius-brevo-plugin?utm_source=docs&utm_medium=pro_overview&utm_campaign=sylius-brevo-plugin" target="_blank" rel="noopener">See pricing →</a></p>
</div>

## Free and Pro

| | Free | Pro |
| --- | :---: | :---: |
| Channel configuration in the admin, encrypted API keys | ✓ | ✓ |
| [Contacts](/features/contacts) and [newsletter](/features/newsletter) with double opt-in | ✓ | ✓ |
| [Catalog](/features/catalog) and [orders](/features/orders) in Brevo Ecommerce | ✓ | ✓ |
| [Tracker](/features/tracking) and cart, order and customer [events](/features/events) | ✓ | ✓ |
| Diagnostics and sync commands | ✓ | ✓ |
| Contact attributes and events | YAML | [Per channel, in the admin](./contact-attributes) |
| [Sylius emails designed in the Brevo editor](./transactional-emails), with Twig as fallback | | ✓ |
| [Sent emails log](./sent-emails): delivery status, blocked addresses and unblocking | | ✓ |
| Starter templates in English and Spanish, previews and test sends | | ✓ |
| [Cart and payment recovery](./recovery) with signed links and UTM | | ✓ |
| [Order shipped, cancelled, refunded and review events](./events) | | ✓ |
| [Webhooks](./webhooks): Brevo unsubscriptions back in Sylius | | ✓ |
| [Past orders](./past-orders) imported as history | | ✓ |

## Coming next

Included in Pro as updates, no add-ons:

- Back-in-stock and price drop alerts
- Brevo coupon collections from Sylius promotions
- Transactional SMS and WhatsApp, with their opt-in
- Loyalty programs with tiers, redemption at checkout and Wallet passes
- B2B CRM: companies and deals from orders
- Brevo payment links and custom objects
- Reporting: revenue attributed to campaigns and automations, email and SMS statistics

## Installing it

Pro is a Composer package served from Odiseo's private repository, with the credentials of your
license. See [Installation](./installation).

# Tracking

With the **Tracking** module on, the Brevo tracker loads on every shop page with the channel's client
key. Saving the configuration fills the key from the Brevo account (Automation > Settings); type
another one in the Tracking tab, or leave it blank to fetch it again.

The module also sends the shop [events](./events) that trigger automations.

## What the tracker sends

- **Page views**: one per page, with the page title, its path and its canonical URL
  (`<link rel="canonical">`, else the URL without query string) keeping the `utm_*` parameters. The
  tracker's own page view is turned off.
- **Identify**: the visitor is identified by email (and `ext_id`, the customer id) on the first page
  after signing in, registering or giving an email in the checkout, so guests are identified too.
- **Product and category views** in Brevo Ecommerce, with the catalog module on (see
  [Events](./events#catalog-views)).

## Consent

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

::: info Server events
Consent covers what runs in the browser. Server events (cart, order and customer events) go through
the Events API once the cart or the customer has an email.
:::

## Content Security Policy

Allow scripts from `cdn.brevo.com` and `sibautomation.com`, and connections to
`in-automate.brevo.com`.

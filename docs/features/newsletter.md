# Newsletter

Turn on the **Newsletter** module (it needs Contacts) and choose a **newsletter list** in the channel
configuration.

![The Newsletter tab](/images/configuration-newsletter.png){.screenshot}

## The list

- Customers subscribed to the newsletter (registration, profile, admin) join the list; unsubscribing
  in Sylius removes them from it. Contacts added to the list from Brevo are never removed.
- Subscribers are synced even when the channel doesn't sync guests.
- Turning the module off stops the list sync, the form and the double opt-in; the chosen list is
  kept. It can't be turned on without Contacts.

## The shop form

Every shop page (the checkout has no footer) ends with a subscription section right before the
footer. Visitors without an account become customers without one, subscribed to the newsletter.

![The subscription section of the shop](/images/newsletter-section.png){.screenshot}

It's a Live Component: it subscribes without leaving the page and shows the result in place (without
JavaScript the form posts and comes back to the section). A hidden field keeps bots out, there's no
CSRF token so pages stay cacheable, and customers already subscribed don't see it.

Its texts are translation keys (`odiseo_brevo.ui.newsletter.title`, `subtitle`, `email`, `subscribe`,
`consent`). The template is `@OdiseoSyliusBrevoPlugin/shop/newsletter/form.html.twig`; override it in
`templates/bundles/OdiseoSyliusBrevoPlugin/`.

To remove the section, or move it (to the homepage only, for instance), disable its hook and render
the `odiseo_brevo:shop:newsletter_form` component where you want it:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_shop.base.footer':
            odiseo_brevo_newsletter:
                enabled: false
```

## Headless shops

Subscribe through the API. It always answers `202`; without an email it takes the logged-in
customer's:

```http
POST /api/v2/shop/newsletter-subscriptions
Content-Type: application/ld+json

{"email": "jane@example.com"}
```

## Double opt-in

::: v-pre
With a Brevo template ID in the channel configuration (a double opt-in template with the
`{{ doubleoptin }}` link), subscriptions from the form or the API wait for the confirmation. Brevo
emails the link and, once followed, the visitor lands on a signed shop URL (valid 30 days) that
subscribes them. The logged-in customer's own email is subscribed right away.
:::

## Unsubscriptions made in Brevo

Unsubscriptions from Brevo email footers don't reach Sylius with the free plugin.

<ProNotice feature="Bringing Brevo unsubscriptions back to Sylius" />

With Pro, Brevo [webhooks](/pro/webhooks) keep the newsletter flag in Sylius in sync: campaign
unsubscriptions and deleted contacts leave the newsletter, contacts added to the list by a Brevo form
join it.

# Troubleshooting

Start with the debug command. It prints what each channel ends up with and tests its connection:

```bash
bin/console odiseo:brevo:debug
```

## Nothing reaches Brevo

| Check | Why |
| --- | --- |
| The channel has a configuration, enabled, with an API key (own or `api.key`) | Without one, Brevo is off for that channel |
| The module is on | Each feature checks its module |
| **Queue transport** in the debug output | With an async transport, nothing is sent until `messenger:consume odiseo_brevo` runs |
| `messenger:failed:show --transport=odiseo_brevo_failed` | Requests Brevo rejected end up there, with Brevo's answer |
| The `brevo` log channel | Every request is logged, with its status |

## Brevo rejects the API key

`odiseo:brevo:debug` fails with the channel's error, and **Test connection** says so in the admin.
Check the key was created in the same Brevo account, and that the account has no IP restriction for
API keys (Brevo > Security > Authorised IPs) that leaves your servers out.

## Some contact attributes are missing in Brevo

Only the attributes the account has are sent; the missing ones are logged. Create them:

```bash
bin/console odiseo:brevo:attributes:setup
```

## Products or categories don't show up in Brevo Ecommerce

- Brevo Ecommerce takes a few minutes to activate on an account the first time.
- Only taxons under the channel's menu taxon, and variants the channel sells (enabled, in the channel,
  with a price), are sent.
- A variant is only sent again when its payload changed: use `products:sync --force` to resend.
- Brevo drops big images: keep the `odiseo_brevo_product` filter or one of a similar size.

## Amounts show up in the wrong currency

Brevo uses one currency per account, the base currency of the first channel configured with the
catalog or orders module. Add the missing exchange rates in Sylius: the configuration page lists
them. See [Currencies](/operations/currencies).

## Links point to localhost or to the wrong host

Links use the channel hostname over `https`. Check the channel's hostname, and the router's
`default_uri` for workers and commands. See [URLs](/operations/phone-numbers-and-urls#urls).

## The tracker doesn't load

- The Tracking module is on and the channel has a client key (Tracking tab).
- With a consent cookie configured, nothing loads until the cookie is set or `brevo:consent` is
  dispatched.
- A Content Security Policy must allow `cdn.brevo.com`, `sibautomation.com` and
  `in-automate.brevo.com`.

## Cart events don't arrive

They start once the cart has an email: a signed-in customer, or a guest past the checkout's address
step.

## Unsubscriptions made in Brevo don't reach Sylius

Not with the free plugin. Pro brings them back through [webhooks](/pro/webhooks).

## Pro: an email still goes out with the Twig template <span class="pro-badge">Pro</span>

The Transactional emails module must be on, the email mapped and enabled for the channel in
**Brevo > Emails**, with a template for the language (or the channel default one). Otherwise Sylius
sends its own email, by design.

## Pro: webhooks don't arrive <span class="pro-badge">Pro</span>

Register them again after changing the API key, the modules or the channel hostname. Brevo must reach
`https://<channel hostname>/brevo/webhook/...`, and with `webhooks.allowed_ips` set, Brevo's ranges
must be in it.

# Console commands

| Command | What it does |
| --- | --- |
| `odiseo:brevo:debug` | Settings of each channel, queue transport and connection check |
| `odiseo:brevo:sync` | Every sync of the modules that are on, in order |
| `odiseo:brevo:attributes:setup` | Creates the contact attributes the account is missing |
| `odiseo:brevo:contacts:sync` | Bulk import of customers into the channel lists |
| `odiseo:brevo:ecommerce:activate` | Activates Brevo Ecommerce on the account |
| `odiseo:brevo:categories:sync` | Sends the channel's taxons |
| `odiseo:brevo:products:sync` | Sends the channel's product variants |
| `odiseo:brevo:orders:sync` <span class="pro-badge">Pro</span> | Imports past orders |
| `odiseo:brevo:templates:scaffold` <span class="pro-badge">Pro</span> | Creates starter email templates |
| `odiseo:brevo:webhooks:register` <span class="pro-badge">Pro</span> | Registers the Brevo webhooks |
| `odiseo:brevo:logs:purge` <span class="pro-badge">Pro</span> | Removes old email logs and webhook events |

The syncs, `attributes:setup`, `templates:scaffold` and `webhooks:register` take `--dry-run`, and
every command but `logs:purge` takes `--channel=CODE`.

## Diagnostics

See what each channel ends up with (configuration, modules, queue transport) and check its
connection:

```bash
bin/console odiseo:brevo:debug [--channel=WEB] [--no-connection]
```

It fails when Brevo rejects a channel's API key or can't be reached, so it works as a health check.
API keys are shown masked. With Pro it also lists the email test mode and hidden copy, mapped Brevo
emails, registered webhooks, events changed in the channel and the UTM of the recovery links.

Add rows with a service implementing `DebugInfoProviderInterface`, tagged
`odiseo_brevo.debug_info_provider`.

## Initial load and catch-up

```bash
bin/console odiseo:brevo:sync --dry-run
bin/console odiseo:brevo:sync [--channel=WEB] [--step=products --step=contacts]
```

Each step is one of the commands above, run with `--channel` and `--dry-run` when it takes them: categories,
products, contacts, and with Pro, past orders. A failed step doesn't stop the next ones, and the
command fails at the end.

Add a step with a `SyncStep` service (name, module, command) tagged `odiseo_brevo.sync_step`; the
higher priority runs first (categories 300, products 200, contacts 100).

## The rest

Each command is described with its feature: [contacts](/features/contacts#importing-existing-customers),
[catalog](/features/catalog), [past orders](/pro/past-orders),
[starter templates](/pro/transactional-emails#starter-templates), [webhooks](/pro/webhooks) and
[sent emails](/pro/sent-emails#cleaning-up).

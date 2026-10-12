# Configuration reference

Per-channel settings (API key, sender, modules, lists, tracker key…) live in **Brevo > Configuration**
in the admin. The YAML below sets the defaults for every channel. Every key is optional.

## `odiseo_sylius_brevo`

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
        statuses: {}         # pending, paid, shipped, fulfilled, cancelled, refunded => Brevo status
    url:
        image_filter: 'odiseo_brevo_product'   # Liip Imagine filter for images sent to Brevo (600px JPEG)
```

| Key | See |
| --- | --- |
| `api.*` | [Your first channel](/getting-started/first-channel), [Background processing](/operations/background-processing#retries) |
| `phone.default_region` | [Phone numbers](/operations/phone-numbers-and-urls#phone-numbers) |
| `contacts.attributes` | [Contacts](/features/contacts#attributes) |
| `tracking.consent_cookie` | [Tracking](/features/tracking#consent) |
| `tracking.events` | [Events](/features/events#turning-events-off-or-renaming-them) |
| `orders.statuses` | [Orders](/features/orders#statuses) |
| `url.image_filter` | [Images](/operations/phone-numbers-and-urls#images) |

## `odiseo_sylius_brevo_pro` <span class="pro-badge">Pro</span>

```yaml
odiseo_sylius_brevo_pro:
    recovery:
        link_ttl: 30         # days a cart recovery link works
    webhooks:
        allowed_ips: []      # IPs or CIDR ranges Brevo calls from; empty accepts any (the token is always checked)
```

| Key | See |
| --- | --- |
| `recovery.link_ttl` | [Cart and payment recovery](/pro/recovery#abandoned-carts) |
| `webhooks.allowed_ips` | [Webhooks](/pro/webhooks#restricting-by-ip) |

## Environment variables

| Variable | Default |
| --- | --- |
| `ODISEO_BREVO_MESSENGER_TRANSPORT_DSN` | `sync://` |
| `ODISEO_BREVO_MESSENGER_FAILED_TRANSPORT_DSN` | `doctrine://default?queue_name=odiseo_brevo_failed` |

To see the configuration each channel ends up with, run `bin/console odiseo:brevo:debug`.

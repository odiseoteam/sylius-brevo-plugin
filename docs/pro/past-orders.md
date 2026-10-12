# Past orders <span class="pro-badge">Pro</span>

<ProNotice />

The free plugin sends orders as they change. To fill Brevo's ecommerce dashboard and the customers'
purchase history with the orders placed before, import them once the Orders module is on:

```bash
bin/console odiseo:brevo:orders:sync --dry-run
bin/console odiseo:brevo:orders:sync --channel=WEB --from=2024-01-01 --to=2024-12-31
bin/console odiseo:brevo:orders:sync --after-id=1500   # resume after a failure
```

- Only orders with a completed checkout are sent, in batches of up to 1000 (`--batch-size`), with
  the same payload as the live sync.
- Brevo imports them in the background as past orders, so they **trigger no automation**.
- A customer unknown to Brevo becomes an unsubscribed contact.
- Sending an order again replaces it.

With Pro installed, `odiseo:brevo:sync` runs this as its last step (with the Orders module on).

# Sylius Brevo Plugin

Brevo integration for Sylius 2.x: contacts and newsletter, ecommerce catalog and order sync,
tracking and automation events, transactional emails built with the Brevo editor, coupons,
SMS, WhatsApp, loyalty and more.

> Work in progress. See [ROADMAP.md](ROADMAP.md) for scope and status.

## Requirements

| Package | Version |
| --- | --- |
| PHP | ^8.2 |
| Sylius | ^2.0 |

## Development

Everything runs in Docker:

```bash
make init           # install dependencies and start the stack
make phpunit        # PHPUnit
make behat          # Behat
make phpstan ecs    # static analysis and coding standard
```

## License

Proprietary. See [LICENSE](LICENSE).

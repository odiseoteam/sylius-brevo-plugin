# Currencies

Brevo shows amounts in a single currency per account: the base currency of the first configured
channel of that account (catalog or orders module).

- Prices and order amounts of channels sharing the account in another currency are converted with the
  Sylius exchange rates. The original order amount stays in `metaInfo`.
- Without an exchange rate they're sent unconverted and a warning is logged. The configuration page
  lists the missing rates.
- A product sold in several channels is read from one in the account's currency when possible.

Events keep their own currency: amounts in [events](/features/events) are in the order's currency
(the channel's for products), with a `currency` property.

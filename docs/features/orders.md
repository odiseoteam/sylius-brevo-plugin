# Orders

With the **Orders** module on, completed orders (never carts) are sent to Brevo Ecommerce when their
checkout, order, payment or shipping state changes, from the shop, the admin, the API or the CLI.
Brevo links them to the contact by email and `ext_id`, creating it (unsubscribed) when missing, and
fills the ecommerce dashboard and the contact's purchase history with them.

Saving the configuration with the module on activates Brevo Ecommerce, as with the
[catalog](./catalog).

## What is sent

| Field | Value |
| --- | --- |
| `id` | Order number |
| `status` | `cancelled`, `refunded`, `fulfilled`, `shipped`, `paid` or `pending`, the first that applies |
| `amount` | Order total |
| `products` | Variant code (the Brevo product id), quantity and unit price after discounts |
| `billing` | Billing address, phone in E.164 and payment method |
| `coupons` | Promotion coupon |
| `metaInfo` | Currency, items, shipping, tax and discount totals, shipping method |

## Statuses

Brevo takes any status. Choose the ones counted as revenue in Brevo > Settings > E-commerce > Order
statuses, or rename them:

```yaml
odiseo_sylius_brevo:
    orders:
        statuses:
            fulfilled: completed
```

## Extending

- Decorate `OrderStatusMapperInterface` (`odiseo_brevo.order.status_mapper`) for other status rules.
- Add fields with a service implementing `OrderPayloadProviderInterface`, tagged
  `odiseo_brevo.order_payload_provider` (`metaInfo` and `billing` are merged by key).

<ProNotice feature="Importing the orders placed before installing" />

The free plugin sends orders as they change. Pro imports the [past orders](/pro/past-orders) as
historical batches, so the dashboard and every customer's history start complete.

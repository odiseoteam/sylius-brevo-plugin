# Events

With the [Tracking](./tracking) module on, the plugin sends events to use as automation triggers:
browse and cart abandonment, welcome series, post-purchase.

## The events

| Event | Sent from | When | Properties |
| --- | --- | --- | --- |
| `product_viewed` | browser | a product page is shown | `product_id`, `name`, `price`, `currency`, `url`, `image` |
| `category_viewed` | browser | a taxon's listing is shown | `category_id`, `name`, `url` |
| `cart_updated` | server | the cart gets items, changes, or gets the customer's email | `cart_id`, `total`, `currency`, `url` (the cart), `items` |
| `cart_deleted` | server | the last item leaves the cart | same as `cart_updated` |
| `order_completed` | server | the checkout is completed | `order_id`, `total`, `items_total`, `shipping_total`, `tax_total`, `discount_total`, `currency`, `coupon`, `items` |
| `order_paid` | server | the order gets fully paid | same as `order_completed` |
| `customer_registered` | server | a customer registers in the shop | `first_name`, `last_name`, `subscribed_to_newsletter` |

`items` lists `product_id` (variant code), `name`, `variant_name`, `quantity`, `price` (unit price
after discounts), `url` and `image`. Amounts are in the order's currency (the channel's for
products).

- **Browser events** are pushed by the tracker, so they wait for consent like the rest.
- **Server events** go through the Events API and identify the contact by email and `ext_id`.
- **Cart events** start once the cart has an email (signed-in customer or the checkout's address
  step) and are sent once per request with the cart's final state.

<ProNotice feature="Order shipped, cancelled and refunded, reviews, and cart recovery links" />

Pro adds `order_awaiting_payment`, `order_shipped`, `order_cancelled`, `order_refunded`,
`review_submitted` and `review_accepted`, and puts a signed `recovery_url` in `cart_updated` that
restores the cart, guest or not. See [Order and review events](/pro/events) and
[Cart and payment recovery](/pro/recovery).

## Catalog views

With the [catalog](./catalog) module on, the tracker also marks the product (its default variant) or
the category as viewed in Brevo Ecommerce (`viewProduct`, `viewCategory`), linked to the synced
catalog.

## Turning events off or renaming them

Names in Brevo take letters, digits, `-` and `_`:

```yaml
odiseo_sylius_brevo:
    tracking:
        events:
            category_viewed: { enabled: false }
            cart_updated: { name: cart-updated }
```

With Pro, each channel does it from the admin. See [Order and review events](/pro/events#per-channel-settings).

## Extending

- Add properties with a service implementing `EventPropertiesProviderInterface`, tagged
  `odiseo_brevo.event_payload_provider`.
- Add events with a `TrackingEventInterface` tagged `odiseo_brevo.tracking_event`. One with the same
  code replaces the plugin's.
- Decide per channel by decorating `TrackingEventSettingsInterface`
  (`odiseo_brevo.tracking.event_settings`).
- Send your own event of a placed order or of a customer by dispatching `TrackOrderEvent` or
  `TrackCustomerEvent` with its code through `BrevoMessageDispatcherInterface`. For any other subject,
  call `TrackingEventSenderInterface` from your own message handler.

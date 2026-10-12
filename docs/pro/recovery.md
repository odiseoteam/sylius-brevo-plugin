# Cart and payment recovery <span class="pro-badge">Pro</span>

<ProNotice />

With the Tracking module on, Brevo gets what an automation needs to bring a customer back:

| Event | Property | Link |
| --- | --- | --- |
| `cart_updated` | `recovery_url` | A signed link that puts the cart back in the visitor's session, guest or not, and goes on to the checkout. `url` stays the plain cart page. |
| `order_awaiting_payment` | `payment_url` | The order page, where the customer pays it. |

## Abandoned carts

Build the automation on `cart_updated`: wait, stop it on `order_completed`, and use
`recovery_url` as the button link. A guest who follows it gets the cart back in a new session, on any
device.

A recovery link works for 30 days from the cart event that carries it, while the cart is still a cart
of that channel. Otherwise the visitor lands on the cart page with a notice. Change it with:

```yaml
odiseo_sylius_brevo_pro:
    recovery:
        link_ttl: 14 # days
```

## Unpaid orders

`order_awaiting_payment` is sent when the checkout is completed and the order is still unpaid, with
the same properties as `order_completed` plus `payment_url`. Let the automation wait and stop it on
`order_paid`. Turn it off or rename it per channel like any other event.

## UTM parameters

Both links take the `utm_source`, `utm_medium` and `utm_campaign` set in **Brevo > Configuration**
(Tracking tab, "Recovery links"). The recovery link keeps them when it goes on to the checkout, so
recovered sales show up in your analytics.

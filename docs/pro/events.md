# Order and review events <span class="pro-badge">Pro</span>

<ProNotice />

With the Tracking module on, Pro adds these server events to the [free ones](/features/events), to
use as automation triggers.

| Event | When | Properties |
| --- | --- | --- |
| `order_awaiting_payment` | the checkout is completed and the order is unpaid | same as `order_completed`, plus `payment_url` |
| `order_shipped` | every shipment of the order is shipped | same as `order_completed`, plus `shipping_method` and `tracking_code` (the last shipment's) |
| `order_cancelled` | the order is cancelled | same as `order_completed` |
| `order_refunded` | the order is fully refunded | same as `order_completed` |
| `review_submitted` | a customer writes a product review | `review_id`, `rating`, `title`, `comment`, `product_id`, `product_name`, `product_url` |
| `review_accepted` | the store accepts the review | same as `review_submitted` |

Order events go to the order's customer through its channel. Review events go to the author; a review
has no channel, so they are sent through the product's channels with Tracking on, once per Brevo
account.

## Per-channel settings

The Tracking tab lists every event, the free and the Pro ones and those added with the
`odiseo_brevo.tracking_event` tag, with where it is sent from. Untick **Send** to turn it off in that
channel, or type its name in Brevo (letters, digits, `-` and `_`). Empty keeps the one from
`odiseo_sylius_brevo.tracking.events`; only the differences are stored.

![Events of a channel, with their names in Brevo](/images/pro-tracking-events.png){.screenshot}

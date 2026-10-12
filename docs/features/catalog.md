# Catalog

With the **Catalog** module on, the channel's taxons and product variants are sent to Brevo
Ecommerce, where campaigns and automations can show them (product blocks, recommendations, abandoned
cart emails).

Saving the configuration with the module on activates Brevo Ecommerce on the account and shows
amounts in the account's currency (see [Currencies](/operations/currencies)). The first activation
takes Brevo a few minutes. From the CLI:

```bash
bin/console odiseo:brevo:ecommerce:activate --channel=WEB
```

## Categories

The channel's taxons become Brevo categories: id = taxon code, name with its path (such as
`T-shirts > Men`), and URL in the channel's default locale.

- Only the taxons under the channel's menu taxon are sent (every non-root taxon without one).
- A disabled taxon, one moved out, or a deleted one is sent as deleted, since Brevo can't delete
  categories.
- Later changes are sent as taxons are created, edited, moved or deleted.

```bash
bin/console odiseo:brevo:categories:sync --dry-run
bin/console odiseo:brevo:categories:sync
```

## Products

Product variants become Brevo products, grouped under their product (`parentId`, left out when the
only variant shares the product code):

| Field | Value |
| --- | --- |
| `id`, `sku` | Variant code |
| `name` | Product name in the channel's default locale |
| `url` | Product page |
| `imageUrl` | Variant image, else the product's (a `main` one first), through the `url.image_filter` Liip filter: 600px JPEG by default, since Brevo drops big images and email clients don't read WebP |
| `price`, `alternativePrice` | Channel price, and the original price when it's higher |
| `stock` | On hand minus on hold, for tracked variants |
| `categories` | Product taxons in the channel, with their parents |
| `description` | Short description, else the description, as plain text |
| `metaInfo` | Option values (`size: M`) and the variant name when the product has several |

- A disabled variant or product, or one the channel doesn't sell (not in the channel or without a
  price), is sent as deleted.
- Changes to products, variants, prices, stock, images or taxons are sent after saving.
- A variant is only sent again when its payload changed.

```bash
bin/console odiseo:brevo:products:sync --dry-run
bin/console odiseo:brevo:products:sync    # --after-id=123 resumes, --force resends unchanged ones
```

Channels sharing a Brevo account share its catalog and its currency.

## Extending

- Add fields (brand, attributes…) with a service implementing `ProductPayloadProviderInterface`,
  tagged `odiseo_brevo.product_payload_provider`; `metaInfo` is merged by key.
- Decorate `odiseo_brevo.catalog.category_payload_builder` to change what is sent for categories.

<ProNotice feature="Back-in-stock and price drop alerts" />

Stock and price alerts are on the Pro roadmap: a "Notify me" button on out-of-stock products, backed
by Brevo's product alerts.

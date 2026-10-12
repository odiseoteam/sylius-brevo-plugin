# Phone numbers and URLs

## Phone numbers

Brevo requires phone numbers in E.164 (`+5491122334455`). Numbers without an international prefix
take their country from the address, then from the channel when it has a single country, then from
`phone.default_region`:

```yaml
odiseo_sylius_brevo:
    phone:
        default_region: AR
```

Numbers that can't be resolved are not sent.

## URLs

Links and images sent to Brevo use the channel hostname over `https`, never the host of the current
request, so a sync run from the admin or a worker builds the same links as the shop.

When the hostname matches the `default_uri` host (`http://localhost:8090` locally, for instance), its
scheme and port are kept.

## Images

Product images go through a Liip Imagine filter, `odiseo_brevo_product` (600px JPEG) by default:
Brevo drops big images and email clients don't read WebP. Use your own with:

```yaml
odiseo_sylius_brevo:
    url:
        image_filter: my_filter
```

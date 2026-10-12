# Extending

Everything is extended with tagged services, decorators and Twig hooks: no need to patch the plugin.

## Payloads

| To | Implement | Tag |
| --- | --- | --- |
| Add contact attributes | `ContactAttributeProviderInterface` | `odiseo_brevo.contact_attribute_provider` |
| Add product fields | `ProductPayloadProviderInterface` | `odiseo_brevo.product_payload_provider` |
| Add order fields | `OrderPayloadProviderInterface` | `odiseo_brevo.order_payload_provider` |
| Add event properties | `EventPropertiesProviderInterface` | `odiseo_brevo.event_payload_provider` |
| Add or replace an event | `TrackingEventInterface` | `odiseo_brevo.tracking_event` |
| Add rows to `odiseo:brevo:debug` | `DebugInfoProviderInterface` | `odiseo_brevo.debug_info_provider` |
| Add a step to `odiseo:brevo:sync` | a `SyncStep` service | `odiseo_brevo.sync_step` |

`metaInfo` (products and orders) and `billing` (orders) are merged by key, so several providers can
add to them.

## Decorators

| Service | Interface | For |
| --- | --- | --- |
| `odiseo_brevo.contact.attribute_mapping` | `AttributeMappingInterface` | Attribute names per channel |
| `odiseo_brevo.catalog.category_payload_builder` | | What is sent for a category |
| `odiseo_brevo.order.status_mapper` | `OrderStatusMapperInterface` | Order status rules |
| `odiseo_brevo.tracking.consent_checker` | `TrackingConsentCheckerInterface` | Tracking consent |
| `odiseo_brevo.tracking.event_settings` | `TrackingEventSettingsInterface` | Events on or off, and their names, per channel |

## Example: a contact attribute

```php
namespace App\Brevo;

use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\ContactAttributeProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('odiseo_brevo.contact_attribute_provider')]
final class VipProvider implements ContactAttributeProviderInterface
{
    public function getAttributeTypes(): array
    {
        return ['vip' => Attribute::TYPE_BOOLEAN];
    }

    public function provide(CustomerInterface $customer, ChannelInterface $channel): array
    {
        return ['vip' => 'VIP' === $customer->getGroup()?->getCode()];
    }
}
```

The key (`vip`) is sent as `VIP` unless `contacts.attributes` maps it to another name, and
`odiseo:brevo:attributes:setup` creates it with that type. Values are ready for Brevo (dates as
`Y-m-d`, amounts as decimals); `null` or `""` is not sent.

## Sending your own events

Dispatch `TrackOrderEvent` or `TrackCustomerEvent` with the event code through
`BrevoMessageDispatcherInterface`: it's sent after the response, once per request. For any other
subject, write your own message and call `TrackingEventSenderInterface` from its handler.

Never call Brevo from a request: implement `BrevoMessageInterface`, send it with
`BrevoMessageDispatcherInterface` and handle it on the `odiseo_brevo.bus`.

## Admin

The channel configuration form is split in tabs. Add one with the
`side_navigation/tab.html.twig` and `sections/tab.html.twig` templates (`tab`, `label` and optional
`module` in the hookable configuration) on the
`odiseo_brevo.admin.channel_configuration.{create,update}.content.form.side_navigation` and
`.sections` hooks, and cards in `.sections.<tab>`.

## Shop

The tracker and the newsletter section are Twig hooks (`sylius_shop.base.head`,
`sylius_shop.base.footer`): disable or move them like any other hook. Templates can be overridden in
`templates/bundles/OdiseoSyliusBrevoPlugin/`.

## Pro <span class="pro-badge">Pro</span>

| To | Implement | Tag |
| --- | --- | --- |
| Add email params | `EmailParamsProviderInterface` | `odiseo_brevo_pro.email_params_provider` |
| Add samples for an email | `EmailSampleProviderInterface` | `odiseo_brevo_pro.email_sample_provider` |
| Handle more webhook events | `WebhookEventHandlerInterface` | `odiseo_brevo_pro.webhook_event_handler` |
| Register more webhook types | `WebhookDefinitionInterface` | `odiseo_brevo_pro.webhook_definition` |

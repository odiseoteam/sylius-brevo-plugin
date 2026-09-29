# Changelog

## [Unreleased]

### Changed

- MIT license.

### Added

- Brevo configuration form in tabs (General, Contacts, Newsletter), shown per module, with hooks for plugins to add tabs and cards.
- `AttributeMappingInterface`: the contact attribute mapping can change per channel (`odiseo:brevo:attributes:setup` uses each channel's names).
- `ContactSyncPauseInterface`: applies customer changes that came from Brevo without syncing them back.
- `Testing\FakeBrevoHttpClient`: test double of the Brevo API for apps and plugins built on this one.

- Plugin skeleton on `sylius/test-application`, Docker environment and CI.
- Brevo HTTP client: per-call credentials, typed exceptions, short in-process retries, `brevo` log channel with masked personal data.
- `AccountApi` to read the Brevo account.
- `odiseo_sylius_brevo.api` configuration (`base_url`, `timeout`, `max_retries`).
- Payload helpers: `MoneyFormatter` (currency fraction digits), `DateFormatter` (UTC ISO 8601), `PhoneNumberNormalizer` (E.164, country from address, channel or default) and `ChannelUrlGenerator` (shop and image URLs on the channel hostname).
- `odiseo_sylius_brevo.phone.default_region` and `odiseo_sylius_brevo.url.image_filter` configuration.
- Brevo configuration per channel in the admin (Brevo > Configuration): API key encrypted with the Sylius encryption key, default sender, modules and a "Test connection" button.
- `odiseo_sylius_brevo.api.key` fallback API key.
- `ModuleInterface` (tag `odiseo_brevo.module`) and `ModuleCheckerInterface` to switch features per channel.
- Messenger infrastructure: `odiseo_brevo.bus`, `odiseo_brevo` transport (sync by default, `ODISEO_BREVO_MESSENGER_TRANSPORT_DSN`) and `odiseo_brevo_failed`.
- `BrevoMessageDispatcherInterface`: messages created in a request are sent after the response and never throw.
- Retry strategy aware of Brevo errors: 429 waits `Retry-After`, 5xx backs off, other 4xx go straight to failed.
- `ContactsApi` (upsert, update, find, delete by email, `ext_id`, contact id or phone), `AttributesApi` (list, create) and `ListsApi` (lists and folders, paginated; add/remove contacts in batches of 150).
- Contacts module: customers synced as Brevo contacts (`ext_id` = customer id) when they, their default address or their orders change, with name, phone, gender, birthday, group, channel, locale, address and purchase history attributes. Guests take their data from the order billing address; unknown values are never sent.
- `ContactAttributeProviderInterface` (tag `odiseo_brevo.contact_attribute_provider`) and `odiseo_sylius_brevo.contacts.attributes` to add, rename or skip attributes.
- `odiseo:brevo:attributes:setup` command to create the missing contact attributes in Brevo.
- Channel options: sync guest customers, delete the contact when the customer is deleted.
- Customers list per channel, chosen among the Brevo account lists: every synced contact joins it.
- `odiseo:brevo:contacts:sync` command: bulk import of existing customers (`/contacts/import`) with `--since`, `--only-subscribed`, `--dry-run`, and waits for Brevo's processes.
- `ProcessesApi` and `ContactsApi::import()`.
- Newsletter module: list per channel; `subscribedToNewsletter` joins it, unsubscribing leaves it. Subscribers are synced even when guests are not, and the initial import adds them to the list.
- Shop newsletter section before the footer (Live Component: subscribes in place, works without JavaScript too; honeypot against bots) and `POST /api/v2/shop/newsletter-subscriptions`, with optional double opt-in (Brevo template per channel and a signed confirmation link).
- `ContactsApi::requestDoubleOptIn()`.
- `DependentModuleInterface`: a module can require others; the configuration can't enable it alone.

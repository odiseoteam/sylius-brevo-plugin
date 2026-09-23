# Changelog

## [Unreleased]

### Added

- Plugin skeleton on `sylius/test-application`, Docker environment and CI.
- Brevo HTTP client: per-call credentials, typed exceptions, short in-process retries, `brevo` log channel with masked personal data.
- `AccountApi` to read the Brevo account.
- `odiseo_sylius_brevo.api` configuration (`base_url`, `timeout`, `max_retries`).
- Payload helpers: `MoneyFormatter` (currency fraction digits), `DateFormatter` (UTC ISO 8601), `PhoneNumberNormalizer` (E.164, country from address, channel or default) and `ChannelUrlGenerator` (shop and image URLs on the channel hostname).
- `odiseo_sylius_brevo.phone.default_region` and `odiseo_sylius_brevo.url.image_filter` configuration.
- Brevo configuration per channel in the admin (Brevo > Configuration): API key encrypted with the Sylius encryption key, default sender, modules and a "Test connection" button.
- `odiseo_sylius_brevo.api.key` fallback API key.
- `ModuleInterface` (tag `odiseo_brevo.module`) and `ModuleCheckerInterface` to switch features per channel.

# Changelog

## [Unreleased]

### Added

- Plugin skeleton on `sylius/test-application`, Docker environment and CI.
- Brevo HTTP client: per-call credentials, typed exceptions, short in-process retries, `brevo` log channel with masked personal data.
- `AccountApi` to read the Brevo account.
- `odiseo_sylius_brevo.api` configuration (`base_url`, `timeout`, `max_retries`).

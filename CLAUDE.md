# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this is

Proprietary Sylius 2.x plugin (`odiseoteam/sylius-brevo-plugin`, namespace
`Odiseo\SyliusBrevoPlugin\`) integrating Brevo. Scope, phases and decisions live in
`ROADMAP.md` (Spanish); tick items there as they land.

It is a plugin, not an app: it runs on `sylius/test-application`, configured from
`tests/TestApplication/`.

## Conventions

- Code and comments in English; comments short and to the point.
- Prefix for services, routes, parameters and translations: `odiseo_brevo.*`. Config root: `odiseo_sylius_brevo`.
- Layout follows SyliusRbacPlugin/SyliusVendorPlugin 2.x: `config/`, `templates/`, `translations/`,
  Doctrine XML mapping, Twig Hooks, Symfony Workflow, API Platform XML resources.
- Every Brevo call goes through `Api\*ApiInterface` → `Http\BrevoHttpClientInterface`. Payloads are
  built by tagged providers. Nothing talks to Brevo synchronously from a request: dispatch a message.
- A Brevo failure must never break a shop request or a checkout.
- BDD-first: observable behavior starts as a red Behat scenario; mechanics get PHPUnit tests.
  Tests never hit the real Brevo API.

## Commands

Everything runs in Docker (no host PHP). First time: `make init`.

```bash
make phpunit / make behat / make phpstan / make ecs / make deptrac / make composer-unused
```

Container lint (fast feedback for DI/config changes):

```bash
DOCKER_USER="$(id -u):$(id -g)" docker compose run --rm --no-deps -w /srv/sylius \
  -e APP_ENV=test -e DATABASE_URL="mysql://root:root@mysql/sylius_test" \
  -e SYLIUS_TEST_APP_BUNDLES_PATH="tests/TestApplication/config/bundles.php" \
  -e SYLIUS_TEST_APP_CONFIGS_TO_IMPORT="@OdiseoSyliusBrevoPlugin/tests/TestApplication/config/config.yaml" \
  -e SYLIUS_TEST_APP_ROUTES_TO_IMPORT="@OdiseoSyliusBrevoPlugin/tests/TestApplication/config/routes.yaml" \
  php vendor/bin/console lint:container
```

PHPUnit suites: `unit`, `functional`, `integration`, `non-unit`, `all`.

CI (`.github/workflows/build.yaml`): matrix Sylius 2.0–2.2 × Symfony 6.4/7.4 × PHP 8.2–8.4, plus a
PostgreSQL job.

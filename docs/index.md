---
layout: home

hero:
  name: Sylius Brevo Plugin
  text: Your Sylius store and Brevo, in sync
  tagline: Customers, newsletter, catalog, orders and shop events in Brevo, per channel and in the background, so Brevo can never break a checkout.
  image:
    src: /logo.png
    alt: Sylius Brevo Plugin
  actions:
    - theme: brand
      text: Get started
      link: /getting-started/requirements
    - theme: alt
      text: Compare with Pro
      link: /pro/
    - theme: alt
      text: GitHub
      link: https://github.com/odiseoteam/sylius-brevo-plugin

features:
  - icon: 👥
    title: Contacts and newsletter
    details: Customers and guests as Brevo contacts with their orders data, a newsletter list, a subscription form and double opt-in.
    link: /features/contacts
    linkText: Sync contacts
  - icon: 🛍️
    title: Catalog and orders
    details: Taxons, product variants and orders in Brevo Ecommerce, converted to the account currency and sent only when they change.
    link: /features/catalog
    linkText: Fill Brevo Ecommerce
  - icon: 📈
    title: Tracking and events
    details: The Brevo tracker with consent, page views and identified visitors, plus cart, order and customer events to trigger automations.
    link: /features/tracking
    linkText: Track the shop
  - icon: 🛡️
    title: Never in the way
    details: Every call goes through Symfony Messenger after the response, with retries, rate limits and a failed queue.
    link: /operations/background-processing
    linkText: How it runs
  - icon: ✉️
    title: Transactional emails (Pro)
    details: Sylius emails designed in the Brevo editor, per language, with a delivery log, previews and test sends.
    link: /pro/transactional-emails
    linkText: Design them in Brevo
  - icon: 🛒
    title: Cart recovery (Pro)
    details: Signed links that bring a guest's cart back, payment links for unpaid orders, and the order and review events.
    link: /pro/recovery
    linkText: Recover carts
---

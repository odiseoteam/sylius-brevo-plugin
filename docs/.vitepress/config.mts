import { defineConfig } from 'vitepress'

const pro = (text: string) => `${text} <span class="pro-badge">Pro</span>`

export default defineConfig({
  title: 'Sylius Brevo Plugin',
  description: 'Brevo for Sylius 2: customers, newsletter, catalog, orders, shop events and transactional emails, kept in sync per channel.',
  base: '/',
  cleanUrls: true,
  lastUpdated: true,
  sitemap: { hostname: 'https://docs.brevo.odiseo.io' },
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' }],
    ['meta', { property: 'og:image', content: 'https://docs.brevo.odiseo.io/brevo-og.png' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
  ],

  themeConfig: {
    logo: '/logo.png',
    search: { provider: 'local' },

    nav: [
      { text: 'Getting started', link: '/getting-started/requirements' },
      { text: 'Features', link: '/features/contacts' },
      { text: 'Pro', link: '/pro/' },
      {
        text: 'Reference',
        items: [
          { text: 'Configuration', link: '/configuration/reference' },
          { text: 'Console commands', link: '/operations/console' },
          { text: 'Extending', link: '/extending/' },
          { text: 'Troubleshooting', link: '/operations/troubleshooting' },
        ],
      },
      { text: 'Get Pro', link: 'https://odiseo.io/en/products/sylius-brevo-plugin?utm_source=docs&utm_medium=nav&utm_campaign=sylius-brevo-plugin' },
    ],

    sidebar: [
      {
        text: 'Getting started',
        items: [
          { text: 'Requirements', link: '/getting-started/requirements' },
          { text: 'Installation', link: '/getting-started/installation' },
          { text: 'Your first channel', link: '/getting-started/first-channel' },
        ],
      },
      {
        text: 'Features',
        items: [
          { text: 'Contacts', link: '/features/contacts' },
          { text: 'Newsletter', link: '/features/newsletter' },
          { text: 'Catalog', link: '/features/catalog' },
          { text: 'Orders', link: '/features/orders' },
          { text: 'Tracking', link: '/features/tracking' },
          { text: 'Events', link: '/features/events' },
        ],
      },
      {
        text: pro('Brevo Pro'),
        items: [
          { text: 'Overview', link: '/pro/' },
          { text: 'Installation', link: '/pro/installation' },
          { text: 'Transactional emails', link: '/pro/transactional-emails' },
          { text: 'Sent emails', link: '/pro/sent-emails' },
          { text: 'Cart recovery', link: '/pro/recovery' },
          { text: 'Order and review events', link: '/pro/events' },
          { text: 'Contact attributes', link: '/pro/contact-attributes' },
          { text: 'Webhooks', link: '/pro/webhooks' },
          { text: 'Past orders', link: '/pro/past-orders' },
        ],
      },
      {
        text: 'Operations',
        items: [
          { text: 'Background processing', link: '/operations/background-processing' },
          { text: 'Currencies', link: '/operations/currencies' },
          { text: 'Phone numbers and URLs', link: '/operations/phone-numbers-and-urls' },
          { text: 'Console commands', link: '/operations/console' },
          { text: 'Troubleshooting', link: '/operations/troubleshooting' },
        ],
      },
      {
        text: 'Adapting it',
        items: [
          { text: 'Configuration reference', link: '/configuration/reference' },
          { text: 'Extending', link: '/extending/' },
          { text: 'Testing your integration', link: '/extending/testing' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/odiseoteam/sylius-brevo-plugin' },
    ],

    editLink: {
      pattern: 'https://github.com/odiseoteam/sylius-brevo-plugin/edit/master/docs/:path',
    },

    footer: {
      message: 'Free plugin released under the MIT License. Brevo Pro under a commercial license.',
      copyright: 'by <a href="https://odiseo.io/en?utm_source=docs&utm_medium=footer&utm_campaign=sylius-brevo-plugin" target="_blank">Odiseo</a>',
    },
  },
})

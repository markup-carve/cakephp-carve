import { defineConfig } from 'vitepress'

const docsBase = process.env.DOCS_BASE ?? '/cakephp-carve/'
const docsVersionText = process.env.DOCS_VERSION_TEXT
const docsLatestBase = process.env.DOCS_LATEST_BASE ?? '/cakephp-carve/'
const docsOtherBase = process.env.DOCS_OTHER_BASE ?? '/cakephp-carve/0.1/'
const siteUrl = (base: string) => `https://markup-carve.github.io${base}`

export default defineConfig({
  title: 'CakePHP Carve',
  description: 'CakePHP plugin for Carve: rendering, views, validation, ORM behavior, commands and caching',

  base: docsBase,

  cleanUrls: true,

  head: [
    ['link', { rel: 'icon', href: `${docsBase}favicon.svg`, type: 'image/svg+xml' }],
    // The unreleased series must not compete with the released docs in search results.
    // Marks a versioned build, so the theme styles only the version menu as a switch.
    ...(docsVersionText ? [['meta', { name: 'docs-version', content: docsVersionText }] as [string, Record<string, string>]] : []),
    ...(process.env.VITE_DOCS_SERIES === 'dev' ? [['meta', { name: 'robots', content: 'noindex' }] as [string, Record<string, string>]] : []),
  ],

  markdown: {
  },

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
      {
        text: 'Links',
        items: [
          { text: 'Live demo', link: 'https://sandbox.dereuromark.de/sandbox/cake-carve-examples' },
          { text: 'Playground', link: 'https://sandbox.dereuromark.de/sandbox/carve' },
          { text: 'carve-php', link: 'https://github.com/markup-carve/carve-php' },
          { text: 'Carve Docs', link: 'https://markup-carve.github.io/carve/' },
          { text: 'Changelog', link: 'https://github.com/markup-carve/cakephp-carve/releases' },
          { text: 'Packagist', link: 'https://packagist.org/packages/markup-carve/cakephp-carve' },
          { text: 'Issues', link: 'https://github.com/markup-carve/cakephp-carve/issues' },
        ],
      },
      ...(docsVersionText ? [{
        text: docsVersionText,
        items: [
          { text: process.env.DOCS_LATEST_LABEL ?? '0.2 (latest)', link: siteUrl(docsLatestBase), target: '_self' },
          { text: process.env.DOCS_OTHER_LABEL ?? '0.1', link: siteUrl(docsOtherBase), target: '_self' },
        ],
      }] : []),
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting started', link: '/guide/' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Carve syntax', link: '/guide/carve-syntax' },
            { text: 'Configuration', link: '/guide/configuration' },
          ],
        },
        {
          text: 'Usage',
          items: [
            { text: 'Rendering and options', link: '/guide/rendering-and-options' },
            { text: 'Safe mode', link: '/guide/safe-mode' },
            { text: 'Views and helper', link: '/guide/views-and-helper' },
            { text: 'Validation', link: '/guide/validation' },
            { text: 'Behavior', link: '/guide/behavior' },
            { text: 'Commands', link: '/guide/commands' },
          ],
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Caching', link: '/guide/caching' },
            { text: 'Extensions', link: '/guide/extensions' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/markup-carve/cakephp-carve' },
    ],

    search: {
      provider: 'local',
    },

    editLink: {
      pattern: `https://github.com/markup-carve/cakephp-carve/edit/${process.env.DOCS_EDIT_BRANCH ?? 'main'}/docs/:path`,
      text: 'Edit this page on GitHub',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Markup Carve',
    },
  },
})

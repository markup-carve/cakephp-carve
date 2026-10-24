import { defineConfig } from 'vitepress'
import { carveMarkdown } from 'carve-grammars/shiki'

export default defineConfig({
  title: 'CakePHP Carve',
  description: 'CakePHP plugin for Carve: rendering, views, validation, ORM behavior, commands and caching',

  base: '/cakephp-carve/',

  cleanUrls: true,

  head: [
    ['link', { rel: 'icon', href: '/cakephp-carve/favicon.svg', type: 'image/svg+xml' }],
  ],

  markdown: {
    ...carveMarkdown(),
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
      pattern: 'https://github.com/markup-carve/cakephp-carve/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Markup Carve',
    },
  },
})

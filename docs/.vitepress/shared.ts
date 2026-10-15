import { DefaultTheme } from 'vitepress'

export const shared: DefaultTheme.Config = {
  i18nRouting: (_data, route, targetLocale) => {
    const page = route.data.relativePath.replace(/^en\//, '').replace(/\.md$/, '')
    if (page.startsWith('changelog/')) {
      return `${targetLocale === 'en' ? '/en' : ''}/${page}${route.query}${route.hash}`
    }
    return targetLocale === 'en' ? '/en/' : '/darbai'
  },
  // Common social links
  socialLinks: [
    { icon: 'github', link: 'https://github.com/vu-sa/vusa.lt' }
  ],
  
  // Last updated footer text
  lastUpdated: {
    text: 'Last Updated'
  },

  // Edit link configuration
  editLink: {
    pattern: 'https://github.com/vu-sa/vusa.lt/edit/main/docs/:path',
    text: 'Edit this page on GitHub'
  },

  // Document footer text
  docFooter: {
    prev: 'Previous page',
    next: 'Next page'
  },
  
  // Footer configuration
  footer: {
    message: 'Released under the MIT License.',
    copyright: `Copyright © ${new Date().getFullYear()} VU Students' Representation`
  }
}

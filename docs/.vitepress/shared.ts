import { DefaultTheme } from 'vitepress'

export const shared: DefaultTheme.Config & Record<string, any> = {
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
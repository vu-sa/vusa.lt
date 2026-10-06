import { describe, expect, it } from 'vitest'
import { appUrl, siteUrl, toCmarkerMarkdown } from '../../../pdf/markdown.ts'

const convert = (body: string) => toCmarkerMarkdown(body, {
  prefix: 'p-changelog-v3',
  file: 'changelog/v3.md',
  pagesInPdf: new Map(),
  missingScreenshots: [],
})

describe('platform links in the PDF', () => {
  it('points app: links at the platform and marks them, leaving guide links on the guide', () => {
    expect(convert('[Pranešimai](/pagrindai/pranesimai), [Pranešimų nustatymai](app:/mano/profile/notifications)')).toBe(
      `[Pranešimai](${siteUrl}/pagrindai/pranesimai), [Pranešimų nustatymai ↗](${appUrl}/mano/profile/notifications)`,
    )
  })
})

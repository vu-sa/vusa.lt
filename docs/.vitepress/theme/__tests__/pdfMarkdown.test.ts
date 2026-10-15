import { describe, expect, it } from 'vitest'
import { omitTechnicalInformation, splitFrontmatter, toCmarkerMarkdown } from '../../../pdf/markdown.ts'

describe('technical information in the guide PDF', () => {
  it.each(['## Techninė informacija', '## Techninė informacija {#technine-informacija}'])('omits %s and its nested content', heading => {
    const body = ['# Rezervacijos', '', '## Veiksmai', 'Pateik rezervaciją.', '', heading, 'Techninės detalės.', '### Teisės', '`ReservationPolicy`', ''].join('\n')

    expect(omitTechnicalInformation(body)).toBe('# Rezervacijos\n\n## Veiksmai\nPateik rezervaciją.\n')
  })

  it.each(['## Toliau', '# Kitas puslapis'])('preserves content starting at %s', heading => {
    const body = ['## Techninė informacija', 'Detalės.', '### Poskyris', 'Daugiau detalių.', heading, 'Matomas tekstas.'].join('\n')

    expect(omitTechnicalInformation(body)).toBe(`${heading}\nMatomas tekstas.`)
  })

  it('leaves pages without a technical section unchanged', () => {
    const body = '# Pradžia\n\n## Veiksmai\nMatomas tekstas.\n'

    expect(omitTechnicalInformation(body)).toBe(body)
  })

  it.each(['```', '~~~', '````'])('ignores heading-like text inside %s code fences', fence => {
    const body = [fence, '## Techninė informacija', fence, '## Techninė informacija', fence, '## Veiksmai', fence, 'Paslėptos detalės.', '## Rezultatas', 'Matomas tekstas.'].join('\n')

    expect(omitTechnicalInformation(body)).toBe([fence, '## Techninė informacija', fence, '## Rezultatas', 'Matomas tekstas.'].join('\n'))
  })

  it('does not close a fence on a different delimiter or a shorter fence', () => {
    const body = ['````markdown', '```', '~~~', '## Techninė informacija', '````', 'Matomas tekstas.'].join('\n')

    expect(omitTechnicalInformation(body)).toBe(body)
  })

  it('keeps technical content and test metadata available to the web and other PDF consumers', () => {
    const source = '---\ntests:\n  - tests/Feature/ReservationTest.php\nlast_reviewed: 2026-09-30\ndoc_status: reviewed\n---\n# Rezervacijos\n\n## Techninė informacija {#technine-informacija}\n`ReservationPolicy`'
    const { frontmatter, body } = splitFrontmatter(source)
    const options = { prefix: 'p-rezervacijos', file: 'rezervacijos.md', pagesInPdf: new Map<string, string>(), missingScreenshots: [] }

    expect(frontmatter).toEqual({ tests: ['tests/Feature/ReservationTest.php'], reviewed: '2026-09-30', status: 'reviewed' })
    expect(toCmarkerMarkdown(body, options)).toContain('ReservationPolicy')
    expect(toCmarkerMarkdown(omitTechnicalInformation(body), options)).not.toContain('ReservationPolicy')
    expect(body).toContain('## Techninė informacija {#technine-informacija}')
  })
})

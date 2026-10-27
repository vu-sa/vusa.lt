import { changelogEntryPattern } from '../.vitepress/changelogEntries.ts'

/** The entry marked `{#vN}` that launched major vN; later entries stay in the web changelog. */
export function selectMajorRelease(markdown: string, major: string): {
  version: string
  title: string
  date: string
  body: string
} {
  const entries = [...markdown.matchAll(changelogEntryPattern)]
  const index = entries.findIndex(entry => entry[3] === major)
  if (index === -1) {
    throw new Error(`No {#${major}} release entry found; later entries cannot replace the major release PDF.`)
  }
  const entry = entries[index]!

  return {
    version: major,
    title: entry[2]!,
    date: entry[1]!,
    body: markdown.slice(entry.index, entries[index + 1]?.index).trim(),
  }
}

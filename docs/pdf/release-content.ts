export function selectMajorRelease(markdown: string, major: string): {
  version: string
  title: string
  date: string
  body: string
} {
  const entries = [...markdown.matchAll(/^## (v\d+\.\d+) — (.+?) \((\d{4}-\d{2}-\d{2})\).*$/gm)]
  const index = entries.findIndex(entry => entry[1] === `${major}.0`)
  if (index === -1) {
    throw new Error(`No ${major}.0 release entry found; minor releases cannot replace the major release PDF.`)
  }
  const entry = entries[index]!

  return {
    version: entry[1]!,
    title: entry[2]!,
    date: entry[3]!,
    body: markdown.slice(entry.index, entries[index + 1]?.index).trim(),
  }
}

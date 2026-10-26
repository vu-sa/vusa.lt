/** `## 2026-10-26 — Title`; the entry that launched major vN also carries `{#vN}`. */
export const changelogEntryPattern = /^## (\d{4}-\d{2}-\d{2}) — (.+?)(?:\s*\{#([\w-]+)\})?\s*$/gm

export interface ChangelogMajor {
  major: string
  since: string
}

/** Majors newest first, each dated by its `{#vN}` launch entry. */
export function changelogMajors(files: Record<string, string>): ChangelogMajor[] {
  return Object.entries(files)
    .flatMap(([major, markdown]) => {
      const launch = [...markdown.matchAll(changelogEntryPattern)].find(entry => entry[3] === major)

      return launch ? [{ major, since: launch[1]! }] : []
    })
    .sort((a, b) => b.since.localeCompare(a.since))
}

export function majorForDate(majors: ChangelogMajor[], date: string): string | undefined {
  return majors.find(major => major.since <= date)?.major
}

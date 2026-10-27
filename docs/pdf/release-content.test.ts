import assert from 'node:assert/strict'
import test from 'node:test'
import fs from 'node:fs'

import { selectMajorRelease } from './release-content.ts'

test('a major PDF excludes newer and older entries and retains its own metadata', () => {
  const release = selectMajorRelease(`# Changelog
## 2026-11-01 — Newer change
Newer feature
## 2026-10-02 — Major release {#v3}
### Main feature
Major content
## 2026-09-30 — Older change
Older content`, 'v3')
  assert.deepEqual(release, {
    version: 'v3', title: 'Major release', date: '2026-10-02',
    body: '## 2026-10-02 — Major release {#v3}\n### Main feature\nMajor content',
  })
})

test('a missing major release fails instead of silently publishing a later entry as its PDF', () => {
  assert.throws(() => selectMajorRelease('## 2026-10-16 — Change\nContent', 'v3'), /No \{#v3\} release entry/)
})

test('the current v3 PDF contains the v3 launch and excludes later entries', () => {
  const changelog = fs.readFileSync(new URL('../changelog/v3.md', import.meta.url), 'utf8')
  const release = selectMajorRelease(changelog, 'v3')
  assert.equal(release.date, '2026-10-02')
  assert.match(release.body, /Atnaujinta Mano VU SA/)
  assert.doesNotMatch(release.body, /^## 2026-10-(?!02)/m)
})

import assert from 'node:assert/strict'
import test from 'node:test'
import fs from 'node:fs'

import { selectMajorRelease } from './release-content.ts'

test('a major PDF excludes newer and older minor entries and retains its own metadata', () => {
  const release = selectMajorRelease(`# Changelog
## v3.2 — Newer minor (2026-11-01) {#v3-2}
Newer feature
## v3.0 — Major release (2026-10-02) {#v3-0}
### Main feature
Major content
## v3.1 — Another minor (2026-10-16) {#v3-1}
Minor content`, 'v3')
  assert.deepEqual(release, {
    version: 'v3.0', title: 'Major release', date: '2026-10-02',
    body: '## v3.0 — Major release (2026-10-02) {#v3-0}\n### Main feature\nMajor content',
  })
})

test('a missing major release fails instead of silently publishing a minor release as its PDF', () => {
  assert.throws(() => selectMajorRelease('## v3.1 — Minor (2026-10-16)\nContent', 'v3'), /No v3\.0 release entry/)
})

test('the current v3 PDF contains v3.0 and excludes the v3.1 mailing list release', () => {
  const changelog = fs.readFileSync(new URL('../changelog/v3.md', import.meta.url), 'utf8')
  const release = selectMajorRelease(changelog, 'v3')
  assert.equal(release.version, 'v3.0')
  assert.match(release.body, /Atnaujinta Mano VU SA/)
  assert.doesNotMatch(release.body, /v3\.1|v3-1|v3-mailing-lists/)
})

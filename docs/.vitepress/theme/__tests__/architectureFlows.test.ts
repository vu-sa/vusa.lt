import fs from 'node:fs'
import path from 'node:path'
import { describe, expect, it } from 'vitest'
import { flows } from '../architecture/flows'

const repoRoot = path.resolve(__dirname, '../../../..')

describe.each(Object.entries(flows))('%s diagram', (_name, flow) => {
  it('points every node at a file that still exists', () => {
    const missing = flow.nodes
      .filter(node => node.file && !fs.existsSync(path.join(repoRoot, node.file)))
      .map(node => `${node.id}: ${node.file}`)

    expect(missing).toEqual([])
  })

  it('connects only nodes that exist', () => {
    const ids = new Set(flow.nodes.map(node => node.id))
    const dangling = flow.edges.filter(edge => !ids.has(edge.source) || !ids.has(edge.target))

    expect(ids.size).toBe(flow.nodes.length)
    expect(dangling).toEqual([])
  })

  it('has both languages for every label', () => {
    const untranslated = [
      ...flow.nodes.flatMap(node => [node.label, node.detail]),
      ...flow.edges.flatMap(edge => (edge.label ? [edge.label] : [])),
    ].filter(text => !text.lt || !text.en)

    expect(untranslated).toEqual([])
  })
})

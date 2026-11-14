import { describe, expect, it } from 'vitest';

import { findGraph, graphRegistry } from '../registry';
import { toFlow } from '../toFlow';
import type { GraphDef, Localized } from '../types';

const localized = (value: Localized | undefined) => value === undefined || (value.lt.trim() !== '' && typeof value.en === 'string');

describe('structure graph data', () => {
  it.each(graphRegistry.map(entry => [entry.key, entry]))('%s is consistent and has both languages', async (_key, entry) => {
    const graph: GraphDef = await entry.load();
    const ids = graph.nodes.map(node => node.id);

    expect(new Set(ids).size).toBe(ids.length);
    expect(new Set(graph.edges.map(edge => edge.id)).size).toBe(graph.edges.length);
    for (const node of graph.nodes) {
      // A group may be an unlabelled frame.
      expect((node.kind === 'group' && node.label.lt === '') || localized(node.label), node.id).toBe(true);
      expect(localized(node.description), node.id).toBe(true);
      expect(node.url === undefined || node.url.startsWith('http'), node.id).toBe(true);
      if (node.parentNode) expect(graph.nodes.find(parent => parent.id === node.parentNode)?.kind, node.id).toBe('group');
    }
    for (const edge of graph.edges) {
      expect(ids, edge.id).toContain(edge.source);
      expect(ids, edge.id).toContain(edge.target);
      expect(localized(edge.label), edge.id).toBe(true);
    }
  });

  it('reads blocks saved with the old preset name', () => {
    expect(findGraph('VusaStructure')?.key).toBe('vusa-structure');
    expect(findGraph('nope')).toBeUndefined();
  });
});

describe('toFlow', () => {
  const graph: GraphDef = {
    nodes: [
      { id: 'a', kind: 'council', label: { lt: 'Taryba', en: 'Council' }, position: { x: 0, y: 0 }, positionEn: { x: 5, y: 0 } },
      { id: 'b', kind: 'group', label: { lt: 'Grupė', en: 'Group' }, position: { x: 0, y: 50 }, width: 200, height: 100 },
    ],
    edges: [{ id: 'a-b', source: 'a', target: 'b', label: { lt: 'Renka', en: 'Elects' }, variant: 'dashed' }],
  };

  it('picks the language, styles by kind and marks dashed edges', () => {
    const { nodes, edges } = toFlow(graph, 'en');

    expect(nodes[0]).toMatchObject({ type: 'multiple-handle', position: { x: 5, y: 0 }, data: { label: 'Council', nodeClass: expect.stringContaining('bg-vusa-yellow') } });
    expect(nodes[1]).toMatchObject({ type: 'parent', style: { width: '200px', height: '100px' } });
    expect(edges[0]).toMatchObject({ label: 'Elects', type: 'smoothstep', class: 'structure-edge--dashed' });
    expect(toFlow(graph, 'lt').nodes[0]!.position).toEqual({ x: 0, y: 0 });
  });

  it('highlights the article being read and links the other nodes to theirs', () => {
    const articles = { a: { title: 'Taryba', url: '/a' }, b: { title: 'Grupė', url: '/b' } };
    const [a, b] = toFlow(graph, 'lt', { highlight: 'a', articles }).nodes;

    expect(a!.data).toMatchObject({ highlighted: true, article: undefined });
    expect(b!.data).toMatchObject({ highlighted: false, article: { url: '/b' } });
  });
});

import { describe, expect, it } from 'vitest';

import { focusOn } from '../focus';

const nodes = [
  { id: 'seimas' },
  { id: 'komitetas', parentNode: 'seimas' },
  { id: 'kiti', parentNode: 'seimas' },
  { id: 'skvc' },
  { id: 'skvc-taryba', parentNode: 'skvc' },
  { id: 'vsf' },
  { id: 'lss' },
];
const edges = [
  { id: 'komitetas-taryba', source: 'komitetas', target: 'skvc-taryba' },
  { id: 'lss-vsf', source: 'lss', target: 'vsf' },
  { id: 'kiti-vsf', source: 'kiti', target: 'vsf' },
];

describe('focusOn', () => {
  it('keeps a node, its ties and the groups around both', () => {
    const focus = focusOn('komitetas', nodes, edges);

    expect([...focus.nodes].sort()).toEqual(['komitetas', 'seimas', 'skvc', 'skvc-taryba']);
    expect([...focus.edges]).toEqual(['komitetas-taryba']);
  });

  it('keeps a group\'s members and everything they are tied to', () => {
    const focus = focusOn('seimas', nodes, edges);

    expect([...focus.nodes].sort()).toEqual(['kiti', 'komitetas', 'seimas', 'skvc', 'skvc-taryba', 'vsf']);
    expect(focus.edges.has('lss-vsf')).toBe(false);
  });

  it('keeps every member of a group a line leads to', () => {
    const focus = focusOn('lss', nodes, [...edges, { id: 'lss-skvc', source: 'lss', target: 'skvc' }]);

    expect([...focus.nodes].sort()).toEqual(['lss', 'skvc', 'skvc-taryba', 'vsf']);
  });
});

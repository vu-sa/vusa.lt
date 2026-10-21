import { describe, expect, it } from 'vitest';

import { flattenTypeTree, typePath, typeWithDescendantIds } from '../typeTree';

const types = [
  { id: 6, title: 'Koordinatorius', parent_id: null },
  { id: 4, title: 'Pirmininkas', parent_id: 6 },
  { id: 9, title: 'Administratorius', parent_id: 6 },
  { id: 10, title: 'Atstovas', parent_id: null },
  { id: 11, title: 'Senato narys', parent_id: 99 },
];

describe('flattenTypeTree', () => {
  it('lists each parent before its children, siblings by title, and a type with a missing parent as a root', () => {
    const nodes = flattenTypeTree(types, type => type.title);

    expect(nodes.map(node => [typePath(node), node.depth])).toEqual([
      ['Atstovas', 0],
      ['Koordinatorius', 0],
      ['Koordinatorius › Administratorius', 1],
      ['Koordinatorius › Pirmininkas', 1],
      ['Senato narys', 0],
    ]);
  });

  it('survives a parent cycle', () => {
    const cyclic = [{ id: 1, title: 'A', parent_id: 2 }, { id: 2, title: 'B', parent_id: 1 }];

    expect(flattenTypeTree(cyclic, type => type.title)).toEqual([]);
  });
});

describe('typeWithDescendantIds', () => {
  it('collects the type and every descendant', () => {
    expect([...typeWithDescendantIds(types, 6)].sort()).toEqual(['4', '6', '9']);
  });
});

export type TypeId = number | string;

export interface TreeType {
  id: TypeId;
  parent_id?: TypeId | null;
}

export interface TypeTreeNode<T extends TreeType> {
  item: T;
  label: string;
  depth: number;
  /** Ancestor labels, root first. */
  ancestors: string[];
}

const key = (id: TypeId | null | undefined) => (id === null || id === undefined ? null : String(id));

/**
 * Types in tree order: each parent followed by its children, siblings by label. A type whose
 * parent is not in the list (filtered out, trashed) becomes a root.
 */
export function flattenTypeTree<T extends TreeType>(types: T[], labelOf: (type: T) => string): TypeTreeNode<T>[] {
  const ids = new Set(types.map(type => key(type.id)));
  const children = new Map<string | null, T[]>();

  for (const type of types) {
    const parent = key(type.parent_id);
    const bucket = parent !== null && ids.has(parent) ? parent : null;
    children.set(bucket, [...(children.get(bucket) ?? []), type]);
  }

  const result: TypeTreeNode<T>[] = [];
  const seen = new Set<string>();

  const walk = (parent: string | null, depth: number, ancestors: string[]) => {
    const siblings = [...(children.get(parent) ?? [])].sort((a, b) => labelOf(a).localeCompare(labelOf(b), 'lt'));

    for (const type of siblings) {
      const id = key(type.id)!;

      if (seen.has(id)) {
        continue;
      }

      seen.add(id);
      const label = labelOf(type);
      result.push({ item: type, label, depth, ancestors });
      walk(id, depth + 1, [...ancestors, label]);
    }
  };

  walk(null, 0, []);

  return result;
}

/**
 * The type and every descendant, e.g. what a parent picker must not offer.
 */
export function typeWithDescendantIds<T extends TreeType>(types: T[], id: TypeId | null | undefined): Set<string> {
  const result = new Set<string>();
  const start = key(id);

  if (start === null) {
    return result;
  }

  const frontier = [start];

  while (frontier.length) {
    const current = frontier.pop()!;

    if (result.has(current)) {
      continue;
    }

    result.add(current);
    frontier.push(...types.filter(type => key(type.parent_id) === current).map(type => key(type.id)!));
  }

  return result;
}

export const typePath = (node: TypeTreeNode<TreeType>) => [...node.ancestors, node.label].join(' › ');

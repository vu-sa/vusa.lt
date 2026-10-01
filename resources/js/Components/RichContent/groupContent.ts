import { endsSectionWrapping } from './bandLayout';

export interface GroupablePart { type: string; options?: Record<string, unknown> | null }
export type ContentGroup<T> = { kind: 'block'; element: T } | { kind: 'section'; element: T; children: T[] };

export function groupContent<T extends GroupablePart>(parts: T[]): ContentGroup<T>[] {
  const groups: ContentGroup<T>[] = [];
  let active: Extract<ContentGroup<T>, { kind: 'section' }> | null = null;
  for (const element of parts) {
    if (element.type === 'section') {
      const group: Extract<ContentGroup<T>, { kind: 'section' }> = { kind: 'section', element, children: [] };
      groups.push(group);
      active = element.options?.wraps === 'none' ? null : group;
    } else {
      if (active && endsSectionWrapping(element)) active = null;
      if (active) active.children.push(element);
      else groups.push({ kind: 'block', element });
    }
  }
  return groups;
}

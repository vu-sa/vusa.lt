import { computed, type MaybeRefOrGetter, toValue } from 'vue';

import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { flattenTypeTree, type TreeType, type TypeId, typeWithDescendantIds } from '@/Utils/typeTree';

/**
 * Tree-ordered nodes for a type picker. `exclude` drops the listed types with their subtrees (a
 * type cannot become its own descendant's child); `omit` hides single types but keeps their
 * children under them.
 */
export function useTypeTreeNodes<T extends TreeType>(
  types: MaybeRefOrGetter<T[]>,
  labelField: MaybeRefOrGetter<string>,
  exclude?: MaybeRefOrGetter<TypeId[] | undefined>,
  omit?: MaybeRefOrGetter<TypeId[] | undefined>,
) {
  return computed(() => {
    const all = toValue(types);
    const excluded = new Set((toValue(exclude) ?? []).flatMap(id => [...typeWithDescendantIds(all, id)]));
    const field = toValue(labelField);

    const omitted = new Set((toValue(omit) ?? []).map(String));

    return flattenTypeTree(
      all.filter(type => !excluded.has(String(type.id))),
      type => getTranslatedValue((type as Record<string, unknown>)[field]) || String(type.id),
    ).filter(node => !omitted.has(String(node.item.id)));
  });
}

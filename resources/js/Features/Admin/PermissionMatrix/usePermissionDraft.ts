import { computed, ref, watch, type Ref } from 'vue';

import type { PermissionAbility, PermissionScope } from './types';

/**
 * The permission set being edited. A cell is one resource ability; choosing a scope replaces every
 * scope the role holds for it, while untouched cells keep their exact names, so a role holding two
 * scopes for one ability keeps both until someone edits that cell.
 */
export function usePermissionDraft(saved: Ref<string[]>) {
  const draft = ref(new Set(saved.value));

  watch(saved, (names) => {
    draft.value = new Set(names);
  });

  const scopesOf = (resource: string, ability: PermissionAbility): PermissionScope[] => {
    const prefix = `${resource}.${ability}.`;

    return [...draft.value]
      .filter(name => name.startsWith(prefix))
      .map(name => name.slice(prefix.length) as PermissionScope);
  };

  /** The widest scope held, which is what the cell shows. */
  function scopeOf(resource: string, ability: PermissionAbility): PermissionScope | null {
    const held = scopesOf(resource, ability);

    return (['*', 'padalinys', 'own'] as PermissionScope[]).find(scope => held.includes(scope)) ?? null;
  }

  function setScope(resource: string, ability: PermissionAbility, scope: PermissionScope | null): void {
    const next = new Set([...draft.value].filter(name => !name.startsWith(`${resource}.${ability}.`)));

    if (scope) {
      next.add(`${resource}.${ability}.${scope}`);
    }

    draft.value = next;
  }

  const added = computed(() => [...draft.value].filter(name => !saved.value.includes(name)).sort());
  const removed = computed(() => saved.value.filter(name => !draft.value.has(name)).sort());
  const isDirty = computed(() => added.value.length > 0 || removed.value.length > 0);

  function reset(): void {
    draft.value = new Set(saved.value);
  }

  return {
    names: computed(() => [...draft.value].sort()),
    scopeOf,
    setScope,
    added,
    removed,
    isDirty,
    reset,
  };
}

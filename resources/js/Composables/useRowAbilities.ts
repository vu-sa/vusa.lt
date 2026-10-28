import { onScopeDispose, reactive, ref, watch } from 'vue';

import { useApi } from '@/Composables/useApi';

type RowId = string | number;
type Ability = 'update' | 'delete';
type Abilities = Record<Ability, boolean>;
export type RowAbilityType = 'institutions' | 'meetings' | 'agenda_items' | 'resources' | 'documents'
  | 'users' | 'responsibilities' | 'mailingLists' | 'supportRequests';

export function useRowAbilities(type: RowAbilityType, visibleIds: () => RowId[]) {
  const cache = reactive(new Map<string, Abilities>());
  const versions = new Map<string, number>();
  const attempted = new Map<string, number>();
  const revision = ref(0);
  const url = ref('');
  const api = useApi<Record<string, Abilities>>(url, { immediate: false, refetch: false });
  let running = false;
  let disposed = false;

  async function load(): Promise<void> {
    if (running || disposed || typeof window === 'undefined') return;
    running = true;
    try {
      while (!disposed) {
        const ids = [...new Set(visibleIds().map(String))]
          .filter(id => attempted.get(id) !== (versions.get(id) ?? 0)).slice(0, 100);
        if (ids.length === 0) break;
        const batchVersions = ids.map(id => versions.get(id) ?? 0);
        ids.forEach((id, index) => attempted.set(id, batchVersions[index]));
        url.value = route('api.v1.admin.recordAbilities', { type, ids });
        try {
          await api.execute();
        }
        catch {
          continue;
        }
        if (disposed || !api.isSuccess.value || !api.data.value) continue;
        ids.forEach((id, index) => {
          if ((versions.get(id) ?? 0) === batchVersions[index]) {
            cache.set(id, api.data.value?.[id] ?? { update: false, delete: false });
          }
        });
      }
    }
    finally {
      running = false;
    }
  }

  watch([() => visibleIds().map(String), revision], () => { void load(); }, { immediate: true });

  function invalidate(ids?: RowId[]): void {
    const targets = ids?.map(String) ?? [...new Set([...attempted.keys(), ...cache.keys()])];
    targets.forEach(id => {
      versions.set(id, (versions.get(id) ?? 0) + 1);
      cache.delete(id);
    });
    revision.value++;
  }

  onScopeDispose(() => {
    disposed = true;
    api.abort();
  });

  return { can: (id: RowId, ability: Ability): boolean => cache.get(String(id))?.[ability] === true, invalidate };
}

/**
 * useActionWindowData — the personalised context the action window's choice
 * screens read.
 *
 * Fetched on demand rather than shared through Inertia props: the globally
 * shared `current_duties` carries only an institution's id and name, and
 * enriching it would cost every request in the app for something only this
 * window needs.
 *
 * The result is cached at module scope so walking between the institution
 * picker and the meeting picker does not refetch.
 */

import { ref, computed, watch } from 'vue';

import { useApi } from '@/Composables/useApi';
import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

export interface ActionWindowInstitution {
  id: string;
  name: string;
  tenant_shortname: string | null;
  /** Only VU SA's own bodies may be announced in the public calendar. */
  is_internal: boolean;
  /** When this body usually meets, from its own history. Null when it has never met. */
  meeting_pattern: { weekday: number; time: string } | null;
  activity_status: InstitutionActivityStatus;
}

export interface ActionWindowMeeting {
  id: string;
  title: string;
  start_time: string;
  institution_id: string;
  institution_name: string;
  completion_status: 'no_items' | 'incomplete';
}

/**
 * Whether the caller may file a meeting under a body they hold no duty in, and the
 * tenants that wider search is scoped to. An empty `tenant_ids` with `enabled` means
 * all-scope: no tenant filter at all.
 */
export interface ActionWindowInstitutionSearch {
  enabled: boolean;
  tenant_ids: number[];
}

export interface ActionWindowContextData {
  institutions: ActionWindowInstitution[];
  meetingsNeedingAttention: ActionWindowMeeting[];
  institutionSearch: ActionWindowInstitutionSearch;
}

const cached = ref<ActionWindowContextData | null>(null);

export function useActionWindowData() {
  // Never immediate: a screen's onMounted is the only trigger. Firing here as well
  // starts a second request that aborts the first, and the aborted one surfaces as a
  // "No response from server" toast even though the data arrived.
  const { data, isFetching, error, execute } = useApi<ActionWindowContextData>(
    route('api.v1.admin.actionWindow.context'),
    { immediate: false },
  );

  const load = async () => {
    if (cached.value) {
      return;
    }

    await execute();
  };

  watch(data, (loaded) => {
    if (loaded) {
      cached.value = loaded;
    }
  }, { immediate: true });

  const institutions = computed(() => cached.value?.institutions ?? []);
  const meetings = computed(() => cached.value?.meetingsNeedingAttention ?? []);
  const institutionSearch = computed<ActionWindowInstitutionSearch>(
    () => cached.value?.institutionSearch ?? { enabled: false, tenant_ids: [] },
  );

  return {
    institutions,
    meetings,
    institutionSearch,
    isLoading: computed(() => !cached.value && isFetching.value),
    error,
    load,
  };
}

/**
 * Drop the cache so the next fetch re-reads the server. Called both after a create
 * (meeting/check-in) and on every `useActionWindow().open()`, since meetings can be
 * completed from pages the window never touches.
 */
export function invalidateActionWindowData() {
  cached.value = null;
}

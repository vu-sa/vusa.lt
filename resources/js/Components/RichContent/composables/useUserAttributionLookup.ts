import { computed, ref, watch } from 'vue';

import { useApi } from '@/Composables/useApi';

export interface UserAttributionResponse {
  name: string;
  photoUrl: string | null;
  attributions: string[];
}

/**
 * Fetches `/users/{user}/attributions` whenever `userId` is set — shared between
 * `PersonQuoteEditor.vue` (the regular form) and `PersonQuoteDisplay.vue` (the
 * full-screen editor's person popover), which both need the same photo/attribution
 * suggestions after a person is (re)picked. Only wires the fetch; applying the
 * response to `snapshot` stays with the caller since the two surfaces mutate it
 * differently (a `defineModel` object vs. an immutable `update:element` emit).
 */
export function useUserAttributionLookup() {
  const userId = ref<number | null>(null);
  const url = computed(() => (userId.value ? route('api.v1.admin.users.attributions', userId.value) : ''));

  const { data, execute } = useApi<UserAttributionResponse>(url, {
    immediate: false,
    showErrorToast: false,
  });

  watch(userId, async (id) => {
    if (!id) return;
    await execute();
  }, { flush: 'post' });

  return { userId, data };
}

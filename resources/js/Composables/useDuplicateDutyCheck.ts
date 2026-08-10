import { computed, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

import { useApi } from '@/Composables/useApi';
import type { DutySimilarityMatches } from '@/Components/AdminForms/DuplicateDutyWarning.vue';

const EMPTY_MATCHES: DutySimilarityMatches = {
  same_institution: [],
  other_institution: [],
  other_institution_count: 0,
};

/**
 * Looks for an existing duty that looks like the one being created or renamed to.
 *
 * Mirrors useDuplicateUserCheck.ts exactly — same debounce, same "gate on url so
 * clearing the field clears the last result" trick. See DuplicateDutyWarning.vue
 * for why this exists: the dominant duplicate-duty cause is admins not knowing
 * duty names are inflected automatically per holder.
 *
 * Advisory only: the caller shows the matches, never blocks.
 */
export function useDuplicateDutyCheck(
  name: () => string,
  institutionId: () => string | null | undefined,
  excludeDutyId: () => string | null | undefined = () => null,
) {
  const url = ref('');

  const { data, isFetching, execute } = useApi<DutySimilarityMatches>(url, {
    immediate: false,
    showErrorToast: false,
  });

  const matches = computed<DutySimilarityMatches>(() => (url.value ? data.value ?? EMPTY_MATCHES : EMPTY_MATCHES));

  const run = useDebounceFn(() => {
    const currentName = (name() ?? '').trim();

    if (currentName.length < 3) {
      url.value = '';
      return;
    }

    const params = new URLSearchParams({ name: currentName });
    const currentInstitutionId = institutionId();
    const currentExcludeDutyId = excludeDutyId();
    if (currentInstitutionId) params.set('institution_id', currentInstitutionId);
    if (currentExcludeDutyId) params.set('exclude_id', currentExcludeDutyId);

    url.value = `${route('api.v1.admin.duties.similar')}?${params.toString()}`;
    execute();
  }, 500);

  watch([() => name(), () => institutionId()], run);

  return { matches, isChecking: isFetching, check: run };
}

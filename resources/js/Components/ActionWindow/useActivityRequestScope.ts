import { computed } from 'vue';

import { useActionWindow, type ActionWindowInstitutionRef } from '@/Composables/useActionWindow';

export function useActivityRequestScope() {
  const { draft } = useActionWindow();
  const scopeKey = computed(() => JSON.stringify(draft.activityRequest.filters));
  const matchesScope = (institution: ActionWindowInstitutionRef) => {
    const { tenantIds, governanceScope } = draft.activityRequest.filters;
    return (tenantIds.length === 0 || tenantIds.includes(String(institution.tenant_id)))
      && (!governanceScope || institution.governance_scope === governanceScope);
  };

  return { scopeKey, matchesScope };
}

<template>
  <div class="grid gap-2 sm:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]" data-slot="activity-request-filters">
    <MultiSelect
      :aria-label="$t('Padaliniai')"
      :model-value="selectedTenants"
      :options="tenants"
      :placeholder="$t('activity_requests.all_tenants')"
      :empty-text="$t('Nieko nerasta')"
      :max-visible-tags="2"
      @update:model-value="setTenants"
    />
    <NativeSelect
      :aria-label="$t('forms.fields.governance_scope')"
      :model-value="draft.activityRequest.filters.governanceScope ?? ''"
      :options="scopeOptions"
      select-class="truncate"
      @update:model-value="setScope"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { useActionWindow, type ActionWindowInstitutionRef } from '@/Composables/useActionWindow';
import { InstitutionScope } from '@/Types/enums';
import { MultiSelect } from '@/Components/ui/multi-select';
import { NativeSelect } from '@/Components/ui/native-select';

const props = defineProps<{ institutions: ActionWindowInstitutionRef[] }>();
const { draft, updateActivityRequest } = useActionWindow();
const page = usePage();
const tenants = computed(() => {
  const options = new Map(props.institutions
    .filter(item => item.tenant_id != null)
    .map(item => [String(item.tenant_id), { id: String(item.tenant_id), label: item.tenant_shortname ?? String(item.tenant_id) }]));
  for (const id of draft.activityRequest.filters.tenantIds) {
    if (!options.has(id)) {
      const tenant = page.props.tenants?.find(item => String(item.id) === id);
      options.set(id, { id, label: tenant?.shortname ?? id });
    }
  }
  return [...options.values()];
});
const selectedTenants = computed(() => tenants.value.filter(item => draft.activityRequest.filters.tenantIds.includes(item.id)));
const setTenants = (items: { id: string }[]) => updateActivityRequest({
  filters: { ...draft.activityRequest.filters, tenantIds: items.map(item => item.id) },
});
const scopeOptions = computed(() => [
  { value: '', label: $t('activity_requests.all_scopes') },
  ...Object.values(InstitutionScope).map(value => ({ value, label: $t(`forms.options.governance_scope_${value}`) })),
]);
const setScope = (value: string | number | null | undefined) => updateActivityRequest({
  filters: { ...draft.activityRequest.filters, governanceScope: Object.values(InstitutionScope).find(scope => scope === value) ?? null },
});
</script>

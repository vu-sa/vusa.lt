<template>
  <div class="space-y-3" data-slot="activity-request-filters">
    <div class="space-y-1">
      <Label id="activity-request-tenants-label">{{ $t('Padaliniai') }}</Label>
      <MultiSelect
        aria-labelledby="activity-request-tenants-label"
        :model-value="selectedTenants"
        :options="tenants"
        :placeholder="$t('activity_requests.all_tenants')"
        :empty-text="$t('Nieko nerasta')"
        @update:model-value="setTenants"
      />
      <p class="text-xs text-muted-foreground">
        {{ $t('activity_requests.filters_hint') }}
      </p>
    </div>
    <div class="space-y-1">
      <Label for="activity-request-scope">{{ $t('forms.fields.governance_scope') }}</Label>
      <NativeSelect
        id="activity-request-scope"
        :model-value="draft.activityRequest.filters.governanceScope ?? ''"
        :options="scopeOptions"
        @update:model-value="setScope"
      />
    </div>
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
import { Label } from '@/Components/ui/label';

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

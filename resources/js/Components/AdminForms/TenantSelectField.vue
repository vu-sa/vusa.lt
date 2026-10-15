<template>
  <FormFieldWrapper
    :id
    :label="$t('forms.fields.tenant')"
    required
    :error
    :valid
    :invalid
  >
    <NativeSelect
      :id
      v-model="model"
      :options="tenantOptions"
      :placeholder="$t('forms.placeholders.select_tenant')"
      :disabled
      :error="Boolean(error || invalid)"
    />
  </FormFieldWrapper>
</template>

<script lang="ts">
/** The main tenant when the actor may use it (e.g. a super admin), otherwise their first. */
export function pickDefaultTenantId(tenants: Pick<App.Entities.Tenant, 'id' | 'type'>[] = []): number | null {
  return tenants.find(tenant => tenant.type === 'pagrindinis')?.id ?? tenants[0]?.id ?? null;
}
</script>

<script setup lang="ts">
import { computed } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import { NativeSelect } from '@/Components/ui/native-select';

const props = withDefaults(defineProps<{
  tenants: App.Entities.Tenant[];
  id?: string;
  disabled?: boolean;
  error?: string;
  valid?: boolean;
  invalid?: boolean;
}>(), {
  id: 'tenant',
  error: undefined,
});

const model = defineModel<number | null>({ default: null });

const tenantOptions = computed(() =>
  props.tenants.map(tenant => ({
    value: tenant.id,
    label: tenant.shortname,
  })),
);
</script>

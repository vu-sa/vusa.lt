<template>
  <FormFieldWrapper
    :id
    :label="$t('forms.fields.tenant')"
    required
    :error
    :valid
    :invalid
  >
    <Select v-model="tenantIdString" :disabled>
      <SelectTrigger :id :class="['h-11 w-full', fieldSurfaceClass]">
        <SelectValue :placeholder="$t('forms.placeholders.select_tenant')" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
          {{ tenant.shortname }}
        </SelectItem>
      </SelectContent>
    </Select>
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

import { fieldSurfaceClass } from '@/Components/ui/control';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

withDefaults(defineProps<{
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

// shadcn Select only takes string values.
const tenantIdString = computed({
  get: () => (model.value ? String(model.value) : ''),
  set: (value: string) => {
    model.value = value ? Number(value) : null;
  },
});
</script>

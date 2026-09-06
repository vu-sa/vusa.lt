<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('Institucijų tipas') }}</FieldLabel>
        <RCTypeSlugSelect :model-value="options?.typeSlug" @update:model-value="(value) => patchOptions({ typeSlug: value })" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.limit') }}</FieldLabel>
        <NumberField :model-value="options?.limit ?? undefined" :min="1" :max="50" @update:model-value="patchOptions({ limit: $event })" />
      </Field>
    </div>

    <Field>
      <FieldLabel>{{ $t('rich-content.tenant_scope') }}</FieldLabel>
      <ToggleGroup :model-value="tenantScopeMode" type="single" class="justify-start" @update:model-value="onTenantScopeChange">
        <ToggleGroupItem value="all">
          {{ $t('rich-content.tenant_scope_all') }}
        </ToggleGroupItem>
        <ToggleGroupItem value="current">
          {{ $t('rich-content.tenant_scope_current') }}
        </ToggleGroupItem>
        <ToggleGroupItem value="selected">
          {{ $t('rich-content.tenant_scope_selected') }}
        </ToggleGroupItem>
      </ToggleGroup>
      <RCTenantMultiSelect v-if="tenantScopeMode === 'selected'" :model-value="selectedTenantScope"
        class="mt-2" @update:model-value="(value) => patchOptions({ tenantScope: value })" />
    </Field>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCTypeSlugSelect from '../Editor/RCTypeSlugSelect.vue';
import RCTenantMultiSelect from '../Editor/RCTenantMultiSelect.vue';

import type { InstitutionList } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { NumberField } from '@/Components/ui/number-field';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const options = defineModel<InstitutionList['options']>({ required: true });

const tenantScopeMode = computed<'current' | 'all' | 'selected'>(() => {
  const scope = options.value?.tenantScope;

  return Array.isArray(scope) ? 'selected' : scope ?? 'all';
});

const selectedTenantScope = computed<number[]>(() => Array.isArray(options.value?.tenantScope)
  ? options.value.tenantScope
  : []);

function patchOptions(patch: Partial<NonNullable<InstitutionList['options']>>): void {
  options.value = { ...(options.value ?? {}), ...patch };
}

function onTenantScopeChange(value: unknown): void {
  if (value === 'current' || value === 'all') {
    patchOptions({ tenantScope: value });
  }
  else if (value === 'selected') {
    patchOptions({ tenantScope: selectedTenantScope.value });
  }
}
</script>

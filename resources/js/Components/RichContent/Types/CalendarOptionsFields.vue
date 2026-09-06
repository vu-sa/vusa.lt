<template>
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.category_alias') }}</FieldLabel>
        <RCCategoryAliasSelect :model-value="options?.categoryAlias" @update:model-value="(v) => patchOptions({ categoryAlias: v })" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.limit') }}</FieldLabel>
        <NumberField :model-value="options?.limit ?? 3" :min="1" :max="10"
          @update:model-value="patchOptions({ limit: $event })" />
      </Field>
    </div>

    <Field>
      <FieldLabel>{{ $t('rich-content.tenant_scope') }}</FieldLabel>
      <RCTenantMultiSelect :model-value="options?.tenantScope" @update:model-value="(v) => patchOptions({ tenantScope: v })" />
    </Field>
  </div>
</template>

<script setup lang="ts">
/**
 * `calendar`'s fetch-configuration fields — how many events (`limit`), from which
 * category (`categoryAlias`), and which tenants (`tenantScope`). Shared between
 * `CalendarEditor.vue` (the regular side form) and `CalendarBlockToolbar.vue`'s
 * full-screen options popover, so both surfaces offer the same controls in one place
 * rather than scattering them.
 *
 * Reassigns the whole `options` object (never a nested `options.value.field = x`
 * mutation) — see `LinkListOptionsFields.vue`'s identical note: the toolbar's popover
 * only observes changes through defineModel's setter.
 */
import { trans as $t } from 'laravel-vue-i18n';

import type { Calendar } from '@/Types/contentParts';
import RCCategoryAliasSelect from '../Editor/RCCategoryAliasSelect.vue';
import RCTenantMultiSelect from '../Editor/RCTenantMultiSelect.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { NumberField } from '@/Components/ui/number-field';

const options = defineModel<Calendar['options']>({ required: true });

function patchOptions(patch: Partial<NonNullable<Calendar['options']>>): void {
  options.value = { ...(options.value ?? {}), ...patch };
}
</script>

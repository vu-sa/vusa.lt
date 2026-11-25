<template>
  <FormSection
    :title="$t('forms.fields.assignable_tenants')"
    :description="$t('Leisti kitiems padaliniams skirti atstovus į šią pareigybę.')"
    data-testid="assignable-tenants-section"
  >
    <div class="space-y-3">
      <MultiSelect
        v-if="canEditDuty"
        id="assignable-tenants"
        v-model="selectedTenants"
        :options="tenants"
        label-field="shortname"
        value-field="id"
        :placeholder="$t('forms.placeholders.select_tenants')"
      />
      <div v-if="visibleRows.length > 0" class="divide-y divide-border border border-border">
        <div v-for="row in visibleRows" :key="row.tenant_id" class="flex items-center justify-between p-3">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-foreground">{{ shortnameOf(row) }}</span>
            <span
              class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-semibold text-muted-foreground"
              :data-testid="`tenant-occupancy-${row.tenant_id}`"
            >
              {{ occupancyOf(row) }} / {{ row.quota ?? '∞' }}
            </span>
          </div>
          <div v-if="canEditDuty" class="flex items-center gap-2">
            <div class="flex items-center gap-1.5">
              <Label :for="`quota-${row.tenant_id}`" class="text-xs text-muted-foreground">{{ $t('Kvota') }}:</Label>
              <Input
                :id="`quota-${row.tenant_id}`"
                :model-value="row.quota ?? ''"
                type="number"
                min="1"
                class="h-7 w-16 text-center text-xs"
                @update:model-value="(value) => row.quota = value === '' ? null : Number(value)"
              />
            </div>
            <Button type="button" variant="ghost" size="sm" class="u-touch text-destructive" @click="removeRow(row.tenant_id)">
              <Trash2 class="size-3.5" />
            </Button>
          </div>
        </div>
      </div>
      <Button
        v-if="canEditDuty"
        type="button"
        variant="ghost"
        size="sm"
        voice="sentence"
        class="u-touch text-muted-foreground"
        data-testid="assignable-tenants-clear"
        @click="emit('clear')"
      >
        {{ $t('Nebeleisti kitiems padaliniams skirti atstovų') }}
      </Button>
    </div>
  </FormSection>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2 } from '@lucide/vue';

import { FormSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';

import { visibleAssignableTenantRows, type AssignableTenantRow } from './dutyFormRows';

/** Delegated seats: units allowed to name representatives to this duty, each with an optional quota. */
const props = defineProps<{
  tenants: { id: number; shortname: string }[];
  canEditDuty: boolean;
  actingTenantIds: number[];
  /** Members each unit already picked, by tenant id. */
  pickedUsers: Record<number, string[]>;
  exOfficioMembers: { tenant_id?: number | null }[];
}>();

const rows = defineModel<AssignableTenantRow[]>('rows', { required: true });

const emit = defineEmits<{ clear: [] }>();

const selectedTenants = computed({
  get: () => {
    const ids = new Set(rows.value.map(row => row.tenant_id));
    return props.tenants.filter(tenant => ids.has(tenant.id));
  },
  set: (tenants: { id: number; shortname: string }[]) => {
    const quotas = new Map(rows.value.map(row => [row.tenant_id, row.quota]));
    rows.value = tenants.map(tenant => ({ tenant_id: tenant.id, quota: quotas.get(tenant.id) ?? null }));
  },
});

const visibleRows = computed(() => visibleAssignableTenantRows(rows.value, props.canEditDuty, props.actingTenantIds));

const shortnameOf = (row: AssignableTenantRow) => props.tenants.find(tenant => tenant.id === row.tenant_id)?.shortname ?? String(row.tenant_id);

const occupancyOf = (row: AssignableTenantRow) => (props.pickedUsers[row.tenant_id] ?? []).length
  + props.exOfficioMembers.filter(member => member.tenant_id === row.tenant_id).length;

function removeRow(tenantId: number): void {
  rows.value = rows.value.filter(row => row.tenant_id !== tenantId);
}
</script>

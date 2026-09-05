<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button type="button" variant="outline" class="w-full justify-between font-normal">
        <span class="truncate" :class="{ 'text-muted-foreground': isNoneSelected }">{{ triggerLabel }}</span>
        <IFluentChevronDown24Regular class="size-4 shrink-0 opacity-50" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="start" class="w-72">
      <DropdownMenuLabel class="flex items-center justify-between gap-3">
        <span>{{ $t('rich-content.tenant_scope') }}</span>
        <div class="flex gap-1">
          <Button size="xs" variant="ghost" class="h-6 px-2 text-xs" :disabled="isAllSelected" @click.stop="selectAll">
            {{ $t('rich-content.tenant_scope_all') }}
          </Button>
          <Button size="xs" variant="ghost" class="h-6 px-2 text-xs" :disabled="isNoneSelected" @click.stop="selectNone">
            {{ $t('rich-content.tenant_scope_none') }}
          </Button>
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <div class="max-h-64 overflow-y-auto">
        <DropdownMenuCheckboxItem
          v-for="tenant in tenants" :key="tenant.id"
          :model-value="isSelected(tenant.id)"
          @update:model-value="(checked) => toggle(tenant.id, checked)"
          @select.prevent
        >
          {{ tenant.shortname }}
        </DropdownMenuCheckboxItem>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
/**
 * Multi-select of every tenant (padalinys), for `categoryAlias`-adjacent fetch options
 * that scope *public* data (calendar events, …) by tenant. Sourced from the `tenants`
 * prop shared globally via `HandleInertiaRequests` — same "global list, no per-user
 * filtering" rationale as `RCCategoryAliasSelect.vue` — and deliberately **not**
 * authorization-gated the way `TenantScopeSelector.vue` (Pages/Admin/Dashboard) is:
 * that component's `tenants` list is pre-filtered by the caller to what the current
 * user may administer, which is the wrong semantic here — the author is choosing which
 * tenants' already-public events to *display to readers*, not which tenants they
 * personally manage, so every tenant must be offered regardless of who's editing.
 *
 * Model: `'all'` (every tenant — the default) or a specific `number[]`. An empty array
 * is a real, distinct state ("None" clicked) — see `Calendar['options'].tenantScope`'s
 * doc comment for why that must mean zero events, not "no filter".
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const modelValue = defineModel<'all' | number[] | undefined>();

const page = usePage();
const tenants = computed(() => page.props.tenants ?? []);

const isAllSelected = computed(() => modelValue.value === 'all' || modelValue.value === undefined);
const isNoneSelected = computed(() => Array.isArray(modelValue.value) && modelValue.value.length === 0);

function isSelected(tenantId: number): boolean {
  if (isAllSelected.value) return true;

  return Array.isArray(modelValue.value) && modelValue.value.includes(tenantId);
}

function toggle(tenantId: number, checked: boolean): void {
  // Starting from "all", unchecking one tenant means "every tenant except this one" —
  // materialize the full id list rather than trying to represent a negative selection.
  const current = isAllSelected.value ? tenants.value.map(t => t.id) : (modelValue.value as number[] ?? []);
  const next = checked
    ? [...new Set([...current, tenantId])]
    : current.filter(id => id !== tenantId);

  modelValue.value = next;
}

function selectAll(): void {
  modelValue.value = 'all';
}

function selectNone(): void {
  modelValue.value = [];
}

const triggerLabel = computed(() => {
  if (isAllSelected.value) return $t('rich-content.tenant_scope_all');
  if (isNoneSelected.value) return $t('rich-content.tenant_scope_none');

  const selected = modelValue.value as number[];
  if (selected.length === 1) {
    return tenants.value.find(t => t.id === selected[0])?.shortname ?? $t('rich-content.tenant_scope');
  }

  return $t('rich-content.tenant_scope_selected_count', { count: selected.length });
});
</script>

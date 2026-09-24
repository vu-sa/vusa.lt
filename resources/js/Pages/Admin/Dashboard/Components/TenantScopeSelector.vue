<template>
  <component
    :is="compact ? 'div' : Card"
    data-tour="tenant-scope"
    :class="compact ? 'inline-flex' : 'border-primary/20 bg-primary/[0.02]'"
  >
    <component
      :is="compact ? 'div' : CardContent"
      :class="compact ? 'inline-flex' : 'flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between'"
    >
      <div v-if="!compact" class="flex items-start gap-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
          <Building2 class="h-4 w-4" />
        </div>
        <div class="space-y-1">
          <h2 class="font-semibold">
            {{ resolvedTitle }}
          </h2>
          <p class="text-sm text-muted-foreground">
            {{ resolvedDescription }}
          </p>
        </div>
      </div>

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="outline"
            :class="[
              'h-11 w-full justify-between gap-3 border-foreground/40 px-4 font-semibold hover:border-foreground sm:w-auto sm:min-w-72',
            ]"
            data-testid="tenant-scope-trigger"
            @click="$emit('engage')"
          >
            <Building2 class="size-4 shrink-0 text-brand" aria-hidden="true" />
            <span class="truncate">{{ triggerLabel }}</span>
            <span class="ml-auto shrink-0 text-xs font-normal tabular-nums text-muted-foreground">
              {{ selectedTenants.length }}/{{ tenants.length }}
            </span>
            <ChevronDown class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-72">
          <DropdownMenuLabel class="flex items-center justify-between gap-3">
            <span>{{ $t('Padaliniai') }}</span>
            <div class="flex gap-1">
              <Button
                size="xs"
                variant="ghost"
                class="h-6 px-2 text-xs"
                :disabled="selectedTenants.length === tenants.length"
                @click.stop="selectAllTenants"
              >
                {{ $t('Visi') }}
              </Button>
              <Button
                size="xs"
                variant="ghost"
                class="h-6 px-2 text-xs"
                :disabled="selectedTenants.length <= 1"
                :title="$t('visak.tenant_scope.keep_one_hint')"
                @click.stop="keepOneTenant"
              >
                {{ $t('visak.tenant_scope.keep_one') }}
              </Button>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <div class="max-h-64 overflow-y-auto">
            <DropdownMenuCheckboxItem
              v-for="tenant in tenants"
              :key="tenant.id"
              :model-value="selectedTenants.includes(String(tenant.id))"
              :disabled="isFinalSelectedTenant(String(tenant.id))"
              @update:model-value="checked => toggleTenant(String(tenant.id), checked)"
              @select.prevent
            >
              {{ tenant.shortname }}
            </DropdownMenuCheckboxItem>
          </div>
        </DropdownMenuContent>
      </DropdownMenu>
    </component>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, ChevronDown } from 'lucide-vue-next';

import type { AtstovavimasTenant } from '../types';

import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const props = defineProps<{
  tenants: AtstovavimasTenant[];
  selectedTenants: string[];
  title?: string;
  description?: string;
  compact?: boolean;
}>();

const emit = defineEmits<{
  'update:selectedTenants': [tenantIds: string[]];
  'engage': [];
}>();

const triggerLabel = computed(() => {
  if (props.selectedTenants.length === props.tenants.length) {
    return $t('Visi padaliniai');
  }

  if (props.selectedTenants.length === 1) {
    return props.tenants.find(tenant => String(tenant.id) === props.selectedTenants[0])?.shortname
      ?? $t('Padalinys');
  }

  return $t('visak.tenant_scope.selected_count', { count: props.selectedTenants.length });
});

const resolvedTitle = computed(() => props.title ?? $t('visak.tenant_scope.title'));
const resolvedDescription = computed(() => props.description ?? $t('visak.tenant_scope.description'));

function toggleTenant(tenantId: string, checked: boolean): void {
  if (!checked && isFinalSelectedTenant(tenantId)) {
    return;
  }

  const selected = new Set(props.selectedTenants);

  if (checked) {
    selected.add(tenantId);
  }
  else {
    selected.delete(tenantId);
  }

  emit('update:selectedTenants', props.tenants
    .map(tenant => String(tenant.id))
    .filter(id => selected.has(id)));
  emit('engage');
}

function selectAllTenants(): void {
  emit('update:selectedTenants', props.tenants.map(tenant => String(tenant.id)));
  emit('engage');
}

/** Deselect all but the first currently-selected tenant (at least one must stay selected). */
function keepOneTenant(): void {
  const kept = props.selectedTenants[0] ?? props.tenants[0]?.id;
  if (kept === undefined) {
    return;
  }

  emit('update:selectedTenants', [String(kept)]);
  emit('engage');
}

function isFinalSelectedTenant(tenantId: string): boolean {
  return props.selectedTenants.length === 1 && props.selectedTenants.includes(tenantId);
}
</script>

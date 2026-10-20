<template>
  <span v-if="!scopes.length && retired.length" class="inline-flex min-h-8 items-center gap-1 text-xs text-muted-foreground" data-testid="cell-baseline">
    <Lock class="size-3" aria-hidden="true" />
    {{ $t('responsibilities.matrix.everyone') }}
  </span>
  <span v-else-if="!scopes.length" class="inline-flex min-h-8 items-center text-xs text-muted-foreground/60" :aria-label="$t('responsibilities.matrix.not_applicable')">
    –
  </span>
  <span v-else-if="readonly" :class="['inline-flex min-h-8 items-center text-sm', value ? 'font-medium text-foreground' : 'text-muted-foreground/60']">
    {{ value ? scopeLabel(value) : '–' }}
  </span>
  <NativeSelect
    v-else
    :model-value="value ?? ''"
    :options
    size="sm"
    :aria-label="`${resourceLabel(resource.key, resource.model)} · ${abilityLabel(ability)}`"
    :select-class="[value ? 'font-medium text-foreground' : 'text-muted-foreground', changed && 'border-brand']"
    :data-cell="`${resource.key}.${ability}`"
    @update:model-value="emit('update', ($event || null) as PermissionScope | null)"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Lock } from '@lucide/vue';

import { abilityLabel, resourceLabel, scopeLabel } from './permissionLabels';
import type { CatalogResource, PermissionAbility, PermissionScope } from './types';

import { NativeSelect } from '@/Components/ui/native-select';

const props = defineProps<{
  resource: CatalogResource;
  ability: PermissionAbility;
  value: PermissionScope | null;
  changed?: boolean;
  /** Scopes every member already has here. */
  retired: PermissionScope[];
  readonly?: boolean;
}>();

const emit = defineEmits<{ update: [scope: PermissionScope | null] }>();

const scopes = computed(() => props.resource.abilities[props.ability] ?? []);

const options = computed(() => [
  { value: '', label: '–' },
  ...scopes.value.map(scope => ({ value: scope, label: scopeLabel(scope) })),
]);
</script>

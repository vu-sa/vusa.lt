<template>
  <span v-if="!scopes.length && retired.length" class="inline-flex min-h-8 items-center gap-1 text-xs text-muted-foreground" data-testid="cell-baseline">
    <Lock class="size-3" aria-hidden="true" />
    {{ $t('responsibilities.matrix.everyone') }}
  </span>
  <span v-else-if="!scopes.length" class="inline-flex min-h-8 items-center text-xs text-muted-foreground/60" :aria-label="$t('responsibilities.matrix.not_applicable')">
    –
  </span>
  <span v-else-if="readonly" :class="['inline-flex min-h-8 items-center text-sm', value ? 'font-medium text-foreground' : 'text-muted-foreground/60']">
    {{ value ? labelForScope(value) : '–' }}
  </span>
  <div v-else>
    <NativeSelect
      :model-value="value ?? ''"
      :options
      size="sm"
      :aria-label="`${resourceLabel(resource.key, resource.model)} · ${abilityLabel(ability, resource.softDeletes)}`"
      :select-class="[value ? 'font-medium text-foreground' : 'text-muted-foreground', changed && 'border-brand']"
      :data-cell="`${resource.key}.${ability}`"
      @update:model-value="emit('update', ($event || null) as PermissionScope | null)"
    />
    <p v-if="ability === 'delete' && !resource.softDeletes" class="mt-0.5 text-[11px] text-muted-foreground" data-testid="cell-permanent">
      {{ $t('responsibilities.matrix.abilities.delete_permanent_hint') }}
    </p>
  </div>
  <p v-if="description" class="mt-1 text-xs leading-relaxed text-muted-foreground" data-testid="cell-scope-description">
    {{ description }}
  </p>
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
const labelForScope = (scope: PermissionScope) => props.resource.scopeLabels?.[scope] ?? scopeLabel(scope);
const description = computed(() => props.value ? props.resource.scopeDescriptions?.[props.ability]?.[props.value] : undefined);

const options = computed(() => [
  { value: '', label: '–' },
  ...scopes.value.map(scope => ({ value: scope, label: labelForScope(scope) })),
]);
</script>

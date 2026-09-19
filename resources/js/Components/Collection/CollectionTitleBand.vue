<template>
  <header
    class="flex flex-col gap-4 border-b border-border pb-5 sm:flex-row sm:items-end sm:justify-between"
    data-slot="collection-title-band"
  >
    <div class="flex min-w-0 items-start gap-4">
      <span
        v-if="definition"
        class="flex size-12 shrink-0 items-center justify-center bg-[var(--entity-category-surface)] text-[var(--entity-category)]"
        :style="categoryVariables"
        aria-hidden="true"
      >
        <component :is="definition.icon" class="size-6" />
      </span>
      <div class="min-w-0">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
          {{ eyebrow }}
        </p>
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">
          {{ title }}
        </h1>
        <p v-if="lead" class="mt-1 max-w-prose text-sm text-muted-foreground">
          {{ lead }}
        </p>
      </div>
    </div>

    <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup lang="ts">
import type { CSSProperties } from 'vue';
import { computed } from 'vue';

import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const props = defineProps<{
  /** Workspace and section, e.g. "ViSAK · Posėdžiai" — location is always stated (wayfinding rule 3). */
  eyebrow: string;
  title: string;
  lead?: string;
  /** Entity type registry key; draws the identity tile every surface shares. */
  entityType: string;
}>();

const definition = computed(() => getEntityTypeDefinition(props.entityType));
const categoryVariables = computed<CSSProperties>(() => ({
  '--entity-category': `var(--cat-${definition.value?.category ?? 1})`,
  '--entity-category-surface': `var(--cat-${definition.value?.category ?? 1}-surface)`,
}));
</script>

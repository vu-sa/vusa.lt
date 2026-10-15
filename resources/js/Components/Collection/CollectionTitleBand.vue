<template>
  <header class="py-3 md:py-5 lg:py-8" data-slot="collection-title-band">
    <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-brand">
      <component :is="definition.icon" v-if="definition" class="size-4 shrink-0" aria-hidden="true" />
      {{ eyebrow }}
    </p>

    <div class="mt-2 flex flex-col gap-3 sm:mt-3 sm:flex-row sm:items-end sm:justify-between sm:gap-5">
      <div class="min-w-0">
        <h1 class="u-display text-balance text-3xl leading-[0.95] text-foreground sm:text-4xl lg:text-5xl">
          {{ title }}
        </h1>
        <p v-if="lead" class="mt-3 max-w-xl text-pretty leading-relaxed text-muted-foreground">
          {{ lead }}
        </p>
      </div>

      <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const props = defineProps<{
  /** Workspace and section, e.g. "ViSAK · Posėdžiai" — location is always stated (wayfinding rule 3). */
  eyebrow: string;
  title: string;
  lead?: string;
  /** Entity type registry key; its icon marks the eyebrow as this band's identity anchor. */
  entityType: string;
}>();

const definition = computed(() => getEntityTypeDefinition(props.entityType));
</script>

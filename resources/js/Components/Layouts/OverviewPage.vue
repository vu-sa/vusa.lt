<template>
  <div class="flex flex-col gap-8" data-slot="overview-page">
    <Head :title="headTitle ?? title" />

    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-slot="overview-title-band">
      <div class="min-w-0">
        <p v-if="eyebrow" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
          {{ eyebrow }}
        </p>
        <slot name="heading">
          <h1 class="mt-1 text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            {{ title }}
          </h1>
        </slot>
        <p v-if="lead" class="mt-2 max-w-prose text-sm text-muted-foreground">
          {{ lead }}
        </p>
      </div>

      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:shrink-0">
        <slot name="actions" />
      </div>
    </header>

    <slot name="attention" />

    <slot />
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
  eyebrow?: string;
  title: string;
  /** Browser-tab title when it should say more than the h1, e.g. "ViSAK · Apžvalga". */
  headTitle?: string;
  lead?: string;
}>();

defineSlots<{
  /** Replaces the h1, e.g. Pradžia's greeting. `title` still feeds the browser tab. */
  heading: () => unknown;
  actions: () => unknown;
  /** The page's one ink band; the caller renders nothing while there is nothing to attend to. */
  attention: () => unknown;
  default: () => unknown;
}>();
</script>

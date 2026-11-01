<template>
  <header class="py-3 md:py-5 lg:py-8" data-slot="collection-title-band">
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand">
      {{ eyebrow }}
    </p>

    <div class="mt-2 flex flex-col gap-3 sm:mt-3 sm:flex-row sm:items-end sm:justify-between sm:gap-5">
      <div class="min-w-0">
        <h1 class="u-display text-balance text-3xl leading-[0.95] text-foreground sm:text-4xl lg:text-5xl">
          {{ title }}
        </h1>
        <div v-if="lead || $slots.lead" class="mt-3 max-w-xl text-pretty leading-relaxed text-muted-foreground">
          <slot name="lead">
            <p>{{ lead }}</p>
          </slot>
        </div>
      </div>

      <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
defineProps<{
  /** The workspace, e.g. "ViSAK"; the headline already names the section. */
  eyebrow: string;
  title: string;
  lead?: string;
}>();
</script>

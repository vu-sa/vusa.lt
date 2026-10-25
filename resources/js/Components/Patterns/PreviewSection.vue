<template>
  <section class="min-w-0" data-slot="preview-section">
    <h3 class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
      {{ title }}
      <span v-if="count !== undefined" class="tabular-nums">({{ count }})</span>
    </h3>

    <div v-if="loading" class="flex flex-col gap-2" data-slot="preview-section-skeleton">
      <div v-for="i in 3" :key="i" class="h-9 animate-pulse bg-muted/50" />
    </div>
    <p v-else-if="empty" class="text-sm text-muted-foreground">
      {{ emptyMessage }}
    </p>
    <slot v-else />
  </section>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  title: string;
  count?: number;
  /** Shows a skeleton instead of the content (an async section still fetching). */
  loading?: boolean;
  /** Collapses the section to one line of `emptyMessage`. */
  empty?: boolean;
  emptyMessage?: string;
}>(), {
  count: undefined,
  emptyMessage: '',
});
</script>

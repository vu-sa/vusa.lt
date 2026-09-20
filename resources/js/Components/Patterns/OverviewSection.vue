<template>
  <section class="flex flex-col gap-3" data-slot="overview-section">
    <!-- Empty sections collapse to one line (visual budget 7). -->
    <p v-if="empty" class="border-t border-border pt-3 text-sm text-muted-foreground">
      <span class="font-medium text-foreground">{{ title }}</span>
      <span v-if="emptyText"> — {{ emptyText }}</span>
    </p>

    <template v-else>
      <header class="flex items-baseline justify-between gap-4 border-t border-border pt-3">
        <h2 class="text-base font-semibold text-foreground">
          {{ title }}
        </h2>
        <Link v-if="href" :href class="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">
          {{ hrefLabel }}
        </Link>
      </header>
      <slot />
    </template>
  </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
  title: string;
  empty?: boolean;
  emptyText?: string;
  href?: string;
  hrefLabel?: string;
}>();
</script>

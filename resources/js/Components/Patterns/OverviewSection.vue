<template>
  <section class="flex flex-col gap-3" data-slot="overview-section">
    <!-- Empty sections collapse to one line (visual budget 7). -->
    <p v-if="empty" :class="['flex items-center gap-2 text-sm text-muted-foreground', variant === 'home' ? 'border-b border-border pb-3' : 'border-t border-border pt-3']">
      <component :is="icon" v-if="icon" class="size-4 shrink-0 text-brand" aria-hidden="true" />
      <span>
        <span :class="variant === 'home' ? 'font-bold uppercase tracking-[0.18em] text-foreground' : 'font-medium text-foreground'">{{ title }}</span>
        <span v-if="emptyText"> — {{ emptyText }}</span>
      </span>
    </p>

    <template v-else>
      <header :class="['flex items-center justify-between gap-4', variant === 'home' ? 'border-b border-border pb-3' : 'border-t border-border pt-3']">
        <h2 :class="['flex items-center gap-2 text-foreground', variant === 'home' ? 'text-sm font-bold uppercase tracking-[0.18em]' : 'text-base font-semibold']">
          <component :is="icon" v-if="icon" class="size-4 shrink-0 text-brand" aria-hidden="true" />
          {{ title }}
        </h2>
        <Link
          v-if="href"
          :href
          :class="variant === 'home'
            ? 'shrink-0 text-xs font-bold uppercase tracking-wide text-brand hover:text-foreground'
            : 'text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline'"
        >
          {{ hrefLabel }}
        </Link>
      </header>
      <slot />
    </template>
  </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

defineProps<{
  title: string;
  icon?: Component;
  variant?: 'default' | 'home';
  empty?: boolean;
  emptyText?: string;
  href?: string;
  hrefLabel?: string;
}>();
</script>

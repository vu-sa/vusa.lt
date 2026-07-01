<template>
  <div class="flex h-full flex-col overflow-y-auto">
    <!-- Header -->
    <div class="border-b p-5">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
          <div class="mb-3 flex items-center gap-2">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
              <component :is="icon" class="size-4" />
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ kicker }}</span>
              <slot name="badges" />
            </div>
          </div>

          <h2 class="text-lg font-semibold leading-snug tracking-tight text-foreground">
            {{ title }}
          </h2>
          <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">
            {{ subtitle }}
          </p>
        </div>

        <div v-if="$slots.media" class="shrink-0">
          <slot name="media" />
        </div>
      </div>

      <div v-if="$slots.actions" class="mt-4 flex flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>

    <!-- Body -->
    <div class="flex-1 p-5">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
  icon: Component;
  /** Small uppercase label above the title (e.g. "Posėdis"). */
  kicker: string;
  title: string;
  subtitle?: string;
}>();
</script>

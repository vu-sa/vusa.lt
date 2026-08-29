<template>
  <div data-slot="action-window-screen" class="flex min-h-0 flex-1 flex-col">
    <div :class="['shrink-0 px-5 pb-5 sm:px-7', centered && 'text-center']">
      <h2 class="text-[1.4rem] font-semibold leading-[1.15] tracking-[-0.02em] text-foreground sm:text-2xl">
        <slot name="title">{{ title }}</slot>
      </h2>
      <p
        v-if="subtitle || $slots.subtitle"
        :class="['mt-2 text-sm leading-snug text-muted-foreground', centered && 'mx-auto max-w-sm']"
      >
        <slot name="subtitle">{{ subtitle }}</slot>
      </p>
    </div>

    <!-- The only scrolling region: the header and footer must stay reachable on a phone.
         The pt/-mt pair leaves room inside the clip for the first row's focus ring
         without moving anything: `overflow-y-auto` would otherwise shear it off. -->
    <div class="-mt-1 min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pb-5 pt-1 sm:px-7">
      <slot />
    </div>

    <div
      v-if="$slots.footer"
      class="shrink-0 border-t border-border/70 bg-background/90 px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] backdrop-blur sm:px-7 sm:pb-4"
    >
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title?: string;
  subtitle?: string;
  /** For screens that are a single question rather than a form step. */
  centered?: boolean;
}>();
</script>

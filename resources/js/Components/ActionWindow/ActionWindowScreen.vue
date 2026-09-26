<template>
  <div data-slot="action-window-screen" class="flex min-h-0 flex-1 flex-col">
    <div
      v-if="title || subtitle || $slots.title || $slots.subtitle"
      :class="['shrink-0 px-5 pb-3 pt-4 sm:px-6 sm:pt-5', centered && 'text-center']"
    >
      <h2
        v-if="title || $slots.title"
        class="text-lg font-bold leading-snug tracking-tight text-foreground sm:text-xl text-balance"
      >
        <slot name="title">
          {{ title }}
        </slot>
      </h2>
      <p
        v-if="subtitle || $slots.subtitle"
        :class="['mt-1.5 text-sm leading-relaxed text-muted-foreground', centered && 'mx-auto max-w-sm']"
      >
        <slot name="subtitle">
          {{ subtitle }}
        </slot>
      </p>
    </div>

    <!-- The only scrolling region: the header and footer must stay reachable on a phone.
         The pt/-mt pair leaves room inside the clip for the first row's focus outline
         without moving anything: `overflow-y-auto` would otherwise shear it off. -->
    <div
      :class="[
        'min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pb-5 sm:px-7',
        (title || subtitle || $slots.title || $slots.subtitle) ? '-mt-1 pt-1' : 'pt-4 sm:pt-5',
      ]"
    >
      <slot />
    </div>

    <div
      v-if="$slots.footer"
      class="shrink-0 border-t border-border bg-background px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-7 sm:pb-4"
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

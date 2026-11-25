<template>
  <div class="border border-dashed border-border px-5 py-16 text-center" data-slot="public-empty-state">
    <div v-if="$slots.icon" class="mx-auto flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground [&_svg]:size-6">
      <slot name="icon" />
    </div>
    <component :is="headingTag" class="mt-4 text-base font-bold text-foreground">
      {{ title }}
    </component>
    <p v-if="description" class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
      {{ description }}
    </p>
    <div v-if="$slots.default" class="mt-6 flex flex-wrap items-center justify-center gap-2">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
/** "Nothing here" for a public listing: a dashed box with an icon, a heading, a line and optional actions. */
withDefaults(defineProps<{
  title: string;
  description?: string;
  /** The page's outline decides the level; listings sit under the page h1. */
  headingTag?: 'h2' | 'h3' | 'p';
}>(), {
  description: undefined,
  headingTag: 'h2',
});
</script>

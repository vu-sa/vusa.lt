<template>
  <p
    v-if="text"
    :class="[
      'mt-3 min-h-[2lh] flex-1 basis-0 overflow-hidden',
      'text-sm leading-relaxed text-muted-foreground',
      '[mask-image:linear-gradient(to_bottom,black_calc(100%-1lh),transparent)]',
    ]"
    data-slot="institution-card-description"
  >
    {{ text }}
  </p>
  <div v-else class="flex-1" />
</template>

<script setup lang="ts">
/**
 * Grows into whatever height the grid row leaves over, so a card with a short title shows more
 * text; basis-0 keeps it from setting the row height itself, and the fade hides the cut line.
 */
defineProps<{
  text: string | null;
}>();
</script>

<template>
  <div v-if="plainText" class="max-w-prose" data-slot="institution-about">
    <!-- eslint-disable-next-line vue/no-v-html -- sanitised on write (Institution::sanitizedHtmlTranslations) -->
    <div v-if="expanded" class="prose prose-sm dark:prose-invert max-w-none text-muted-foreground" v-html="description" />
    <p v-else ref="clamped" class="line-clamp-2 text-sm text-muted-foreground">
      {{ plainText }}
    </p>
    <button
      v-if="overflows || expanded"
      type="button"
      class="mt-1 text-xs font-medium text-muted-foreground underline-offset-4 hover:text-foreground hover:underline pointer-coarse:min-h-11"
      :aria-expanded="expanded"
      data-testid="institution-about-toggle"
      @click="expanded = !expanded"
    >
      {{ expanded ? $t('Rodyti mažiau') : $t('Skaityti daugiau') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { useResizeObserver } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  description?: string | null;
}>();

const expanded = ref(false);
const overflows = ref(false);
const clamped = ref<HTMLElement | null>(null);

// Block ends become spaces so two paragraphs do not run together in the two-line summary.
const plainText = computed(() => {
  const html = props.description?.trim();
  if (!html) {
    return '';
  }

  const spaced = html.replace(/<\/(?:p|div|li|h[1-6]|blockquote)>|<br\s*\/?>/gi, '$& ');
  const text = new DOMParser().parseFromString(spaced, 'text/html').body.textContent ?? '';

  return text.replace(/\s+/g, ' ').trim();
});

const measure = () => {
  const element = clamped.value;
  if (element) {
    overflows.value = element.scrollHeight - element.clientHeight > 1;
  }
};

useResizeObserver(clamped, measure);
watch(() => props.description, () => {
  expanded.value = false;
  requestAnimationFrame(measure);
});
</script>

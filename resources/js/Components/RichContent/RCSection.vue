<template>
  <!-- scroll-mt-32 matches .rc-prose's heading offset — the ToC's scroll-to logic uses a
       160px JS offset (TableOfContents.vue) which already runs slightly ahead of the
       128px CSS one on tiptap headings; kept consistent with that existing behavior. -->
  <section :class="['relative scroll-mt-32', PADDING_CLASS[padding], BACKGROUND_CLASS[background]]">
    <div :class="['container relative z-10 mx-auto px-4', INNER_CLASS[inner]]">
      <SectionHeader v-if="title" :title :subtitle :align />
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
/**
 * Shared section chrome for content types that render their own full-bleed block
 * (hero, accordion, card-stack, carousel, photo-gallery, number-stats, the list types).
 * Before this existed, each of those displays copy-pasted its own
 * `py-16 [bg] … container mx-auto max-w-*` markup and none of them rendered a header,
 * which is why pages like MembershipPage had to supply `SectionHeader` separately
 * around the rich-content block instead of the block owning its own title/subtitle.
 */
import SectionHeader from '@/Components/ui/SectionHeader.vue';

withDefaults(defineProps<{
  title?: string;
  subtitle?: string;
  background?: 'none' | 'muted' | 'contrast' | 'gradient';
  padding?: 'none' | 'sm' | 'md' | 'lg';
  /** Inner content max-width — independent of the canvas column the block itself sits in. */
  inner?: 'prose' | 'content' | 'wide' | 'full';
  align?: 'center' | 'start';
}>(), {
  background: 'none',
  padding: 'lg',
  inner: 'wide',
  align: 'center',
});

const BACKGROUND_CLASS: Record<string, string> = {
  none: '',
  muted: 'bg-zinc-50 dark:bg-zinc-900',
  contrast: 'bg-white dark:bg-zinc-950',
  // Same subtle surface as RichContentCard / the hero panel variant — for a section
  // that wants that card-like look rather than a flat fill.
  gradient: 'bg-gradient-to-br from-zinc-50 to-zinc-100/50 dark:from-zinc-800/80 dark:to-zinc-900',
};

const PADDING_CLASS: Record<string, string> = {
  none: '',
  sm: 'py-8',
  md: 'py-12',
  lg: 'py-16',
};

const INNER_CLASS: Record<string, string> = {
  prose: 'max-w-2xl',
  content: 'max-w-4xl',
  wide: 'max-w-6xl',
  full: 'max-w-none',
};
</script>

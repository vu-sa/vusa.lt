<template>
  <!-- The card aesthetic used across the site (SummerCampCard is the origin) — extracted
       here so link-list's photo style, the content-grid `card` cell and event-list's
       `cards` style all render identically instead of three near-copies drifting apart. -->
  <article class="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/50 transition-all duration-300 hover:shadow-lg hover:ring-zinc-300 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600">
    <!-- Stretched link: the whole card is clickable, but content underneath (e.g. a
         footer list of per-event links) can still carry its own, higher-stacked links. -->
    <SmartLink v-if="href" :href="href" class="absolute inset-0 z-10" :aria-label="title" />

    <!-- Cover — omitted entirely (not even the fallback placeholder) when there's no
         image and the card wasn't asked to always reserve a cover slot. Grids that mix
         photo and text-only cards (e.g. content-grid) want a plain card, not an empty
         gradient box; grids of uniformly-photographed items (event/summer-camp cards)
         want the fallback so the grid stays visually uniform even if one item lacks a
         photo. -->
    <div v-if="coverImage || showCoverFallback" class="relative aspect-[16/9] overflow-hidden bg-zinc-200 dark:bg-zinc-800">
      <img v-if="coverImage" :src="coverImage" :alt="coverAlt ?? title"
        class="size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
      <div v-else class="flex size-full items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/25">
        <slot name="cover-fallback">
          <IFluentImage24Regular class="size-10 text-vusa-red/50" />
        </slot>
      </div>

      <span v-if="badge" class="absolute right-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur-sm">
        {{ badge }}
      </span>
    </div>

    <div class="flex flex-1 flex-col p-5">
      <h3 class="text-base font-bold leading-tight text-zinc-900 dark:text-zinc-100">
        {{ title }}
      </h3>
      <p v-if="meta" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
        {{ meta }}
      </p>

      <div v-if="$slots.default" class="relative z-20 mt-4 flex flex-1 flex-col gap-2">
        <slot />
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import SmartLink from '@/Components/Public/SmartLink.vue';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

withDefaults(defineProps<{
  title: string;
  coverImage?: string | null;
  coverAlt?: string;
  /** Small pill in the cover's top-right corner (e.g. an item count). */
  badge?: string | number | null;
  /** One-line note under the title (e.g. a date). */
  meta?: string | null;
  /** When set, the whole card becomes a link (stretched-link pattern). Omit it when
   *  the default slot supplies its own nested links instead (e.g. a per-event list). */
  href?: string | null;
  /** Reserve the cover slot (rendering the fallback icon) even when `coverImage` is
   *  unset — for grids where every card is expected to carry a photo. Default true to
   *  preserve existing callers (SummerCampCard, link-list, event-list); content-grid's
   *  plain `card` cell opts out since a text-only card shouldn't show an empty photo box. */
  showCoverFallback?: boolean;
}>(), {
  showCoverFallback: true,
});
</script>

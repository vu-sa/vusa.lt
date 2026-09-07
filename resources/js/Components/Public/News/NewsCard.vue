<!-- eslint-disable vue/no-v-html -->
<template>
  <SmartLink
    :href
    prefetch
    :class="[
      'group',
      size === 'featured' ? 'grid gap-6 lg:grid-cols-2 lg:gap-10' : 'flex flex-col',
    ]"
    data-slot="news-card"
  >
    <MediaFrame
      :src="news.image ?? undefined"
      :alt="news.title"
      ratio="16/9"
      :grayscale="false"
      :eager
      hover-zoom
      class="bg-secondary"
    >
      <template #fallback>
        <IFluentImage24Regular class="size-10 text-muted-foreground/50" />
      </template>

      <!-- The category marker sits on the photograph, top-left, so a grid of cards reads as a
           list of subjects before it reads as a list of pictures.

           Loud on the one featured article, quiet on a grid of many — one accent per view is the
           rule, and a dozen solid brand blocks in a grid is a dozen accents. The quiet form is
           spelled out rather than using `variant="muted"`, which is a bordered chip for a light
           ground and disappears against a photograph. -->
      <TagChip
        v-if="news.category"
        :label="news.category"
        class="absolute left-0 top-0"
        :class="[
          size === 'featured'
            ? 'bg-brand-fill text-brand-foreground font-bold'
            : (size === 'sm' ? 'bg-background/90 text-brand' : undefined),
        ]"
      />
    </MediaFrame>

    <div :class="size === 'featured' ? 'flex flex-col justify-center' : 'mt-4 flex flex-1 flex-col'">
      <span v-if="news.publish_time" class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ longDate(news.publish_time) }}
      </span>

      <h3 :class="[
        'text-pretty font-bold text-foreground transition-colors group-hover:text-brand',
        size === 'featured'
          ? 'mt-3 text-3xl sm:text-4xl leading-tight'
          : (size === 'lg' ? 'mt-3 text-2xl leading-tight sm:text-[1.7rem]' : 'mt-2 text-lg leading-snug'),
      ]">
        {{ news.title }}
      </h3>

      <div
        v-if="showExcerpt && news.short"
        :class="[
          'text-pretty leading-relaxed text-muted-foreground',
          size === 'featured'
            ? 'mt-4 text-base sm:text-lg line-clamp-3'
            : (size === 'lg' ? 'mt-3 line-clamp-3' : 'mt-2 line-clamp-2 text-sm'),
        ]"
        v-html="news.short"
      />

      <span
        v-if="size === 'lg' || size === 'featured'"
        :class="[
          'inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand',
          size === 'featured' ? 'mt-4 sm:mt-6' : 'mt-4',
        ]"
      >
        {{ $t('Skaityti daugiau') }}
        <IFluentArrowUpRight16Regular class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
      </span>
    </div>
  </SmartLink>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';

import SmartLink from '@/Components/Public/SmartLink.vue';
import IFluentArrowUpRight16Regular from '~icons/fluent/arrow-up-right-16-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';
import { MediaFrame, TagChip } from '@/Components/Public/Base';
import type { NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';
import { localizedRoute } from '@/Utils/LocalizedRoutes';

/**
 * One article as a card: picture, category, date, headline.
 *
 * Three sizes:
 * - `featured`: 2-column hero article with large typography for archive lead
 * - `lg`: vertical lead card with large typography (used in NewsElement)
 * - `sm`: compact card for grids (archive, related news)
 */
const props = withDefaults(defineProps<{
  news: NewsItem | (Omit<NewsItem, 'publish_time'> & { publish_time?: string | number | Date | null });
  size?: 'sm' | 'lg' | 'featured';
  /** `lg` and `featured` always show it; `sm` only where there is room, such as the archive grid. */
  showExcerpt?: boolean;
  /** Opt out of lazy loading for a card that is above the fold. */
  eager?: boolean;
}>(), {
  size: 'sm',
});

const page = usePage();

const showExcerpt = computed(() => props.showExcerpt ?? (props.size === 'lg' || props.size === 'featured'));

const href = computed(() => localizedRoute('news', {
  news: props.news.permalink ?? '',
  subdomain: page.props.tenant?.subdomain ?? 'www',
}, props.news.lang));

const normalizeDate = (d: number | Date | string | undefined | null): Date => {
  if (!d) return new Date();
  if (d instanceof Date) return d;
  if (typeof d === 'number') {
    return new Date(d < 10000000000 ? d * 1000 : d);
  }
  if (typeof d === 'string' && /^\d+$/.test(d)) {
    const num = Number(d);
    return new Date(num < 10000000000 ? num * 1000 : num);
  }
  return new Date(d);
};

const longDate = (time: number | string | Date) => formatStaticTime(
  normalizeDate(time),
  { year: 'numeric', month: 'long', day: 'numeric' },
  page.props.app.locale,
);
</script>

<template>
  <SmartLink :href prefetch class="group flex flex-col" data-slot="news-card">
    <MediaFrame
      :src="imageSrc"
      :alt="news.title"
      :ratio="size === 'lg' ? '16/9' : '16/10'"
      :grayscale="false"
      :eager
      hover-zoom
      class="bg-secondary"
    >
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
        :class="size === 'sm' && 'bg-background/90 text-brand'"
      />
    </MediaFrame>

    <div class="mt-4 flex flex-1 flex-col">
      <span v-if="news.publish_time" class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ longDate(news.publish_time) }}
      </span>

      <h3 :class="[
        'text-pretty font-bold text-foreground transition-colors group-hover:text-brand',
        size === 'lg' ? 'mt-3 text-2xl leading-tight sm:text-[1.7rem]' : 'mt-2 text-lg leading-snug',
      ]">
        {{ news.title }}
      </h3>

      <div
        v-if="showExcerpt && news.short"
        :class="[
          'text-pretty leading-relaxed text-muted-foreground',
          size === 'lg' ? 'mt-3 line-clamp-3' : 'mt-2 line-clamp-2 text-sm',
        ]"
        v-html="news.short"
      />

      <span v-if="size === 'lg'" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand">
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
import { MediaFrame, TagChip } from '@/Components/Public/Base';
import type { NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';
import { localizedRoute } from '@/Utils/LocalizedRoutes';

/**
 * One article as a card: picture, category, date, headline.
 *
 * Two sizes rather than two components — they are the same card at two weights, and keeping them
 * apart is how the featured article and the grid below it drifted into different treatments
 * before. `lg` is the one article a band leads with (`NewsElement`'s featured column); `sm` is a
 * cell in a grid of many (the article page's related news, the archive).
 */
const props = withDefaults(defineProps<{
  news: NewsItem;
  size?: 'sm' | 'lg';
  /** `lg` always shows it; `sm` only where there is room, such as the archive grid. */
  showExcerpt?: boolean;
  /** Opt out of lazy loading for a card that is above the fold. */
  eager?: boolean;
}>(), {
  size: 'sm',
  showExcerpt: undefined,
  eager: false,
});

// Articles without an image are a real case (the local dev database has no uploads at all), and
// an empty MediaFrame reads as a broken layout rather than as "no photo".
const FALLBACK_IMAGE = '/images/icons/naujienu_foto.png';

const page = usePage();

const imageSrc = computed(() => props.news.image || FALLBACK_IMAGE);

const showExcerpt = computed(() => props.showExcerpt ?? props.size === 'lg');

const href = computed(() => localizedRoute('news', {
  news: props.news.permalink ?? '',
  subdomain: page.props.tenant?.subdomain ?? 'www',
}, props.news.lang));

const longDate = (time: string) => formatStaticTime(
  new Date(time),
  { year: 'numeric', month: 'long', day: 'numeric' },
  page.props.app.locale,
);
</script>

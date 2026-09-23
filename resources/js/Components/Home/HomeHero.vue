<template>
  <section
    :class="[
      'relative isolate flex flex-col overflow-hidden text-foreground',
      'sm:min-h-40 sm:flex-row sm:items-end sm:justify-between sm:gap-8 sm:bg-background sm:p-7',
    ]"
    data-slot="home-hero"
  >
    <img
      :src="photo.src"
      :alt="photo.alt"
      loading="lazy"
      class="absolute inset-0 -z-10 hidden size-full object-cover opacity-65 sm:block"
    >
    <!-- The public hero's "strong" scrim (HeroCarouselSlideView), mirrored so the news side reads too, but faded
         into the page background so light mode stays paper. Phones get no band: just the date and greeting. -->
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-r from-background/95 from-0% via-background/68 via-32% to-background/22 to-62% sm:block" aria-hidden="true" />
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-l from-background/95 from-0% via-background/68 via-32% to-transparent to-62% sm:block" aria-hidden="true" />
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-t from-background/78 via-transparent to-background/18 sm:block" aria-hidden="true" />

    <div class="min-w-0">
      <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground" data-testid="hero-date">
        {{ today }}
      </p>
      <h1 class="u-display mt-2 text-balance text-2xl text-foreground sm:text-3xl" data-tour="greeting-section">
        {{ greeting }}
      </h1>
      <p v-if="summary" class="mt-1 text-sm text-foreground" data-testid="hero-summary">
        {{ summary }}
      </p>
    </div>

    <article v-if="news" class="hidden min-w-0 flex-col gap-2 sm:flex sm:max-w-sm sm:items-end sm:text-right" data-testid="hero-news">
      <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
        {{ $t('home.hero.news_eyebrow') }}
        <span aria-hidden="true">·</span>
        <time :datetime="news.publish_time">{{ publishedAgo }}</time>
      </p>
      <h2 class="line-clamp-2 text-sm font-medium leading-snug text-foreground">
        <a
          v-if="news.public_url"
          :href="news.public_url"
          class="hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-foreground"
        >
          {{ news.title }}
        </a>
        <template v-else>
          {{ news.title }}
        </template>
      </h2>
      <div class="flex flex-wrap gap-2 sm:justify-end">
        <Button v-if="news.public_url" as-child variant="outline" size="xs" class="pointer-coarse:min-h-11">
          <a :href="news.public_url" data-testid="hero-read">
            {{ $t('home.hero.read_news') }}
          </a>
        </Button>
        <Button as-child variant="outline" size="xs" class="pointer-coarse:min-h-11">
          <a :href="news.archive_url" data-testid="hero-archive">
            {{ $t('home.hero.all_news') }}
          </a>
        </Button>
      </div>
    </article>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import type { HomeHeroNews } from './types';

import { Button } from '@/Components/ui/button';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { communityPhotos } from '@/Constants/communityPhotos';
import { VILNIUS_TIMEZONE } from '@/Utils/dateTime';

const props = defineProps<{
  greeting: string;
  news: HomeHeroNews | null;
  /** One line on what waits, so the answer shows before the rep scrolls. */
  summary?: string | null;
}>();

const { locale, formatNearDate } = useDateFormatter();

const DAY_MS = 86_400_000;

// Without news the photo changes once a day, not per visit, so the page stays calm.
const photo = computed(() => {
  if (props.news) {
    return { src: props.news.image, alt: '' };
  }

  return communityPhotos[Math.floor(Date.now() / DAY_MS) % communityPhotos.length];
});

const today = computed(() => new Intl.DateTimeFormat(locale.value, {
  timeZone: VILNIUS_TIMEZONE,
  weekday: 'long',
  month: 'long',
  day: 'numeric',
}).format(new Date()));

const publishedAgo = computed(() => props.news ? formatNearDate(props.news.publish_time, { fallbackFormat: 'full' }) : '');
</script>

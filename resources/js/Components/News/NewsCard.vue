<!-- eslint-disable vue/no-v-html -->
<template>
  <component
    :is="useInertia ? Link : 'a'"
    :href
    :prefetch="useInertia ? true : undefined"
    :class="[
      'group',
      size === 'featured' ? 'grid gap-6 lg:grid-cols-2 lg:gap-10' : size === 'compact' ? 'flex gap-4 py-4' : 'flex flex-col',
    ]"
    data-slot="news-card"
  >
    <MediaFrame
      :image="news.image_media"
      :sizes="imageSizes"
      :src="news.image ?? undefined"
      :alt="news.title"
      ratio="16/9"
      :grayscale="false"
      :eager
      hover-zoom
      :class="size === 'compact' ? 'w-28 shrink-0 sm:w-36' : 'bg-secondary'"
    >
      <template #fallback>
        <Image v-if="size === 'compact'" class="size-8 text-muted-foreground/50" />
        <IFluentImage24Regular v-else class="size-10 text-muted-foreground/50" />
      </template>
    </MediaFrame>

    <div :class="[
      size === 'featured' ? 'flex flex-col justify-center' : 'flex flex-1 flex-col',
      size === 'compact' ? 'min-w-0 justify-center gap-1' : 'mt-4',
    ]">
      <span v-if="news.publish_time" class="text-xs font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ longDate(news.publish_time) }}
      </span>
      <component :is="headingTag" :class="[
        'text-pretty font-bold text-foreground transition-colors group-hover:text-brand',
        size === 'featured' ? 'mt-3 text-3xl leading-tight sm:text-4xl' :
        size === 'lg' ? 'mt-3 text-2xl leading-tight sm:text-[1.7rem]' :
        size === 'compact' ? 'text-sm leading-snug' : 'mt-2 text-lg leading-snug',
      ]">
        <SearchMatch v-if="titleMatch" :match="titleMatch" /><template v-else>
          {{ news.title }}
        </template>
      </component>
      <!-- A search excerpt takes the short's place, so cards keep their height while typing. -->
      <div v-if="showExcerpt && news.short && excerptMatch" :class="excerptClass">
        <SearchMatch :match="excerptMatch" />
      </div>
      <div v-else-if="showExcerpt && news.short" :class="excerptClass" v-html="unwrapLinks(news.short)" />
      <span v-if="size === 'lg' || size === 'featured'" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand">
        {{ $t('Skaityti daugiau') }} <IFluentArrowUpRight16Regular class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
      </span>
    </div>
  </component>
</template>

<script setup lang="ts">

import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Image } from '@lucide/vue';
import { computed } from 'vue';

import SearchMatch from '@/Components/ui/SearchMatch.vue';
import { descriptionMatch, matchTitle } from '@/Shared/Search/matches';
import MediaFrame from '@/Components/Brand/MediaFrame.vue';
import type { ImageData } from '@/Types/media';
import type { NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import { unwrapLinks } from '@/Utils/String';
import IFluentArrowUpRight16Regular from '~icons/fluent/arrow-up-right-16-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

type PreviewNews = Pick<NewsItem, 'id' | 'title' | 'lang' | 'permalink' | 'image'> & {
  _searchMatch?: NewsItem['_searchMatch'];
  _searchTitleMatches?: NewsItem['_searchTitleMatches'];
  _searchFieldMatches?: NewsItem['_searchFieldMatches'];
  image_media?: ImageData | null;
  short?: string;
  publish_time?: string | number | Date | null;
  public_url?: string | null;
};

const props = withDefaults(defineProps<{
  news: PreviewNews;
  size?: 'sm' | 'lg' | 'featured' | 'compact';
  showExcerpt?: boolean;
  eager?: boolean;
  inertia?: boolean;
  /** h2 where the card sits directly under the page's h1 (no section heading between). */
  headingTag?: 'h2' | 'h3';
}>(), {
  size: 'sm',
  headingTag: 'h3',
  // eslint-disable-next-line vue/no-boolean-default
  inertia: true,
});

const page = usePage<PageProps>();
const imageSizes = computed(() => props.size === 'compact'
  ? '(min-width: 640px) 144px, 112px'
  : props.size === 'featured' || props.size === 'lg'
    ? '(min-width: 1280px) 584px, (min-width: 1024px) calc((100vw - 104px) / 2), (min-width: 640px) calc(100vw - 48px), calc(100vw - 40px)'
    : '(min-width: 1280px) 384px, (min-width: 1024px) calc((100vw - 128px) / 3), (min-width: 640px) calc((100vw - 80px) / 2), calc(100vw - 40px)');

const showExcerpt = computed(() => props.showExcerpt ?? (props.size === 'lg' || props.size === 'featured'));
const titleMatch = computed(() => matchTitle(props.news.title, props.news._searchTitleMatches));
const excerptMatch = computed(() => descriptionMatch(props.news, 'short'));
const excerptSizeClass: Record<string, string> = {
  featured: 'mt-4 line-clamp-3 text-base sm:text-lg',
  lg: 'mt-3 line-clamp-3',
};
const excerptClass = computed(() => [
  'text-pretty leading-relaxed text-muted-foreground',
  excerptSizeClass[props.size] ?? 'mt-2 line-clamp-2 text-sm',
]);
const href = computed(() => props.news.public_url ?? localizedRoute('news', {
  news: props.news.permalink ?? '',
  subdomain: page.props.tenant?.subdomain ?? 'www',
}, props.news.lang));
const useInertia = computed(() => {
  if (!props.inertia || typeof window === 'undefined' || !href.value) return false;
  return new URL(href.value, window.location.href).origin === window.location.origin;
});

const longDate = (time: string | number | Date) => formatStaticTime(
  time instanceof Date
    ? time
    : new Date(typeof time === 'number' || /^\d+$/.test(time) ? Number(time) * (Number(time) < 10000000000 ? 1000 : 1) : time),
  { year: 'numeric', month: 'long', day: 'numeric' },
  page.props.app.locale,
);
</script>

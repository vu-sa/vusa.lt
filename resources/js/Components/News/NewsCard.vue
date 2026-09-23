<!-- eslint-disable vue/no-v-html -->
<template>
  <component
    :is="sameOrigin ? Link : 'a'"
    :href
    :prefetch="sameOrigin ? true : undefined"
    :class="[
      'group',
      size === 'featured' ? 'grid gap-6 lg:grid-cols-2 lg:gap-10' : size === 'compact' ? 'flex gap-4 py-4' : 'flex flex-col',
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
      <h3 :class="[
        'text-pretty font-bold text-foreground transition-colors group-hover:text-brand',
        size === 'featured' ? 'mt-3 text-3xl leading-tight sm:text-4xl' :
        size === 'lg' ? 'mt-3 text-2xl leading-tight sm:text-[1.7rem]' :
        size === 'compact' ? 'text-sm leading-snug' : 'mt-2 text-lg leading-snug',
      ]">
        {{ news.title }}
      </h3>
      <div
        v-if="showExcerpt && news.short"
        :class="[
          'text-pretty leading-relaxed text-muted-foreground',
          size === 'featured' ? 'mt-4 line-clamp-3 text-base sm:text-lg' :
          size === 'lg' ? 'mt-3 line-clamp-3' : 'mt-2 line-clamp-2 text-sm',
        ]"
        v-html="news.short"
      />
      <span v-if="size === 'lg' || size === 'featured'" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand">
        {{ $t('Skaityti daugiau') }} <IFluentArrowUpRight16Regular class="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
      </span>
    </div>
  </component>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Image } from 'lucide-vue-next';
import { computed } from 'vue';

import MediaFrame from '@/Components/Brand/MediaFrame.vue';
import type { NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import IFluentArrowUpRight16Regular from '~icons/fluent/arrow-up-right-16-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

type PreviewNews = Pick<NewsItem, 'id' | 'title' | 'lang' | 'permalink' | 'image'> & {
  short?: string;
  publish_time?: string | number | Date | null;
  public_url?: string | null;
};

const props = withDefaults(defineProps<{
  news: PreviewNews;
  size?: 'sm' | 'lg' | 'featured' | 'compact';
  showExcerpt?: boolean;
  eager?: boolean;
}>(), { size: 'sm' });

const page = usePage<PageProps>();
const showExcerpt = computed(() => props.showExcerpt ?? (props.size === 'lg' || props.size === 'featured'));
const href = computed(() => props.news.public_url ?? localizedRoute('news', {
  news: props.news.permalink ?? '',
  subdomain: page.props.tenant?.subdomain ?? 'www',
}, props.news.lang));
const sameOrigin = computed(() => {
  if (typeof window === 'undefined' || !href.value) return false;
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

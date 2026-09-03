<template>
  <!-- Single root: with 4 sibling v-if/else-if/else branches, RichContentParser's
       width/spacing :class had no single element to fall through to. -->
  <div>
    <section class="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:px-8 lg:py-24" aria-labelledby="news-section-heading">
      <!-- Section head: eyebrow + display heading on the left, the archive link on the right,
           closed by the hairline that every band on this surface is separated by. -->
      <div class="flex flex-wrap items-end justify-between gap-4 border-b border-border pb-5">
        <div>
          <EyebrowLabel v-if="showEyebrow">{{ $t('Naujienos') }}</EyebrowLabel>
          <h2 id="news-section-heading" class="u-display mt-2 text-3xl text-foreground sm:text-4xl">
            {{ heading }}
          </h2>
        </div>
        <SmartLink
          :href="archiveHref"
          prefetch
          class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand"
        >
          {{ $t('Žiūrėti visas') }}
          <IFluentArrowRight16Regular class="size-4" />
        </SmartLink>
      </div>

      <div v-if="loading" class="mt-8 grid gap-8 lg:grid-cols-2 lg:gap-10">
        <div class="space-y-4">
          <Skeleton class="aspect-[16/10] w-full" />
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-8 w-3/4" />
          <Skeleton class="h-20 w-full" />
        </div>
        <div class="flex flex-col">
          <div v-for="i in 3" :key="i" class="flex gap-4 border-t border-border py-5 first:border-t-0 first:pt-0 sm:gap-5">
            <Skeleton class="aspect-[16/10] w-32 shrink-0 sm:w-44" />
            <div class="flex-1 space-y-2 py-1">
              <Skeleton class="h-3 w-20" />
              <Skeleton class="h-4 w-full" />
              <Skeleton class="h-3 w-24" />
            </div>
          </div>
        </div>
      </div>

      <p v-else-if="error" class="mt-8 text-destructive" role="alert">
        {{ $t("Nepavyko užkrauti naujienų") }}
      </p>

      <p v-else-if="!featured" class="mt-8 text-muted-foreground">
        {{ $t("Nėra naujienų") }}
      </p>

      <div v-else class="mt-8 grid gap-8 lg:grid-cols-2 lg:gap-10">
        <!-- Featured: the one article that gets a picture at full width. `NewsCard` owns this
             treatment — the article page's related grid and the archive render the same card at
             its smaller size, and keeping one component is what stops the three drifting apart. -->
        <NewsCard :news="featured" size="lg" eager />

        <!-- The rest as a hairline list. Rows are separate links, not slide selectors: the
             design has no carousel here, so every headline is one click from the reader. -->
        <div class="flex flex-col">
          <SmartLink
            v-for="item in rest"
            :key="item.id"
            :href="getNewsRoute(item)"
            prefetch
            class="group flex gap-4 border-t border-border py-5 first:border-t-0 first:pt-0 sm:gap-5 lg:first:border-t lg:first:pt-5"
          >
            <MediaFrame
              :src="getImageSrc(item.image)"
              :alt="item.title"
              ratio="16/10"
              :grayscale="false"
              hover-zoom
              class="w-32 shrink-0 sm:w-44"
            />
            <div class="flex flex-1 flex-col justify-center gap-1.5">
              <span v-if="item.category" class="text-[0.6875rem] font-bold uppercase tracking-[0.18em] text-brand">
                {{ item.category }}
              </span>
              <h3 class="text-pretty font-bold leading-snug text-foreground transition-colors group-hover:text-brand">
                {{ item.title }}
              </h3>
              <span v-if="item.publish_time" class="text-xs font-medium text-muted-foreground">
                {{ longDate(item.publish_time) }}
              </span>
            </div>
          </SmartLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import { trans as $t } from 'laravel-vue-i18n';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import SmartLink from './SmartLink.vue';

import NewsCard from '@/Components/Public/News/NewsCard.vue';
import type { News, NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';
import { useNewsFetch } from '@/Services/ContentService';
import { EyebrowLabel, MediaFrame } from '@/Components/Public/Base';
import { Skeleton } from '@/Components/ui/skeleton';

// Fallback image for news without images
const FALLBACK_IMAGE = '/images/icons/naujienu_foto.png';

const getImageSrc = (image: string | null): string => image ?? FALLBACK_IMAGE;

// Props - element is from content parts. `resolved` is the server-resolved payload
// (ContentPartResolver, via RichContentParser's `resolved` prop); `prefetchedNews` is
// the older homepage-only prop, kept as a fallback until HomePage moves onto the
// resolver too (see RichContentParser.vue).
const props = defineProps<{
  element: News;
  resolved?: { type: string; items: NewsItem[] } | null;
  /** @deprecated Superseded by `resolved` — only HomePage still supplies this directly. */
  prefetchedNews?: NewsItem[];
}>();

const page = usePage();

const serverNews = computed<NewsItem[] | undefined>(() => props.resolved?.items ?? props.prefetchedNews);

/**
 * Presence, not emptiness. `[]` from the resolver means "the server looked and there is nothing
 * to show" — treating that as "no data" sent the component off to fetch the same empty answer
 * over the network, and flashed a skeleton before rendering the same empty state.
 */
const hasPrefetchedNews = computed(() => serverNews.value !== undefined);

// Only use API fetch if no server-provided news is available (prevents waterfall on
// pages that already got it from ContentPartResolver or the homepage prefetch).
const { news: apiFetchedNews, loading: apiLoading, error: apiError } = hasPrefetchedNews.value
  ? { news: ref([]), loading: ref(false), error: ref(null) }
  : useNewsFetch();

// Combine sources: prefer server-provided data, fall back to API data
const newsItems = computed<NewsItem[]>(() => {
  if (hasPrefetchedNews.value) {
    return serverNews.value as NewsItem[];
  }
  return apiFetchedNews.value as NewsItem[] ?? [];
});

const loading = computed(() => !hasPrefetchedNews.value && apiLoading.value);
const error = computed(() => !hasPrefetchedNews.value && apiError.value);

const heading = computed(() => props.element?.json_content?.title || $t('Kas naujo bendruomenėje'));

/**
 * Most authored blocks are titled simply "Naujienos", which is also the eyebrow. Showing both
 * stacks the same word twice, so the eyebrow steps aside whenever the author has already said it.
 */
const showEyebrow = computed(() => heading.value.trim().toLowerCase() !== $t('Naujienos').trim().toLowerCase());

const featured = computed<NewsItem | undefined>(() => newsItems.value[0]);
// Three, not "the rest": the list column is sized against the featured article beside it, and
// a fourth row makes the two columns visibly uneven.
const rest = computed<NewsItem[]>(() => newsItems.value.slice(1, 4));

const longDate = (time: string) => formatStaticTime(
  new Date(time),
  { year: 'numeric', month: 'long', day: 'numeric' },
  page.props.app.locale,
);

const getNewsRoute = (item: NewsItem) => localizedRoute('news', {
  news: item.permalink ?? '',
  subdomain: page.props.tenant?.subdomain ?? 'www',
}, item.lang);

const archiveHref = computed(() => route('newsArchive', {
  subdomain: page.props.tenant?.subdomain ?? 'www',
  lang: page.props.app.locale,
}));
</script>

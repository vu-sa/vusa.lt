<template>
  <!-- Single root: with 4 sibling v-if/else-if/else branches, RichContentParser's
       width/spacing :class had no single element to fall through to. -->
  <section :class="bandClasses" aria-labelledby="news-section-heading">
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
      <!-- Section head: eyebrow + display heading on the left, the archive link on the right,
           closed by the hairline that every band on this surface is separated by. -->
      <div class="flex flex-wrap items-end justify-between gap-4 border-b border-border pb-5">
        <div>
          <EyebrowLabel v-if="editable || showEyebrow">
            <RCInlineText
              as="span" :model-value="editable ? (element.json_content.eyebrow ?? '') : eyebrowText"
              :editable :placeholder="$t('Naujienos')"
              @update:model-value="updateEyebrow"
            />
          </EyebrowLabel>
          <!-- eslint-disable-next-line vuejs-accessibility/heading-has-content -- RCInlineText renders the real text at runtime; eslint can't see through the child component. -->
          <h2 id="news-section-heading" class="u-display mt-2 text-3xl text-foreground sm:text-4xl">
            <RCInlineText
              as="span" :model-value="editable ? (element.json_content.title ?? '') : heading"
              :editable :placeholder="$t('Kas naujo bendruomenėje')"
              @update:model-value="updateTitle"
            />
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
          <Skeleton class="aspect-[16/9] w-full" />
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-8 w-3/4" />
          <Skeleton class="h-20 w-full" />
        </div>
        <div class="flex flex-col">
          <div v-for="i in 3" :key="i" class="flex gap-4 border-t border-border py-5 first:border-t-0 first:pt-0 sm:gap-5">
            <Skeleton class="aspect-[16/9] w-32 shrink-0 sm:w-44" />
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
              :src="item.image ?? undefined"
              :alt="item.title"
              ratio="16/9"
              :grayscale="false"
              hover-zoom
              class="w-32 shrink-0 sm:w-44"
            >
              <template #fallback>
                <IFluentImage24Regular class="size-6 text-muted-foreground/50" />
              </template>
            </MediaFrame>
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
    </div>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import SmartLink from './SmartLink.vue';

import { localizedRoute } from '@/Utils/LocalizedRoutes';
import NewsCard from '@/Components/Public/News/NewsCard.vue';
import type { News, NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';
import { useNewsFetch } from '@/Services/ContentService';
import { EyebrowLabel, MediaFrame } from '@/Components/Public/Base';
import { Skeleton } from '@/Components/ui/skeleton';
import RCInlineText from '@/Components/RichContent/Editor/Fullscreen/RCInlineText.vue';
import type { BandResolution } from '@/Components/RichContent/bandLayout';
import { BAND_GROUND_CLASS, BAND_PADDING } from '@/Components/RichContent/sectionClasses';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

// Props - element is from content parts. `resolved` is the server-resolved payload
// (ContentPartResolver, via RichContentParser's `resolved` prop); `prefetchedNews` is
// the older homepage-only prop, kept as a fallback until HomePage moves onto the
// resolver too (see RichContentParser.vue).
const props = defineProps<{
  element: News;
  resolved?: { type: string; items: NewsItem[] } | null;
  /** @deprecated Superseded by `resolved` — only HomePage still supplies this directly. */
  prefetchedNews?: NewsItem[];
  /** Full-screen editor mode: the title and eyebrow become click-to-edit. */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this type has no per-field claiming, but an
   *  undeclared non-undefined prop would otherwise land on the root as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
  band?: BandResolution;
}>();

const emit = defineEmits<(e: 'update:element', value: News) => void>();

function updateTitle(title: string): void {
  emit('update:element', { ...props.element, json_content: { ...props.element.json_content, title } });
}

function updateEyebrow(eyebrow: string): void {
  emit('update:element', { ...props.element, json_content: { ...props.element.json_content, eyebrow } });
}

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
const eyebrowText = computed(() => props.element.json_content.eyebrow || $t('Naujienos'));

/**
 * Most authored blocks are titled simply "Naujienos", which is also the eyebrow. Showing both
 * stacks the same word twice, so the eyebrow steps aside whenever the author has already said it.
 */
const showEyebrow = computed(() => eyebrowText.value.trim().toLowerCase() !== heading.value.trim().toLowerCase());

const bandClasses = computed(() => props.band?.classes
  ?? ['rc-band', 'relative', 'scroll-mt-32', BAND_PADDING, BAND_GROUND_CLASS.tint, 'rc-viewport']);

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

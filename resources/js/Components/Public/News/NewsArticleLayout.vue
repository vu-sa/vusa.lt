<template>
  <article :class="['relative', className]" data-slot="news-article">
    <!-- ── Title band ───────────────────────────────────────────────────────────────────────
         The masthead the article emerges from: warm tint on light, the near-black `--ink` slab on
         dark (see `.band-masthead`). `rc-viewport` escapes the rc-canvas, `.wrapper` and
         PublicLayout's `.container` so it reaches the viewport edges.

         The `-mt-*` pull-up mirrors PublicLayout's content wrapper (`pt-4 md:pt-6 lg:pt-8`)
         exactly — without it that padding shows as a strip of page background between the fixed
         header and the band, which should sit flush against it. Same idiom as HeroElement's. -->
    <header class="band-masthead rc-viewport -mt-4 border-b border-border md:-mt-6 lg:-mt-8">
      <div class="mx-auto max-w-3xl px-5 py-12 sm:px-6 lg:py-16">
        <PublicBreadcrumbs v-if="showBreadcrumbs" variant="inline" class="mb-8" />

        <div class="border-l-2 border-brand pl-5 sm:pl-7">
          <TagChip v-if="categoryName" :label="categoryName" />
          <h1 :class="['u-display text-3xl sm:text-5xl', categoryName && 'mt-5']">
            {{ article.title }}
          </h1>
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3 pl-5 text-xs font-medium text-muted-foreground sm:pl-7">
          <span v-if="article.tenant" class="flex items-center gap-1.5">
            <IFluentOrganization16Regular class="size-3.5 text-brand" aria-hidden="true" />
            {{ article.tenant }}
          </span>
          <span v-if="article.publish_time" class="flex items-center gap-1.5">
            <IFluentCalendarLtr16Regular class="size-3.5 text-brand" aria-hidden="true" />
            <time :datetime="isoPublishTime">{{ publishedOn }}</time>
          </span>
          <span v-if="article.reading_time" class="flex items-center gap-1.5">
            <IFluentClock24Regular class="size-3.5 text-brand" aria-hidden="true" />
            {{ $t('news.reading_time', { minutes: String(article.reading_time) }) }}
          </span>

          <!-- The design has no per-article language switch; this is built in its idiom rather
               than bolted on — the same hairline box the header's utility controls use, sitting
               with the other article metadata because that is what it is. -->
          <Link
            v-if="otherLangURL"
            :href="otherLangURL"
            class="inline-flex items-center gap-2 border border-border px-2.5 py-1 transition-colors hover:border-brand hover:text-brand"
          >
            <LocaleFlag :locale="otherLocale" />
            <span>{{ $t('news.read_in_other_language') }}</span>
          </Link>
        </div>

        <!-- Tags are ours too, and they are links: the old markup used <button> + router.visit,
             which cannot be middle-clicked, opened in a tab, or followed by a crawler. -->
        <div v-if="tags.length > 0" class="mt-5 flex flex-wrap gap-2 pl-5 sm:pl-7">
          <TagChip
            v-for="tag in tags"
            :key="tag.id"
            :label="tag.name"
            :href="tag.href"
            variant="muted"
          />
        </div>
      </div>
    </header>

    <!-- ── Hero image ───────────────────────────────────────────────────────────────────────
         Deliberately wider than the reading measure and pulled up so it straddles the band's
         bottom edge — the one element that ties the masthead to the article below it. Full
         colour: the grayscale treatment is for photography sitting *behind* type. -->
    <div v-if="article.image" class="mx-auto max-w-4xl px-5 sm:px-6">
      <MediaFrame
        :src="article.image as string"
        :alt="article.title"
        ratio="16/9"
        :grayscale="false"
        eager
        class="sm:-mt-8 sm:aspect-[2/1]"
        :style="{ viewTransitionName: `news-image-${article.id}` }"
      />
      <p v-if="article.image_author" class="mt-2 text-right text-xs text-muted-foreground">
        {{ article.image_author }}
      </p>
    </div>

    <!-- ── Body ─────────────────────────────────────────────────────────────────────────── -->
    <div class="mx-auto max-w-3xl px-5 py-12 sm:px-6 lg:py-16">
      <ReadingSizeControl>
        <div v-if="article.short" class="rc-lead" v-html="article.short" />

        <HighlightsCallout v-if="article.highlights?.length" :highlights="article.highlights as string[]" />

        <!-- A nested canvas, not a `.wrapper` child, so its gutter/wide steps are zeroed out:
             the width here is already bounded by the column above. -->
        <div class="mt-8 rc-canvas text-base md:text-[1.0625rem]"
          style="--rc-measure: 44rem; --rc-gutter: 0px; --rc-wide-step: 0px; --rc-content-step: 0px">
          <slot />
        </div>
      </ReadingSizeControl>

      <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-border pt-6">
        <SmartLink
          :href="archiveHref"
          prefetch
          class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand"
        >
          <IFluentArrowLeft16Regular class="size-4" />
          {{ $t('news.all_news') }}
        </SmartLink>
        <ShareButton :title="article.title" />
      </div>
    </div>

    <!-- ── Related ──────────────────────────────────────────────────────────────────────── -->
    <section v-if="relatedArticles.length > 0" class="rc-viewport border-t border-border bg-secondary/40">
      <div class="mx-auto max-w-7xl px-5 py-14 sm:px-6 lg:px-8 lg:py-20">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-border pb-5">
          <div>
            <EyebrowLabel>{{ $t('news.read_on') }}</EyebrowLabel>
            <h2 class="u-display mt-2 text-2xl text-foreground sm:text-3xl">
              {{ $t('news.other_news') }}
            </h2>
          </div>
          <SmartLink
            :href="archiveHref"
            prefetch
            class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand"
          >
            {{ $t('Žiūrėti visas') }}
            <IFluentArrowUpRight16Regular class="size-4" />
          </SmartLink>
        </div>

        <div class="grid gap-x-8 gap-y-10 pt-10 sm:grid-cols-2 lg:grid-cols-3">
          <NewsCard v-for="related in relatedArticles" :key="related.id" :news="related" />
        </div>
      </div>
    </section>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';

import HighlightsCallout from './HighlightsCallout.vue';
import NewsCard from './NewsCard.vue';

import IFluentArrowLeft16Regular from '~icons/fluent/arrow-left-16-regular';
import IFluentArrowUpRight16Regular from '~icons/fluent/arrow-up-right-16-regular';
import IFluentCalendarLtr16Regular from '~icons/fluent/calendar-ltr-16-regular';
import IFluentClock24Regular from '~icons/fluent/clock-24-regular';
import IFluentOrganization16Regular from '~icons/fluent/organization-16-regular';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { EyebrowLabel, MediaFrame, ReadingSizeControl, ShareButton, TagChip } from '@/Components/Public/Base';
import type { NewsItem } from '@/Types/contentParts';
import { formatStaticTime } from '@/Utils/IntlTime';

/**
 * The news article, in one design.
 *
 * It used to be four near-duplicated layout branches (`modern`/`classic`/`immersive`/`headline`)
 * behind a `news.layout` column that 1189 of 1191 articles left on `modern`. The column is gone
 * with the branches — the design has one article treatment, and four ways to render the same
 * thing is four places for it to drift.
 */
const props = withDefaults(defineProps<{
  article: App.Entities.News & { category?: { name?: string | null } | null; reading_time?: number | null };
  otherLangURL?: string;
  locale?: string;
  /** Author-controlled (Advanced Settings in NewsForm) — the band still renders, just untrailed. */
  showBreadcrumbs?: boolean;
  relatedArticles?: NewsItem[];
  className?: string;
}>(), {
  otherLangURL: undefined,
  locale: 'lt',
  showBreadcrumbs: true,
  relatedArticles: () => [],
  className: undefined,
});

const page = usePage();

const subdomain = computed(() => page.props.tenant?.subdomain ?? 'www');

// `category` arrives as the whole relation (the controller's `only()` resolves it), not as a
// name — the chip wants only the label.
const categoryName = computed(() => props.article.category?.name ?? undefined);

const otherLocale = computed(() => (props.locale === 'lt' ? 'en' : 'lt'));

const isoPublishTime = computed(() => (
  props.article.publish_time ? new Date(props.article.publish_time).toISOString() : undefined
));

const publishedOn = computed(() => (props.article.publish_time
  ? formatStaticTime(
    new Date(props.article.publish_time),
    { year: 'numeric', month: 'long', day: 'numeric' },
    props.locale,
  )
  : ''));

const archiveHref = computed(() => route('newsArchive', {
  subdomain: subdomain.value,
  lang: props.locale,
}));

/**
 * A tag's name is either a plain string or a `{ lt, en }` translation object, depending on
 * whether the payload came through `toArray()` or `toFullArray()`. Resolved once here so the
 * template does not carry the branch.
 */
function tagName(tag: App.Entities.Tag): string {
  if (typeof tag.name === 'string') return tag.name;

  if (tag.name && typeof tag.name === 'object' && !Array.isArray(tag.name)) {
    const names = tag.name as Record<string, string>;

    return names[props.locale] ?? names.lt ?? names.en ?? '';
  }

  return '';
}

const tags = computed(() => (props.article.tags ?? [])
  .filter(tag => tag.alias)
  .map(tag => ({
    id: tag.id,
    name: tagName(tag),
    href: route('newsArchive', {
      lang: props.locale,
      subdomain: subdomain.value,
      tag: tag.alias,
    }),
  })));
</script>

<template>
  <NewsArticleLayout
    :article
    :other-lang-u-r-l="$page.props.otherLangURL ?? undefined"
    :locale="$page.props.app.locale"
    :show-breadcrumbs
    :related-articles
  >
    <RichContentParser :content="article.content?.parts ?? []" :resolved="resolvedParts" />
  </NewsArticleLayout>

  <FeedbackPopover />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import RichContentParser from '@/Components/RichContent/RichContentParser.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import NewsArticleLayout from '@/Components/Public/News/NewsArticleLayout.vue';
import type { ImageData } from '@/Types/media';
import IFluentNews24Regular from '~icons/fluent/news-24-regular';

interface NewsArticleTag {
  id: number;
  name: string;
  alias: string;
}

interface NewsArticle {
  id: number;
  title: string;
  short: string;
  lang: string;
  other_lang_id: number | null;
  permalink: string | null;
  publish_time: string | null;
  content: App.Entities.Content;
  important: number;
  main_points: string | null;
  read_more: string | null;
  show_breadcrumbs: boolean;
  highlights: unknown[] | Record<string, unknown> | null;
  image_author: string | null;
  tags: NewsArticleTag[];
  reading_time: number;
  image: string | null;
  image_media: ImageData | null;
  tenant: string;
}

interface RelatedArticle {
  id: number;
  title: string;
  short: string;
  lang: string;
  image: string | null;
  image_media: ImageData | null;
  permalink: string | null;
  publish_time: string | null;
  url: string;
}

interface ResolvedParts {
  [partId: string]: Record<string, unknown>;
}

const props = defineProps<{
  article: NewsArticle;
  relatedArticles: RelatedArticle[];
  resolvedParts: ResolvedParts;
}>();

const page = usePage();

const showBreadcrumbs = computed(() => props.article.show_breadcrumbs !== false);

// The article masthead owns this trail, so PublicLayout skips its breadcrumb bar.
usePageBreadcrumbs(() => {
  // An empty trail clears the previous page's breadcrumbs.
  if (!showBreadcrumbs.value) return [];

  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Naujienos',
      'newsArchive',
      {
        lang: page.props.app.locale,
        subdomain: page.props.tenant?.subdomain || 'www',
      },
      IFluentNews24Regular,
    ),
    BreadcrumbHelpers.createBreadcrumbItem(props.article.title),
  ]);
}, { placement: 'band' });
</script>

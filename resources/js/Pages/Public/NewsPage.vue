<template>
  <!-- The title band, the share row and the related grid all belong to the article, so the page
       itself is only the composition. -->
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
import type { NewsItem } from '@/Types/contentParts';
import IFluentNews24Regular from '~icons/fluent/news-24-regular';

const props = defineProps<{
  article: App.Entities.News & { reading_time?: number | null };
  relatedArticles?: NewsItem[];
  /** Server-resolved dynamic blocks (link-list, event-list, …) keyed by content-part id. */
  resolvedParts?: Record<number, unknown>;
}>();

const page = usePage();

// An author can hide the breadcrumb trail on a news article (Advanced Settings in
// NewsForm) — the title band still carries the headline, it just goes untrailed.
const showBreadcrumbs = computed(() => props.article.show_breadcrumbs !== false);

// Set breadcrumbs for news article page.
//
// `placement: 'band'` — the trail belongs inside the article's ink masthead, so PublicLayout
// skips the bar it draws above page content for every other route. One trail, not two.
usePageBreadcrumbs(() => {
  // Author disabled breadcrumbs for this article — an empty trail clears the previous page's.
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

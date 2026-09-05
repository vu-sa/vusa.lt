<template>
  <!-- No wrapping `.wrapper` here — PublicLayout already wraps page content in one;
       nesting a second grid doubled the gutters/padding for everything below.

       The title band, the share row and the related grid all belong to the article, so the page
       itself is only the composition. -->
  <NewsArticleLayout
    :article
    :other-lang-u-r-l="$page.props.otherLangURL ?? undefined"
    :locale="$page.props.app.locale"
    :show-breadcrumbs="showBreadcrumbs"
    :related-articles="relatedArticles"
  >
    <RichContentParser :content="article.content?.parts ?? []" :resolved="resolvedParts" />
  </NewsArticleLayout>

  <FeedbackPopover />
</template>

<script setup lang="ts">
import { computed, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

import RichContentParser from '@/Components/RichContent/RichContentParser.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import { usePageBreadcrumbs, useBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import NewsArticleLayout from '@/Components/Public/News/NewsArticleLayout.vue';
import type { NewsItem } from '@/Types/contentParts';
import IFluentNews24Regular from '~icons/fluent/news-24-regular';

const props = defineProps<{
  article: App.Entities.News & { category?: { name?: string | null } | null; reading_time?: number | null };
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
  // Author disabled breadcrumbs for this article — return nothing so the setter skips.
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

// usePageBreadcrumbs persists breadcrumbs across navigation and only sets when the
// getter is non-empty, so a suppressed article would otherwise inherit the *previous*
// page's trail. Explicitly clear the global state whenever breadcrumbs are off.
const { clear: clearBreadcrumbs } = useBreadcrumbs();
watchEffect(() => {
  if (!showBreadcrumbs.value) clearBreadcrumbs();
});
</script>

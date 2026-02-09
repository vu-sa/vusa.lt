<template>
  <div class="wrapper">
    <!-- News article with dynamic layout based on article settings -->
    <NewsArticleLayout
      :article
      :other-lang-u-r-l="$page.props.otherLangURL"
      :locale="$page.props.app.locale"
      :layout="(article.layout as 'modern' | 'classic' | 'immersive' | 'headline') || 'modern'"
      class-name="mb-8 md:mb-16"
    >
      <RichContentParser :content="article.content?.parts ?? []" />
    </NewsArticleLayout>

    <!-- Related Articles Section -->
    <section v-if="relatedArticles && relatedArticles.length > 0" :class="['mt-8 mb-12', relatedArticlesContainerClass]">
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100 p-6 ring-1 ring-zinc-200/50 dark:from-zinc-900 dark:to-zinc-800 dark:ring-zinc-700/50">
        <!-- Decorative blur elements -->
        <div class="absolute -right-16 -top-16 size-48 rounded-full bg-vusa-red/5 blur-3xl" />
        <div class="absolute -bottom-8 -left-8 size-32 rounded-full bg-vusa-yellow/5 blur-3xl" />

        <div class="relative">
          <h2 class="text-xl font-semibold mb-5 text-zinc-900 dark:text-zinc-50">
            {{ $t('Skaityti daugiau') }}
          </h2>
          <ul class="space-y-3">
            <li v-for="related in relatedArticles" :key="related.id" class="flex items-start gap-3">
              <span class="mt-2 size-1.5 rounded-full bg-vusa-red/60 flex-shrink-0" />
              <Link
                :href="related.url"
                class="group flex flex-1 items-baseline justify-between gap-4 hover:text-vusa-red transition-colors"
              >
                <span class="font-heading group-hover:underline text-zinc-800 dark:text-zinc-200">{{ related.title }}</span>
                <time
                  :datetime="related.publish_time"
                  class="text-sm text-zinc-500 dark:text-zinc-400 whitespace-nowrap"
                >
                  {{ formatDate(related.publish_time) }}
                </time>
              </Link>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <FeedbackPopover />
  </div>
</template>

<script setup lang="ts">
// No longer need computed, onMounted, onUnmounted - usePageBreadcrumbs handles this
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import RichContentParser from '@/Components/RichContentParser.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import NewsArticleLayout from '@/Components/Public/News/NewsArticleLayout.vue';
import IFluentNews24Regular from '~icons/fluent/news-24-regular';

interface RelatedArticle {
  id: number;
  title: string;
  permalink: string;
  publish_time: string;
  url: string;
}

const props = defineProps<{
  article: App.Entities.News;
  relatedArticles?: RelatedArticle[];
}>();

const page = usePage();

// Container class for related articles section - matches the article layout width
const relatedArticlesContainerClass = computed(() => {
  const layout = props.article.layout || 'modern';
  const layoutStyles: Record<string, string> = {
    modern: 'max-w-3xl mx-auto',
    classic: 'max-w-4xl mx-auto',
    immersive: 'max-w-3xl mx-auto px-4', // Immersive content uses max-w-3xl
    headline: 'max-w-3xl', // Left-aligned
  };
  return layoutStyles[layout] || 'max-w-3xl mx-auto';
});

// Format date for display
const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString(page.props.app.locale === 'lt' ? 'lt-LT' : 'en-GB', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Set breadcrumbs for news article page
usePageBreadcrumbs(() => {
  const items = [];

  // News archive link
  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Naujienos',
      'newsArchive',
      {
        newsString: page.props.app.locale === 'lt' ? 'naujienos' : 'news',
        lang: page.props.app.locale,
        subdomain: page.props.tenant?.subdomain || 'www',
      },
      IFluentNews24Regular,
    ),
  );

  // Current news article
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(props.article.title),
  );

  return BreadcrumbHelpers.publicContent(items);
});
</script>

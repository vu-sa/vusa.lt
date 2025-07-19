<template>
  <div class="wrapper">
    <!-- News article with fixed modern layout -->
    <NewsArticleLayout 
      :article="article" 
      :other-lang-URL="$page.props.otherLangURL"
      :locale="$page.props.app.locale"
      layout="modern"
      className="mb-8 md:mb-16"
    >
      <RichContentParser :content="article.content?.parts" />
    </NewsArticleLayout>
    
    <FeedbackPopover />
  </div>
</template>

<script setup lang="ts">
// No longer need computed, onMounted, onUnmounted - usePageBreadcrumbs handles this
import { usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";

import RichContentParser from "@/Components/RichContentParser.vue";
import FeedbackPopover from "@/Components/Public/FeedbackPopover.vue";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import NewsArticleLayout from "@/Components/Public/News/NewsArticleLayout.vue";
import IFluentNews24Regular from "~icons/fluent/news-24-regular";

const props = defineProps<{
  article: App.Entities.News;
}>();

const page = usePage();

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
        subdomain: page.props.tenant?.subdomain || 'www'
      },
      IFluentNews24Regular
    )
  );
  
  // Current news article
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(props.article.title)
  );
  
  return BreadcrumbHelpers.publicContent(items);
});
</script>

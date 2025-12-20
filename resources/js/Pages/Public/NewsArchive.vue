<template>
  <div>
    <!-- Page header with breadcrumb -->
    <header class="mb-8 pt-6 md:pt-10">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-50 m-0">
            {{ $t('Naujienų archyvas') }}
          </h1>
          
          <!-- Current tag filter display -->
          <div v-if="props.currentTag" class="mt-3 flex items-center gap-2">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('Filtruojama pagal žymą:') }}</span>
            <Button 
              size="sm"
              class="rounded-full"
            >
              {{ typeof props.currentTag.name === 'object' ? (props.currentTag.name[$page.props.app.locale] || props.currentTag.name.lt || props.currentTag.name.en) : props.currentTag.name }}
            </Button>
            <SmartLink 
              :href="route('newsArchive', {
                lang: $page.props.app.locale,
                newsString: $page.props.app.locale === 'lt' ? 'naujienos' : 'news',
                subdomain: $page.props.tenant?.subdomain || 'www'
              })"
              class="plain"
            >
              <Button size="icon-sm" variant="ghost" class="rounded-full">
                <XIcon class="h-4 w-4" />
              </Button>
            </SmartLink>
          </div>
        </div>
        
        <!-- Pagination statistics -->
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ $t('Rodoma :from - :to iš :total', {
            from: news.from?.toString() || '0',
            to: news.to?.toString() || '0',
            total: news.total?.toString() || '0'
          }) }}
        </div>
      </div>
    </header>
    
    <!-- News grid with responsive layout -->
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <SmartLink 
        v-for="item in news.data" 
        :key="item.id" 
        class="plain"
        prefetch
        :href="route('news', {
          lang: item.lang,
          news: item.permalink ?? '',
          newsString: 'naujiena',
          subdomain: $page.props.tenant?.subdomain ?? 'www',
        })"
      >
        <NewsCard :news="item" :locale="$page.props.app.locale" />
      </SmartLink>
    </div>
    
    <!-- Empty state when no news items available -->
    <div v-if="news.data.length === 0" class="my-20 text-center">
      <div class="flex flex-col items-center gap-4">
        <IFluentNews24Regular class="h-16 w-16 text-zinc-300 dark:text-zinc-600" />
        <h3 class="text-xl font-semibold text-zinc-700 dark:text-zinc-300">
          {{ $t('Nėra naujienų') }}
        </h3>
        <p class="text-zinc-500 dark:text-zinc-400">
          {{ $t('Šiuo metu nėra publikuotų naujienų. Užsukite vėliau.') }}
        </p>
      </div>
    </div>
    
    <!-- Pagination with improved implementation using Shadcn components -->
    <div v-if="news.last_page > 1" class="mt-12 flex justify-center">
      <Pagination :items-per-page="news.per_page" :total="news.total">
        <PaginationContent>
          <PaginationItem v-if="news.current_page > 1" :value="1">
            <PaginationFirst @click.prevent="handlePageChange(1)" />
          </PaginationItem>
          
          <PaginationItem v-if="news.current_page > 1" :value="news.current_page - 1">
            <PaginationPrevious @click.prevent="handlePageChange(news.current_page - 1)">
              <ChevronLeftIcon class="h-4 w-4" />
              <span class="sr-only">{{ $t('Ankstesnis puslapis') }}</span>
            </PaginationPrevious>
          </PaginationItem>
          
          <!-- First page -->
          <PaginationItem 
            v-if="news.current_page > 3" 
            :value="1" 
            :is-active="news.current_page === 1" 
            @click.prevent="handlePageChange(1)"
          >
            1
          </PaginationItem>
          
          <!-- Show ellipsis if current page is more than 4 from start -->
          <PaginationEllipsis v-if="news.current_page > 4" />
          
          <!-- Show 2-3 pages before current page -->
          <PaginationItem 
            v-for="page in getPagesBefore()" 
            :key="`before-${page}`"
            :value="page"
            :is-active="news.current_page === page"
            @click.prevent="handlePageChange(page)"
          >
            {{ page }}
          </PaginationItem>
          
          <!-- Current page -->
          <PaginationItem 
            :value="news.current_page"
            :is-active="true"
            @click.prevent="handlePageChange(news.current_page)"
          >
            {{ news.current_page }}
          </PaginationItem>
          
          <!-- Show 2-3 pages after current page -->
          <PaginationItem 
            v-for="page in getPagesAfter()" 
            :key="`after-${page}`"
            :value="page"
            :is-active="news.current_page === page"
            @click.prevent="handlePageChange(page)"
          >
            {{ page }}
          </PaginationItem>
          
          <!-- Show ellipsis if current page is more than 4 from end -->
          <PaginationEllipsis v-if="news.current_page < news.last_page - 3" />
          
          <!-- Last page -->
          <PaginationItem 
            v-if="news.current_page < news.last_page - 2" 
            :value="news.last_page"
            :is-active="news.current_page === news.last_page"
            @click.prevent="handlePageChange(news.last_page)"
          >
            {{ news.last_page }}
          </PaginationItem>
          
          <PaginationItem v-if="news.current_page < news.last_page" :value="news.current_page + 1">
            <PaginationNext @click.prevent="handlePageChange(news.current_page + 1)">
              <span class="sr-only">{{ $t('Kitas puslapis') }}</span>
              <ChevronRightIcon class="h-4 w-4" />
            </PaginationNext>
          </PaginationItem>
          
          <PaginationItem v-if="news.current_page < news.last_page" :value="news.last_page">
            <PaginationLast @click.prevent="handlePageChange(news.last_page)" />
          </PaginationItem>
        </PaginationContent>
      </Pagination>
    </div>
  </div>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
// onMounted/onUnmounted no longer needed - usePageBreadcrumbs handles lifecycle
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { XIcon, ChevronLeftIcon, ChevronRightIcon } from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import NewsCard from "@/Components/Public/News/NewsCard.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";
import { 
  Pagination, 
  PaginationContent, 
  PaginationItem, 
  PaginationNext, 
  PaginationPrevious,
  PaginationEllipsis,
  PaginationFirst,
  PaginationLast
} from "@/Components/ui/pagination";

const props = defineProps<{
  news: PaginatedModels<App.Entities.News>;
  currentTag?: App.Entities.Tag | null;
}>();

// Handle pagination with improved UX
const handlePageChange = (page: number) => {
  // Show loading state on the entire list
  const loadingOverlay = document.createElement('div');
  loadingOverlay.className = 'fixed inset-0 bg-white/50 dark:bg-zinc-900/50 z-50 flex items-center justify-center';
  loadingOverlay.innerHTML = '<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600/80"></div>';
  document.body.appendChild(loadingOverlay);
  
  router.reload({
    data: {
      page: page,
    },
    only: ["news"],
    onSuccess: () => {
      document.body.removeChild(loadingOverlay);
      
      // Scroll to top with smooth animation
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    },
    onError: () => {
      document.body.removeChild(loadingOverlay);
    }
  });
};

// Get 2-3 pages before current page (if available)
const getPagesBefore = () => {
  const pages: number[] = [];
  const current = props.news.current_page;
  
  // Show up to 3 pages before current page
  for (let i = Math.max(1, current - 3); i < current; i++) {
    pages.push(i);
  }
  
  return pages;
};

// Get 2-3 pages after current page (if available)
const getPagesAfter = () => {
  const pages: number[] = [];
  const current = props.news.current_page;
  const lastPage = props.news.last_page;
  
  // Show up to 3 pages after current page
  for (let i = current + 1; i <= Math.min(lastPage, current + 3); i++) {
    pages.push(i);
  }
  
  return pages;
};

// Clear breadcrumbs for news archive page (no specific breadcrumbs needed)
usePageBreadcrumbs([]);
</script>

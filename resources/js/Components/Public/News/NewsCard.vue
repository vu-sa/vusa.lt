<template>
  <article class="group relative flex flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition-all duration-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
    <!-- Image section -->
    <div class="relative aspect-[16/9] w-full overflow-hidden">
      <img :src="news.image" :alt="news.title" 
        class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-105" />
        
      <!-- Date badge - only shown if publish_time exists -->
      <div v-if="news.publish_time" class="absolute bottom-3 left-3 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium shadow-sm backdrop-blur-sm dark:bg-zinc-800/90">
        <div class="flex items-center">
          <IFluentCalendarLtr16Regular class="mr-1.5 h-3.5 w-3.5 text-red-500 dark:text-red-400" />
          <time :datetime="formatISODate(news.publish_time)">
            {{ formatStaticTime(new Date(news.publish_time),
                { year: "numeric", month: "short", day: "numeric" },
                locale) }}
          </time>
        </div>
      </div>
    </div>
    
    <!-- Content section -->
    <div class="flex flex-1 flex-col p-4">
      <!-- Title -->
      <h3 class="mb-2 text-base font-bold leading-tight text-zinc-900 line-clamp-2 dark:text-zinc-50 mt-0">
        {{ news.title }}
      </h3>
      
      <!-- Summary if available -->
      <p v-if="news.short" class="mb-4 text-sm text-zinc-600 line-clamp-3 dark:text-zinc-300" v-html="news.short"></p>
      
      <!-- Footer: tenant info + read more -->
      <div class="mt-auto flex items-center justify-between pt-2">
        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ news.tenant?.shortname || news.tenant }}</span>
        
        <span class="inline-flex items-center gap-1 text-sm font-medium text-red-600/80 transition-colors group-hover:text-red-700 dark:text-red-400 dark:group-hover:text-red-300">
          {{ $t('Skaityti') }}
          <IFluentArrowRight16Regular class="h-3.5 w-3.5 transform transition-transform group-hover:translate-x-0.5" />
        </span>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { formatStaticTime } from "@/Utils/IntlTime";

const props = defineProps<{
  news: App.Entities.News;
  locale?: string;
}>();

// Default locale if not provided
const locale = props.locale || 'lt';

// Formatter for ISO date - safely handles null/undefined values
function formatISODate(date: string | number | null | undefined) {
  return date ? new Date(date).toISOString() : '';
}
</script>
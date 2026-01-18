<template>
  <Card :class="dashboardCardClasses" role="region" :aria-label="$t('Naujienos')">
    <!-- Decorative accent -->
    <div class="absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45 bg-blue-400/30 dark:bg-blue-500/20"
      aria-hidden="true" />

    <CardHeader class="pb-2 relative z-10">
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2 text-base">
          <NewspaperIcon class="h-5 w-5 text-blue-600 dark:text-blue-400" aria-hidden="true" />
          {{ $t('Naujienos') }}
        </CardTitle>
        <a :href="route('newsArchive', {
          subdomain: $page.props.tenant?.subdomain ?? 'www',
          lang: locale === 'lt' ? 'lt' : 'en',
          newsString: locale === 'lt' ? 'naujienos' : 'news'
        })" class="text-xs text-primary hover:underline">
          {{ $t('Visos') }} →
        </a>
      </div>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 pt-0">
      <div class="flex flex-col space-y-1">
        <a v-for="news in newsList" :key="news.id" :href="route('news', {
          subdomain: news.tenant?.alias ?? $page.props.tenant?.subdomain
            ?? 'www',
          lang: news.lang,
          newsString: locale === 'lt' ? 'naujiena' : 'news',
          news: news.permalink
        })"
          class="flex items-center gap-3 py-2 px-2 -mx-2 rounded-md transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700/50">
          <!-- Image thumbnail -->
          <div class="overflow-hidden rounded-md aspect-4/3 shrink-0 bg-muted" style="width: 70px;">
            <img :src="String(news.image)" :alt="news.title" loading="lazy"
              class="w-full h-full object-cover" width="70" height="53">
          </div>
          <!-- Content -->
          <div class="flex flex-col min-w-0 flex-1">
            <span
              class="text-zinc-800 dark:text-zinc-200 font-semibold text-sm leading-tight line-clamp-2 hover:text-vusa-red transition-colors">
              {{ news.title }}
            </span>
            <span v-if="news.publish_time" class="text-zinc-500 dark:text-zinc-400 text-xs mt-1">
              {{ formatDate(news.publish_time) }}
            </span>
          </div>
          <!-- Arrow indicator -->
          <ChevronRightIcon class="h-4 w-4 shrink-0 text-muted-foreground" />
        </a>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { format, parseISO } from "date-fns";
import { lt, enUS } from "date-fns/locale";
import { Newspaper as NewspaperIcon, ChevronRight as ChevronRightIcon } from "lucide-vue-next";

import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { dashboardCardClasses } from "@/Composables/useDashboardCardStyles";

const props = defineProps<{
  newsList: App.Entities.News[];
}>();

const page = usePage();
const locale = computed(() => page.props.app.locale);
const dateLocale = computed(() => locale.value === 'lt' ? lt : enUS);

// Format date
const formatDate = (dateStr: string | Date | null) => {
  if (!dateStr) return '';
  try {
    const date = typeof dateStr === 'string' ? parseISO(dateStr) : dateStr;
    return format(date, 'MMM d, yyyy', { locale: dateLocale.value });
  } catch {
    return '';
  }
};
</script>

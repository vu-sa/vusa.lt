<template>
  <div v-if="events.length > 0 || news.length > 0" class="grid gap-6 lg:grid-cols-2" data-slot="site-content">
    <HomeSection :title="$t('Artimiausi renginiai')" :empty="events.length === 0">
      <ul class="divide-y divide-border border-y border-border">
        <li v-for="event in events" :key="event.id">
          <Link :href="route('calendar.edit', event.id)" prefetch class="flex items-center gap-3 px-1 py-3 hover:bg-secondary pointer-coarse:py-4">
            <span class="min-w-0 flex-1 truncate font-medium">{{ event.title }}</span>
            <span v-if="event.date" class="shrink-0 text-sm text-muted-foreground">{{ formatNearDate(event.date) }}</span>
          </Link>
        </li>
      </ul>
    </HomeSection>

    <HomeSection :title="$t('Naujausios naujienos')" :empty="news.length === 0">
      <ul class="divide-y divide-border border-y border-border">
        <li v-for="item in news" :key="item.id">
          <Link :href="route('news.edit', item.id)" prefetch class="flex items-center gap-3 px-1 py-3 hover:bg-secondary pointer-coarse:py-4">
            <span class="min-w-0 flex-1 truncate font-medium">{{ item.title }}</span>
            <span v-if="item.date" class="shrink-0 text-sm text-muted-foreground">{{ formatNearDate(item.date) }}</span>
          </Link>
        </li>
      </ul>
    </HomeSection>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import HomeSection from './HomeSection.vue';
import type { HomeContentItem } from './types';

import { formatNearDate } from '@/Utils/dateTime';

defineProps<{
  events: HomeContentItem[];
  news: HomeContentItem[];
}>();
</script>

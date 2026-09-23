<template>
  <div v-if="events.length > 0 || news.length > 0" class="grid gap-10 lg:grid-cols-2 lg:gap-16" data-slot="site-content">
    <OverviewSection v-if="events.length > 0" :title="$t('Artimiausi renginiai')">
      <div class="divide-y divide-border">
        <EventCard v-for="event in events" :key="event.id" :event variant="compact" />
      </div>
    </OverviewSection>

    <OverviewSection v-if="news.length > 0" :title="$t('Naujausios naujienos')">
      <div class="divide-y divide-border">
        <NewsCard v-for="item in news" :key="item.id" :news="item" size="compact" />
      </div>
    </OverviewSection>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import EventCard from '@/Components/Calendar/EventCard.vue';
import NewsCard from '@/Components/News/NewsCard.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import type { HomeNewsPreview } from './types';

defineProps<{
  events: App.Entities.Calendar[];
  news: HomeNewsPreview[];
}>();
</script>

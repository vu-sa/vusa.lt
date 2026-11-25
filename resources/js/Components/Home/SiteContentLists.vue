<template>
  <OverviewSection
    v-if="items.length > 0"
    :title="$t('home.site_content_title')"
    :icon="Globe"
    variant="home"
    data-slot="site-content"
  >
    <CollectionRowList :items frame="attached" :item-key="item => item.key">
      <template #default="{ item }">
        <!-- Native links: these open the public site, outside the admin's Inertia app. -->
        <component
          :is="item.href ? 'a' : 'div'"
          :href="item.href ?? undefined"
          :class="ROW_LINK_CLASS"
          :data-slot="item.kind === 'event' ? 'site-content-event' : 'site-content-news'"
        >
          <component :is="item.kind === 'event' ? CalendarDays : Newspaper" class="mt-0.5 size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <span class="min-w-0 flex-1">
            <span class="line-clamp-2 text-pretty font-semibold text-foreground">{{ item.title }}</span>
            <span class="mt-0.5 block text-xs text-muted-foreground">
              {{ item.kind === 'event' ? $t('Renginys') : $t('Naujiena') }}
              <span aria-hidden="true">·</span>
              <time :datetime="item.date">{{ formatNearDate(item.date, { thresholdDays: 30 }) }}</time>
            </span>
          </span>
        </component>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, Globe, Newspaper } from '@lucide/vue';
import { computed } from 'vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { safeHref } from '@/Utils/safeHref';

import type { HomeCalendarPreview, HomeNewsPreview } from './types';

const props = defineProps<{
  events: HomeCalendarPreview[];
  news: HomeNewsPreview[];
}>();

const { formatNearDate } = useDateFormatter();

// Upcoming events first, in the order the server sorted them, then the newest news.
const items = computed(() => [
  ...props.events.map(event => ({
    key: `event:${event.id}`,
    kind: 'event' as const,
    title: event.title,
    date: event.date,
    href: safeHref(event.public_url ?? undefined) ?? null,
  })),
  ...props.news.map(item => ({
    key: `news:${item.id}`,
    kind: 'news' as const,
    title: item.title,
    date: item.publish_time,
    href: safeHref(item.public_url ?? undefined) ?? null,
  })),
]);
</script>

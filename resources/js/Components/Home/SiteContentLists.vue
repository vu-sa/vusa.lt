<template>
  <OverviewSection
    v-if="items.length > 0"
    :title="$t('home.site_content_title')"
    :icon="Globe"
    variant="home" content-ruled
    data-slot="site-content"
  >
    <CollectionRowList :items frame="box" :item-key="item => item.key">
      <template #default="{ item }">
        <!-- Native links: these open the public site, outside the admin's Inertia app. -->
        <component
          :is="item.href ? 'a' : 'div'"
          :href="item.href ?? undefined"
          :class="cn(ROW_LINK_CLASS, 'group')"
          :data-slot="item.kind === 'event' ? 'site-content-event' : 'site-content-news'"
        >
          <!-- The thumbnail carries the kind: a corner chip on a photo, the icon itself on an empty tile. -->
          <span class="relative aspect-[4/3] w-20 shrink-0 overflow-hidden rounded-md bg-secondary sm:w-24" data-slot="site-content-thumb">
            <img
              v-if="hasImage(item)"
              :src="item.image!"
              alt=""
              loading="lazy"
              class="size-full object-cover transition-transform duration-300 group-hover:scale-103"
              @error="failedImages.add(item.key)"
            >
            <span v-else class="flex size-full items-center justify-center text-muted-foreground">
              <component :is="kindIcon(item.kind)" class="size-5" aria-hidden="true" />
            </span>
            <span class="pointer-events-none absolute inset-0 rounded-md ring-1 ring-inset ring-foreground/8" aria-hidden="true" />
            <span v-if="hasImage(item)" class="absolute bottom-1 left-1 flex size-6 items-center justify-center rounded-sm bg-background/90 text-foreground" aria-hidden="true">
              <component :is="kindIcon(item.kind)" class="size-3.5" />
            </span>
          </span>
          <span class="min-w-0 flex-1 self-center">
            <span class="line-clamp-2 text-pretty font-semibold text-foreground">{{ item.title }}</span>
            <span class="mt-0.5 flex flex-wrap gap-x-1.5 text-xs text-muted-foreground">
              <span>{{ item.kind === 'event' ? $t('Renginys') : $t('Naujiena') }}</span>
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
import { computed, reactive } from 'vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { safeHref } from '@/Utils/safeHref';
import { cn } from '@/Utils/Shadcn/utils';

import type { HomeCalendarPreview, HomeNewsPreview } from './types';

const props = defineProps<{
  events: HomeCalendarPreview[];
  news: HomeNewsPreview[];
}>();

const { formatNearDate } = useDateFormatter();

const kindIcon = (kind: 'event' | 'news') => (kind === 'event' ? CalendarDays : Newspaper);

// A missing file falls back to the icon tile rather than a broken-image glyph.
const failedImages = reactive(new Set<string>());
const hasImage = (item: { key: string; image: string | null }) => Boolean(item.image) && !failedImages.has(item.key);

// Upcoming events first, in the order the server sorted them, then the newest news.
const items = computed(() => [
  ...props.events.map(event => ({
    key: `event:${event.id}`,
    kind: 'event' as const,
    title: event.title,
    date: event.date,
    image: event.image ?? null,
    href: safeHref(event.public_url ?? undefined) ?? null,
  })),
  ...props.news.map(item => ({
    key: `news:${item.id}`,
    kind: 'news' as const,
    title: item.title,
    date: item.publish_time,
    image: item.image ?? null,
    href: safeHref(item.public_url ?? undefined) ?? null,
  })),
]);
</script>

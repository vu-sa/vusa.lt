<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :background="element.options?.background ?? 'none'" :padding="element.options?.padding ?? 'lg'"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <!-- Grouped cards — one RCFeatureCard per group (tenant), events as a footer link
         list. This is the SummerCampCard shape, generalized. -->
    <div v-if="hasGroups && style === 'cards'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <RCFeatureCard
        v-for="group in resolved!.groups" :key="group.key"
        :title="group.label"
        :cover-image="coverImageFor(group.items)"
        :cover-alt="group.label"
        :badge="group.items.length > 1 ? group.items.length : null"
      >
        <template #cover-fallback>
          <IFluentTent24Regular class="size-10 text-vusa-red/50" />
        </template>
        <ul class="contents">
          <li v-for="event in group.items" :key="event.id">
            <SmartLink
              :href="event.href"
              class="relative z-20 -mx-2.5 flex items-start gap-3 rounded-xl p-2.5 transition-colors hover:bg-white/70 dark:hover:bg-zinc-800/60"
            >
              <div class="flex min-w-11 flex-col items-center justify-center rounded-lg bg-vusa-red/10 px-2 py-1.5 text-center text-vusa-red dark:bg-vusa-red/20">
                <span class="text-[9px] font-semibold uppercase leading-none">{{ formatMonthShort(event.date ?? undefined, locale) }}</span>
                <span class="mt-0.5 text-base font-bold leading-none tabular-nums">{{ dayOfMonth(event.date) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-zinc-800 transition-colors group-hover:text-vusa-red dark:text-zinc-200">
                  {{ dateSpan(event).primary }}
                </p>
                <p v-if="event.location" class="mt-0.5 flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                  <IFluentLocation20Regular class="size-3 shrink-0" />
                  <span class="truncate">{{ event.location }}</span>
                </p>
              </div>
              <IFluentChevronRight12Regular class="mt-2 size-3 shrink-0 text-zinc-300 transition-colors group-hover:text-vusa-red dark:text-zinc-600" />
            </SmartLink>
          </li>
        </ul>
      </RCFeatureCard>
    </div>

    <!-- Ungrouped cards -->
    <div v-else-if="style === 'cards'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <RCFeatureCard
        v-for="event in resolved!.items" :key="event.id"
        :title="event.title"
        :cover-image="event.imageUrl"
        :cover-alt="event.title"
        :meta="dateSpan(event).primary"
        :href="event.href"
      >
        <template #cover-fallback>
          <IFluentTent24Regular class="size-10 text-vusa-red/50" />
        </template>
        <p v-if="event.location" class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
          <IFluentLocation20Regular class="size-3 shrink-0" />
          <span class="truncate">{{ event.location }}</span>
        </p>
      </RCFeatureCard>
    </div>

    <!-- Flat chronological list -->
    <ul v-else class="divide-y divide-zinc-200/60 dark:divide-zinc-800">
      <li v-for="event in resolved!.items" :key="event.id">
        <SmartLink :href="event.href" class="group flex items-center justify-between gap-4 py-3">
          <div class="min-w-0">
            <p class="truncate font-medium text-zinc-800 transition-colors group-hover:text-vusa-red dark:text-zinc-200">
              {{ event.title }}
            </p>
            <p v-if="event.location" class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ event.location }}</p>
          </div>
          <span class="shrink-0 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ dateSpan(event).primary }}</span>
        </SmartLink>
      </li>
    </ul>

    <!-- Empty state, mirroring SummerCamps.vue's -->
    <div
      v-if="isEmpty"
      class="rounded-2xl bg-zinc-50 py-16 text-center ring-1 ring-zinc-200/50 dark:bg-zinc-800/50 dark:ring-zinc-700/50"
    >
      <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
        <IFluentTent24Regular class="size-6 text-zinc-400" />
      </div>
      <p class="text-zinc-600 dark:text-zinc-400">
        {{ element.options?.emptyMessage || $t('rich-content.event_list_empty') }}
      </p>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
/**
 * Displays the `event-list` block's server-resolved payload (see `EventListResolver`).
 * `groupBy: 'tenant'` reproduces `SummerCampCard`'s card shape generically — the two
 * should stay visually identical, since `SummerCampCard` is this display's namesake.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';

import type { EventListResolved, EventListResolvedItem } from '@/Types/contentParts';
import RCSection from '../RCSection.vue';
import RCFeatureCard from '../RCFeatureCard.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { formatEventDateSpan, formatMonthShort } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  anchorId?: number | null;
  resolved?: EventListResolved | null;
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const style = computed(() => props.element.options?.style ?? 'cards');
const hasGroups = computed(() => (props.resolved?.groups?.length ?? 0) > 0);
const isEmpty = computed(() => (props.resolved?.meta?.total ?? 0) === 0);

const dateSpan = (event: EventListResolvedItem) =>
  formatEventDateSpan(event.date ?? '', event.endDate, { allDay: event.isAllDay, locale: locale.value });

const dayOfMonth = (date: string | null) => (date ? new Date(date).getDate() : '');

function coverImageFor(items: EventListResolvedItem[]): string | null {
  return items.find(item => item.imageUrl)?.imageUrl ?? null;
}
</script>

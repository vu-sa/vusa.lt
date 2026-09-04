<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :band :align="element.options?.align ?? 'center'"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <!-- Grouped cards — one RCFeatureCard per group (tenant), events as a footer link
         list. This is the SummerCampCard shape, generalized. -->
    <div v-if="hasGroups && style === 'cards'" class="grid gap-6" :class="smartGridCols(groups.length)">
      <RCFeatureCard
        v-for="group in groups" :key="group.key"
        :title="group.label"
        :cover-image="coverImageFor(group.items)"
        :cover-alt="group.label"
        :badge="group.items.length > 1 ? group.items.length : null"
      >
        <template #cover-fallback>
          <IFluentTent24Regular class="size-10 text-brand/50" />
        </template>
        <ul class="contents">
          <li v-for="event in group.items" :key="event.id">
            <SmartLink
              :href="event.href"
              class="relative z-20 -mx-2.5 flex items-start gap-3 p-2.5 transition-colors hover:bg-secondary/60"
            >
              <div class="flex min-w-11 flex-col items-center justify-center border border-border px-2 py-1.5 text-center text-brand">
                <span class="text-[0.5625rem] font-semibold uppercase leading-none">{{ formatMonthShort(event.date ?? undefined, locale) }}</span>
                <span class="mt-0.5 text-base font-bold leading-none tabular-nums">{{ dayOfMonth(event.date) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-foreground transition-colors group-hover:text-brand">
                  {{ dateSpan(event).primary }}
                </p>
                <p v-if="event.location" class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                  <IFluentLocation20Regular class="size-3 shrink-0" />
                  <span class="truncate">{{ event.location }}</span>
                </p>
              </div>
              <IFluentChevronRight12Regular class="mt-2 size-3 shrink-0 text-muted-foreground transition-colors group-hover:text-brand" />
            </SmartLink>
          </li>
        </ul>
      </RCFeatureCard>
    </div>

    <!-- Ungrouped cards -->
    <div v-else-if="style === 'cards'" class="grid gap-6" :class="smartGridCols(items.length)">
      <RCFeatureCard
        v-for="event in items" :key="event.id"
        :title="event.title"
        :cover-image="event.imageUrl"
        :cover-alt="event.title"
        :meta="dateSpan(event).primary"
        :href="event.href"
      >
        <template #cover-fallback>
          <IFluentTent24Regular class="size-10 text-brand/50" />
        </template>
        <p v-if="event.location" class="flex items-center gap-1 text-xs text-muted-foreground">
          <IFluentLocation20Regular class="size-3 shrink-0" />
          <span class="truncate">{{ event.location }}</span>
        </p>
      </RCFeatureCard>
    </div>

    <!-- Flat chronological list — the design's scannable event row: a ruled date block, the
         title, and its when/where on one line, separated by hairlines rather than boxed. -->
    <ul v-else>
      <li v-for="event in items" :key="event.id">
        <SmartLink
          :href="event.href"
          class="group flex items-center gap-4 border-b border-border py-5 transition-colors hover:bg-background sm:gap-6"
        >
          <div class="flex w-14 shrink-0 flex-col items-center justify-center border border-border bg-background py-2 text-foreground transition-colors group-hover:border-brand sm:w-16">
            <span class="text-2xl font-bold leading-none tabular-nums">{{ dayOfMonth(event.date) }}</span>
            <span class="mt-1 text-[0.625rem] font-bold uppercase tracking-wide text-muted-foreground">
              {{ formatMonthShort(event.date ?? undefined, locale) }}
            </span>
          </div>

          <div class="min-w-0 flex-1">
            <h3 class="text-pretty font-bold leading-snug text-foreground transition-colors group-hover:text-brand">
              {{ event.title }}
            </h3>
            <div class="mt-1.5 flex flex-col gap-1 text-sm text-muted-foreground sm:flex-row sm:items-center sm:gap-4">
              <span class="flex items-center gap-1.5">
                <IFluentCalendarLtr20Regular class="size-3.5 shrink-0" />
                {{ dateSpan(event).primary }}
              </span>
              <span v-if="event.location" class="flex items-center gap-1.5">
                <IFluentLocation20Regular class="size-3.5 shrink-0" />
                <span class="truncate">{{ event.location }}</span>
              </span>
            </div>
          </div>

          <IFluentArrowRight16Regular class="hidden size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-brand sm:block" />
        </SmartLink>
      </li>
    </ul>

    <!-- Empty state: a ruled panel, same language as the rows above it. -->
    <div v-if="isEmpty" class="border border-border bg-secondary/40 py-16 text-center">
      <IFluentTent24Regular class="mx-auto mb-4 size-8 text-muted-foreground" />
      <p class="text-muted-foreground">
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
import { smartGridCols } from '../gridStacking';
import type { BandResolution } from '../bandLayout';

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  anchorId?: number | null;
  resolved?: EventListResolved | null;
  band?: BandResolution;
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const style = computed(() => props.element.options?.style ?? 'cards');
// `resolved` is `undefined` in every editor-preview surface until the debounced fetch
// returns (see composables/useLiveBlockPreview.ts / useContentPartPreview.ts), and was
// previously dereferenced with a `!` assertion here — reachable and previously crashed
// with "can't access property 'items', $props.resolved is undefined" on the exact
// preview paths that don't await resolution. Mirror LinkListDisplay.vue's safe-computed
// pattern instead.
const groups = computed(() => props.resolved?.groups ?? []);
const items = computed(() => props.resolved?.items ?? []);
const hasGroups = computed(() => groups.value.length > 0);
const isEmpty = computed(() => (props.resolved?.meta?.total ?? 0) === 0);

const dateSpan = (event: EventListResolvedItem) =>
  formatEventDateSpan(event.date ?? '', event.endDate, { allDay: event.isAllDay, locale: locale.value });

const dayOfMonth = (date: string | null) => (date ? new Date(date).getDate() : '');

function coverImageFor(items: EventListResolvedItem[]): string | null {
  return items.find(item => item.imageUrl)?.imageUrl ?? null;
}
</script>

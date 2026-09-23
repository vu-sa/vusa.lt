<template>
  <article
    class="group flex flex-col transition-colors"
    :class="{ 'opacity-75': variant === 'past' }"
    data-slot="event-card"
  >
    <component
      :is="sameOrigin ? Link : 'a'"
      v-if="variant === 'compact'"
      :href="eventHref"
      class="flex items-center gap-4 border-b border-border py-4 hover:bg-secondary/40 pointer-coarse:min-h-11"
    >
      <DatePlate :date="eventDateObj" class="w-14 border border-border bg-secondary/40" />
      <span class="min-w-0 flex-1">
        <span class="block text-sm font-bold text-foreground transition-colors group-hover:text-brand">{{ eventTitle }}</span>
        <span class="mt-1 block text-xs text-muted-foreground">{{ formattedDateTime }}</span>
      </span>
      <IFluentArrowUpRight20Regular class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
    </component>
    <!-- 16:10 fixed-ratio image frame per v0 design -->
    <component :is="sameOrigin ? Link : 'a'"
      v-else
      :href="eventHref"
      class="relative aspect-[16/9] overflow-hidden border border-border bg-secondary"
    >
      <img
        v-if="imageUrl && !imageLoadError"
        class="size-full object-cover transition-transform duration-400 group-hover:scale-103"
        :src="imageUrl"
        :alt="eventTitle"
        :style="{ objectPosition: event.main_image_focal_point ?? '50% 30%' }"
        loading="lazy"
        @error="imageLoadError = true"
        @load="imageLoadError = false"
      >
      <div
        v-else
        class="flex size-full items-center justify-center bg-secondary text-muted-foreground/40"
      >
        <IFluentCalendarLtr24Regular class="size-10" />
      </div>

      <!-- Date plate (top-left) -->
      <DatePlate
        :date="eventDateObj"
        class="absolute left-0 top-0 border-b border-r border-brand"
      />

      <!-- Badges (top-right) -->
      <div
        v-if="showBadges && (eventTypeName || tenantShortname)"
        class="absolute right-0 top-0 flex max-w-[65%] flex-wrap justify-end"
      >
        <span
          v-if="eventTypeName"
          :class="[
            'border-b border-l border-border bg-background/90 px-2 py-0.5',
            'text-[0.625rem] font-bold uppercase tracking-wider text-brand backdrop-blur-xs truncate',
          ]"
        >
          {{ eventTypeName }}
        </span>
        <span
          v-if="tenantShortname"
          :class="[
            'border-b border-l border-border bg-background/90 px-2 py-0.5',
            'text-[0.625rem] font-bold uppercase tracking-wider text-muted-foreground backdrop-blur-xs truncate',
          ]"
        >
          {{ tenantShortname }}
        </span>
      </div>
    </component>

    <!-- Content -->
    <div v-if="variant !== 'compact'" class="flex flex-1 flex-col pt-4">
      <!-- Title -->
      <h3 class="text-pretty text-lg font-bold leading-snug text-foreground transition-colors group-hover:text-brand">
        <component :is="sameOrigin ? Link : 'a'" :href="eventHref">
          {{ eventTitle }}
        </component>
      </h3>

      <!-- Metadata -->
      <div class="mt-2 flex flex-col gap-1 text-sm text-muted-foreground">
        <span class="flex items-center gap-1.5">
          <IFluentCalendarLtr20Regular class="size-3.5 shrink-0 text-brand" />
          <span>{{ formattedDateTime }}</span>
        </span>

        <span v-if="event.is_remote" class="flex items-center gap-1.5">
          <IFluentGlobe20Regular class="size-3.5 shrink-0 text-brand" />
          <span>{{ $t('Nuotolinis renginys') }}</span>
        </span>
        <span v-else-if="eventLocation" class="flex items-center gap-1.5 min-w-0">
          <IFluentLocation16Regular class="size-3.5 shrink-0 text-brand" />
          <span class="truncate">{{ eventLocation }}</span>
        </span>
      </div>

      <!-- Action -->
      <div class="mt-auto flex items-center justify-between gap-3 pt-4">
        <component :is="sameOrigin ? Link : 'a'"
          :href="eventHref"
          class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-foreground transition-colors group-hover:text-brand"
        >
          <span>{{ variant === 'past' ? $t('Peržiūrėti') : $t('Daugiau') }}</span>
          <IFluentArrowUpRight20Regular class="size-4" />
        </component>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import DatePlate from '@/Components/Public/Base/DatePlate.vue';
import { formatEventDateSpan } from '@/Utils/IntlTime';
import { getCalendarEvent2Route } from '@/Utils/Route';
import type { LocaleEnum } from '@/Types/enums';
import IFluentCalendarLtr20Regular from '~icons/fluent/calendar-ltr-20-regular';
import IFluentCalendarLtr24Regular from '~icons/fluent/calendar-ltr-24-regular';
import IFluentLocation16Regular from '~icons/fluent/location-16-regular';
import IFluentGlobe20Regular from '~icons/fluent/globe-20-regular';
import IFluentArrowUpRight20Regular from '~icons/fluent/arrow-up-right-20-regular';

interface CalendarEventLike {
  id: number | string;
  title: string | string[] | Record<string, unknown>;
  date: string | number | Date;
  end_date?: string | number | Date | null;
  is_all_day?: boolean;
  is_remote?: boolean;
  location?: string | string[] | null;
  main_image_url?: string | null;
  main_image_focal_point?: string | null;
  facebook_url?: string | null;
  event_type?: { name: string } | null;
  event_type_name?: string | null;
  tenant?: { shortname: string } | null;
  tenant_shortname?: string | null;
  public_url?: string | null;
}

const page = usePage();

const props = withDefaults(defineProps<{
  event: CalendarEventLike | App.Entities.Calendar;
  variant?: 'upcoming' | 'past' | 'compact';
  showBadges?: boolean;
}>(), {
  variant: 'upcoming',
  // eslint-disable-next-line vue/no-boolean-default
  showBadges: true,
});

const imageLoadError = ref(false);

const eventTitle = computed(() => {
  const t = props.event.title;
  if (Array.isArray(t)) return t.join(' ');
  if (typeof t === 'string') return t;
  return String(t ?? '');
});

const eventLocation = computed(() => {
  const loc = props.event.location;
  if (!loc) return null;
  if (Array.isArray(loc)) return loc.join(' ');
  return String(loc);
});

const eventTypeName = computed(() => {
  const ev = props.event as CalendarEventLike;
  return ev.event_type?.name ?? ev.event_type_name ?? null;
});

const tenantShortname = computed(() => {
  const ev = props.event as CalendarEventLike;
  return ev.tenant?.shortname ?? ev.tenant_shortname ?? null;
});

const eventHref = computed(() => getCalendarEvent2Route(props.event, page.props.app.locale));
const sameOrigin = computed(() => {
  if (typeof window === 'undefined') return false;
  return new URL(eventHref.value, window.location.href).origin === window.location.origin;
});

const imageUrl = computed(() => {
  const ev = props.event as CalendarEventLike;
  return ev.main_image_url ?? null;
});

const normalizeDate = (d: number | Date | string | undefined | null): Date => {
  if (!d) return new Date();
  if (d instanceof Date) return d;
  if (typeof d === 'number') {
    // UNIX timestamp in seconds
    return new Date(d < 10000000000 ? d * 1000 : d);
  }
  return new Date(d);
};

const eventDateObj = computed(() => normalizeDate(props.event.date));

const formattedDateTime = computed(() => {
  const startDate = normalizeDate(props.event.date);
  const endDate = props.event.end_date ? normalizeDate(props.event.end_date) : null;

  const span = formatEventDateSpan(startDate, endDate, {
    allDay: props.event.is_all_day,
    locale: page.props.app.locale as LocaleEnum,
  });

  return `${span.primary} · ${span.secondary}`;
});
</script>

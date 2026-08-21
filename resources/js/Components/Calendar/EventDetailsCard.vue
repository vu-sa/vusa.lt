<template>
  <div
    class="rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 p-5 ring-1 ring-zinc-200/50 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50"
  >
    <!-- When -->
    <div class="flex gap-3">
      <div
        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20"
      >
        <IFluentCalendarLtr20Regular class="size-5 text-vusa-red" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
          {{ $t("Data") }}
        </p>
        <p class="mt-0.5 font-semibold text-zinc-900 dark:text-zinc-100">
          {{ dateSpan.primary }}
        </p>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          {{ dateSpan.secondary }}
        </p>
        <a
          v-if="googleLink"
          :href="googleLink"
          target="_blank"
          rel="noopener noreferrer"
          class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-vusa-red hover:underline"
        >
          <ISimpleIconsGoogle class="size-3.5" />
          {{ $t("Į kalendorių") }}
        </a>
      </div>
    </div>

    <!-- Where: remote events show no map/address, only a join link if one exists -->
    <div v-if="event.is_remote" class="mt-5 border-t border-zinc-200/60 pt-5 dark:border-zinc-700/60">
      <div class="flex gap-3">
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20"
        >
          <IFluentGlobe20Regular class="size-5 text-vusa-red" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            {{ $t("Vieta") }}
          </p>
          <p class="mt-0.5 font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $t("Nuotolinis renginys") }}
          </p>
          <a
            v-if="joinUrl"
            :href="joinUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-vusa-red hover:underline"
          >
            <IFluentOpen20Regular class="size-3.5" />
            {{ $t("Prisijungti") }}
          </a>
        </div>
      </div>
    </div>

    <div v-else-if="location" class="mt-5 border-t border-zinc-200/60 pt-5 dark:border-zinc-700/60">
      <div class="flex gap-3">
        <div
          class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20"
        >
          <IFluentLocation20Regular class="size-5 text-vusa-red" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
            {{ $t("Vieta") }}
          </p>
          <p class="mt-0.5 font-semibold text-zinc-900 dark:text-zinc-100">
            {{ location }}
          </p>
        </div>
      </div>

      <EventLocationMap
        v-if="coordinates"
        :latitude="coordinates.lat"
        :longitude="coordinates.lng"
        :label="location"
        class="mt-3"
      />

      <a
        :href="googleMapsUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-vusa-red hover:underline"
      >
        <IFluentOpen20Regular class="size-3.5" />
        {{ $t("Žiūrėti žemėlapyje") }}
      </a>
    </div>

    <!-- Who -->
    <div
      v-if="organizer"
      class="mt-5 flex gap-3 border-t border-zinc-200/60 pt-5 dark:border-zinc-700/60"
    >
      <div
        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-vusa-red/10 dark:bg-vusa-red/20"
      >
        <IFluentPeopleTeam20Regular class="size-5 text-vusa-red" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
          {{ $t("Organizatorius") }}
        </p>
        <p class="mt-0.5 font-semibold text-zinc-900 dark:text-zinc-100">
          {{ organizer }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import EventLocationMap from '@/Components/Calendar/EventLocationMap.vue';
import { formatEventDateSpan } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  event: App.Entities.Calendar;
  googleLink?: string | null;
  /** Server-side geocode of `event.location`; null when unresolvable. */
  coordinates?: { lat: number; lng: number; display_name: string } | null;
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const location = computed(() => (props.event.location ? String(props.event.location) : ''));
const organizer = computed(() => (props.event.organizer ? String(props.event.organizer) : ''));
/** The call-to-action URL doubles as the join link for a remote event. */
const joinUrl = computed(() => (props.event.cto_url ? String(props.event.cto_url) : ''));

const dateSpan = computed(() =>
  formatEventDateSpan(props.event.date, props.event.end_date, {
    allDay: props.event.is_all_day,
    locale: locale.value,
  }),
);

const googleMapsUrl = computed(
  () => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location.value)}`,
);
</script>

<template>
  <article
    class="group flex h-full flex-col overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/50 transition-all duration-300 hover:shadow-lg hover:ring-zinc-300 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600"
  >
    <!-- Cover -->
    <div class="relative aspect-[16/9] overflow-hidden bg-zinc-200 dark:bg-zinc-800">
      <img
        v-if="coverImage"
        :src="coverImage"
        :alt="facultyName"
        class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
        loading="lazy"
      >
      <div
        v-else
        class="flex size-full items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/25"
      >
        <IFluentTent24Regular class="size-10 text-vusa-red/50" />
      </div>

      <span
        v-if="events.length > 1"
        class="absolute right-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur-sm"
      >
        {{ events.length }} {{ $tChoice("summerCamps.camp_count", events.length) }}
      </span>
    </div>

    <div class="flex flex-1 flex-col p-5">
      <h3 class="text-base font-bold leading-tight text-zinc-900 dark:text-zinc-100">
        {{ facultyName }}
      </h3>

      <!-- One row per camp: a faculty can run more than one -->
      <ul class="mt-4 flex flex-1 flex-col gap-2">
        <li v-for="event in events" :key="event.id">
          <SmartLink
            :href="eventHref(event)"
            class="flex items-start gap-3 rounded-xl p-2.5 -mx-2.5 transition-colors hover:bg-white/70 dark:hover:bg-zinc-800/60"
          >
            <div
              class="flex min-w-11 flex-col items-center justify-center rounded-lg bg-vusa-red/10 px-2 py-1.5 text-center text-vusa-red dark:bg-vusa-red/20"
            >
              <span class="text-[9px] font-semibold uppercase leading-none">
                {{ formatMonthShort(event.date, locale) }}
              </span>
              <span class="mt-0.5 text-base font-bold leading-none tabular-nums">
                {{ dayOfMonth(event.date) }}
              </span>
            </div>

            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-zinc-800 transition-colors group-hover:text-vusa-red dark:text-zinc-200">
                {{ dateSpan(event).primary }}
              </p>
              <p
                v-if="event.location"
                class="mt-0.5 flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400"
              >
                <IFluentLocation20Regular class="size-3 shrink-0" />
                <span class="truncate">{{ event.location }}</span>
              </p>
            </div>

            <IFluentChevronRight12Regular
              class="mt-2 size-3 shrink-0 text-zinc-300 transition-colors group-hover:text-vusa-red dark:text-zinc-600"
            />
          </SmartLink>

          <a
            v-if="event.cto_url"
            :href="String(event.cto_url)"
            target="_blank"
            rel="noopener noreferrer"
            class="ml-14 inline-flex items-center gap-1 text-xs font-semibold text-vusa-red hover:underline"
          >
            {{ $t("Registruotis") }} →
          </a>
        </li>
      </ul>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { formatEventDateSpan, formatMonthShort } from '@/Utils/IntlTime';
import { getFacultyName } from '@/Utils/String';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  tenant: { id: number; alias: string; fullname: string };
  events: App.Entities.Calendar[];
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

/**
 * Tenant names are stored in the locative ("… Studentų atstovybė Teisės fakultete").
 * `getFacultyName` converts them back to the nominative; the main VU SA tenant has no
 * faculty part at all, so fall back to the full name.
 */
const facultyName = computed(() => {
  const name = getFacultyName(props.tenant).trim();
  return name ? `VU ${name}` : props.tenant.fullname;
});

const coverImage = computed(() => props.events.find(event => event.main_image_url)?.main_image_url ?? '');

const dateSpan = (event: App.Entities.Calendar) =>
  formatEventDateSpan(event.date, event.end_date, {
    allDay: event.is_all_day,
    locale: locale.value,
  });

const dayOfMonth = (date: string) => new Date(date).getDate();

const eventHref = (event: App.Entities.Calendar) =>
  route('calendar.event', {
    calendar: event.id,
    lang: locale.value,
    subdomain: 'www',
  });
</script>

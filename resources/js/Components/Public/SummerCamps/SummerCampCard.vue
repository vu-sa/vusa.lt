<template>
  <RCFeatureCard :title="facultyName" :cover-image="coverImage || null" :cover-alt="facultyName"
    :badge="events.length > 1 ? `${events.length} ${$tChoice('summerCamps.camp_count', events.length)}` : null">
    <template #cover-fallback>
      <IFluentTent24Regular class="size-10 text-vusa-red/50" />
    </template>

    <!-- One row per camp: a faculty can run more than one. `contents` keeps the <ul>
         valid HTML without affecting layout — RCFeatureCard's footer div already
         supplies the flex/gap styling the <li>s render into. -->
    <ul class="contents">
    <li v-for="event in events" :key="event.id">
      <SmartLink
        :href="eventHref(event)"
        class="relative z-20 flex items-start gap-3 rounded-xl p-2.5 -mx-2.5 transition-colors hover:bg-white/70 dark:hover:bg-zinc-800/60"
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
        class="relative z-20 ml-14 inline-flex items-center gap-1 text-xs font-semibold text-vusa-red hover:underline"
      >
        {{ $t("Registruotis") }} →
      </a>
    </li>
    </ul>
  </RCFeatureCard>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import RCFeatureCard from '@/Components/RichContent/RCFeatureCard.vue';
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

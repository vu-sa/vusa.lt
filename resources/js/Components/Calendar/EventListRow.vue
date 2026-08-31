<template>
  <Link
    :href="href"
    class="group flex items-center gap-4 rounded-xl border border-zinc-200 bg-white px-4 py-3 transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700/70 dark:bg-zinc-800/60 dark:hover:border-zinc-600 dark:hover:bg-zinc-800"
  >
    <div
      class="flex size-12 shrink-0 flex-col items-center justify-center rounded-lg bg-vusa-red/10 leading-none dark:bg-vusa-red/20"
    >
      <span class="text-[10px] font-semibold uppercase tracking-wide text-vusa-red">{{ monthLabel }}</span>
      <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ dayLabel }}</span>
    </div>

    <div class="min-w-0 flex-1">
      <p class="truncate font-medium text-zinc-900 transition-colors group-hover:text-vusa-red dark:text-zinc-100">
        {{ title }}
      </p>
      <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
        {{ metaLine }}
      </p>
    </div>

    <IFluentChevronRight20Regular
      class="size-4 shrink-0 text-zinc-300 transition-colors group-hover:text-vusa-red dark:text-zinc-600"
    />
  </Link>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

import { formatEventDateSpan, formatMonthShort, formatStaticTime } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  event: App.Entities.Calendar;
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const startsAt = computed(() => new Date(props.event.date));

const monthLabel = computed(() => formatMonthShort(startsAt.value, locale.value));
const dayLabel = computed(() => formatStaticTime(startsAt.value, { day: 'numeric' }, locale.value));

const title = computed(() =>
  Array.isArray(props.event.title) ? props.event.title.join(' ') : String(props.event.title ?? ''),
);

const href = computed(() => route('calendar.event', { calendar: props.event.id, lang: locale.value }));

/**
 * The badge already carries the start day, so the line under the title adds only what it
 * cannot — except for a multi-day event, where the span is the point.
 */
const metaLine = computed(() => {
  const span = formatEventDateSpan(props.event.date, props.event.end_date, {
    allDay: props.event.is_all_day,
    locale: locale.value,
  });

  const endsAt = props.event.end_date ? new Date(props.event.end_date) : null;
  const isMultiDay = !!endsAt && endsAt.toDateString() !== startsAt.value.toDateString();

  const place = props.event.is_remote
    ? $t('Nuotolinis renginys')
    : (Array.isArray(props.event.location) ? props.event.location.join(' ') : props.event.location);

  return [isMultiDay ? span.primary : null, span.secondary, place].filter(Boolean).join(' · ');
});
</script>

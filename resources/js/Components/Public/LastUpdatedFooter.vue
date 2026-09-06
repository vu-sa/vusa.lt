<template>
  <footer v-if="formattedDate" class="mt-16 border-t border-border pt-6 pb-2">
    <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
      <IFluentClock24Regular class="size-3.5 shrink-0" />
      <span>
        {{ $t('content-page.last_updated_footer') }}
        <time :datetime="isoDate ?? undefined">{{ formattedDate }}</time>
      </span>
    </p>
  </footer>
</template>

<script setup lang="ts">
/**
 * The "last updated" line used to sit right under the page `<h1>`, showed a relative
 * "3 days ago" for the first week, and needed no `<time>` element or explanatory
 * text — easy to miss and ambiguous once you noticed it. Moved to the bottom of the
 * page with more breathing room, an explicit label, and always a static, absolute
 * date (relative time doesn't make sense once it's not immediately next to the
 * title, and it would otherwise silently go stale between renders).
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import IFluentClock24Regular from '~icons/fluent/clock-24-regular';
import { formatStaticTime } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  /** Raw ISO-ish date string (`last_edited_at` or `updated_at`) — `null` hides the footer entirely. */
  date?: string | null;
}>();

const inertiaPage = usePage();
const locale = computed(() => (inertiaPage.props.app?.locale === 'en' ? LocaleEnum.EN : LocaleEnum.LT));

const isoDate = computed(() => (props.date ? new Date(props.date).toISOString() : null));

const formattedDate = computed(() => {
  if (!props.date) return null;

  return formatStaticTime(new Date(props.date), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }, locale.value);
});
</script>

<template>
  <div
    :class="[
      'group relative flex h-full flex-col rounded-lg border border-border bg-card p-4',
      interactiveCardClass,
      muted && 'opacity-70',
    ]"
    data-slot="duty-summary-card"
  >
    <div class="flex items-start justify-between gap-3">
      <!--
        The name is the card's only link, and its ::after covers the card, so the
        whole surface is the click target while the lines below stay plain text.
      -->
      <Link
        :href="route('duties.show', duty.id)"
        class="text-base font-semibold leading-snug text-foreground no-underline transition-colors after:absolute after:inset-0 after:content-[''] group-hover:text-primary"
      >
        {{ displayName }}
      </Link>
    </div>

    <p v-if="showInstitution && duty.institution?.name" class="flex items-center mt-1 gap-2 text-xs leading-4 text-muted-foreground">
      <Building2 class="icon-inline shrink-0" />
      <span class="min-w-0">
        {{ duty.institution.name }}
        <span v-if="duty.institution.tenant?.shortname" class="text-muted-foreground/60">
          ({{ duty.institution.tenant.shortname }})
        </span>
      </span>
    </p>

    <!-- Pushed to the bottom so the meta line sits level across a row of cards
         whose titles wrap to different heights. -->
    <div
      v-if="tenureLabel || contactEmail"
      class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1.5 pt-4 text-xs text-muted-foreground"
    >
      <span v-if="tenureLabel" class="inline-flex min-w-0 items-center gap-1.5">
        <Calendar class="icon-inline shrink-0" />
        <span class="truncate tabular-nums">{{ tenureLabel }}</span>
      </span>
      <span v-if="contactEmail" class="inline-flex min-w-0 items-center gap-1.5">
        <Mail class="icon-inline shrink-0" />
        <span class="truncate">{{ contactEmail }}</span>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, Calendar, Mail } from 'lucide-vue-next';

import { interactiveCardClass } from '@/Utils/interactiveCard';
import { formatStaticTime } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';
import { changeDutyNameEndings } from '@/Utils/String';

export interface SummaryDuty {
  id: string | number;
  name: string;
  email?: string | null;
  places_to_occupy?: number | null;
  institution?: { id: string | number; name: string; tenant?: { shortname?: string | null } | null } | null;
  current_users?: App.Entities.User[];
  /** The viewing user's own tenure on this duty (present on ShowUser). */
  pivot?: { start_date?: string; end_date?: string | null; additional_email?: string; use_original_duty_name?: boolean } | null;
}

export interface DutySummaryHolder {
  name?: string | null;
  /** Pronouns as a locale string ("jis/jo") or a { lt, en } map. */
  pronouns?: string | { lt?: string; en?: string } | null;
}

const props = withDefaults(defineProps<{
  duty: SummaryDuty;
  /** Show the institution line under the duty name. */
  showInstitution?: boolean;
  /**
   * Show the occupancy badge and the holders row. Both describe how the *duty*
   * is staffed, which says nothing on a member profile — turn them off there.
   */
  showStatus?: boolean;
  showHolders?: boolean;
  /** Dim the card (e.g. previous duties). */
  muted?: boolean;
  /** Exclude this user from the holders avatar group (e.g. the profile being viewed). */
  excludeUserId?: string | number;
  /**
   * The person whose duty this is. When provided, the duty name's ending is
   * inflected to match their pronouns/name (e.g. "Koordinatorius" →
   * "Koordinatorė"); otherwise the stored name is shown as-is.
   */
  holder?: DutySummaryHolder | null;
  /** Per-assignment override: keep the stored duty name uninflected. */
  useOriginalDutyName?: boolean;
}>(), {
  showInstitution: true,
  showStatus: true,
  showHolders: true,
  muted: false,
  excludeUserId: undefined,
  holder: null,
  useOriginalDutyName: false,
});

const locale = computed(() => (usePage().props.app.locale as LocaleEnum) ?? LocaleEnum.LT);

// `use_original_duty_name` on the pivot takes precedence over the caller-supplied
// prop, since it is the per-assignment setting that actually governs inflection.
const effectiveUseOriginal = computed(() => props.duty.pivot?.use_original_duty_name ?? props.useOriginalDutyName);

const displayName = computed(() => {
  if (!props.holder) return props.duty.name;
  const rawPronouns = props.holder.pronouns;
  const pronouns = typeof rawPronouns === 'string'
    ? rawPronouns
    : (rawPronouns?.[locale.value as 'lt' | 'en'] ?? '');
  return changeDutyNameEndings(
    { name: props.holder.name ?? '' } as App.Entities.User,
    props.duty.name,
    locale.value,
    pronouns,
    effectiveUseOriginal.value,
  );
});

/** The assignment's own contact address wins over the duty's shared one. */
const contactEmail = computed(() => props.duty.pivot?.additional_email || props.duty.email || '');

const tenureLabel = computed(() => {
  const start = props.duty.pivot?.start_date;
  if (!start) { return ''; }
  const monthAndYear: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short' };
  const startLabel = formatStaticTime(new Date(start), monthAndYear, locale.value);
  const end = props.duty.pivot?.end_date;
  if (!end || new Date(end) >= new Date()) {
    return `${startLabel} – ${$t('dabar')}`;
  }
  return `${startLabel} – ${formatStaticTime(new Date(end), monthAndYear, locale.value)}`;
});
</script>

<template>
  <div
    :class="[
      'group relative flex flex-col gap-2 rounded-lg border border-border bg-card p-3',
      interactiveCardClass,
      muted && 'opacity-70',
    ]"
  >
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0">
        <Link
          :href="route('duties.show', duty.id)"
          class="truncate text-sm font-semibold text-foreground transition-colors hover:text-primary"
        >
          {{ duty.name }}
        </Link>
        <p v-if="showInstitution && duty.institution?.name" class="truncate text-xs text-muted-foreground">
          <Link :href="route('institutions.show', duty.institution.id)" class="hover:text-primary hover:underline">
            {{ duty.institution.name }}
          </Link>
          <span v-if="duty.institution.tenant?.shortname"> ({{ duty.institution.tenant.shortname }})</span>
        </p>
      </div>
      <Badge :variant="statusVariant" class="shrink-0 text-xs">
        {{ statusText }}
      </Badge>
    </div>

    <div class="flex items-center justify-between gap-2">
      <div class="flex min-w-0 items-center gap-2">
        <UsersAvatarGroup v-if="visibleHolders.length" :users="visibleHolders" :max="4" :size="24" clickable />
        <span class="text-xs text-muted-foreground">
          {{ holderCount }}<template v-if="duty.places_to_occupy"> / {{ duty.places_to_occupy }}</template>
          {{ $t('užimta') }}
        </span>
      </div>
      <span v-if="duty.email" class="hidden shrink-0 truncate text-xs text-muted-foreground sm:inline">
        {{ duty.email }}
      </span>
    </div>

    <div v-if="tenureLabel" class="flex items-center gap-1 text-xs text-muted-foreground">
      <Calendar class="h-3 w-3 shrink-0" />
      <span class="truncate">{{ tenureLabel }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar } from 'lucide-vue-next';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { Badge } from '@/Components/ui/badge';
import { interactiveCardClass } from '@/Utils/interactiveCard';
import { formatStaticTime } from '@/Utils/IntlTime';

export interface SummaryDuty {
  id: string | number;
  name: string;
  email?: string | null;
  places_to_occupy?: number | null;
  institution?: { id: string | number; name: string; tenant?: { shortname?: string | null } | null } | null;
  current_users?: App.Entities.User[];
  /** The viewing user's own tenure on this duty (present on ShowUser). */
  pivot?: { start_date?: string; end_date?: string | null; additional_email?: string } | null;
}

const props = withDefaults(defineProps<{
  duty: SummaryDuty;
  /** Show the institution line under the duty name. */
  showInstitution?: boolean;
  /** Dim the card (e.g. previous duties). */
  muted?: boolean;
  /** Exclude this user from the holders avatar group (e.g. the profile being viewed). */
  excludeUserId?: string | number;
}>(), {
  showInstitution: true,
  muted: false,
  excludeUserId: undefined,
});

const holders = computed<App.Entities.User[]>(() => props.duty.current_users ?? []);

const visibleHolders = computed(() =>
  props.excludeUserId != null
    ? holders.value.filter(user => String(user.id) !== String(props.excludeUserId))
    : holders.value,
);

const holderCount = computed(() => holders.value.length);

const statusVariant = computed(() => {
  const current = holderCount.value;
  const max = props.duty.places_to_occupy || 0;
  if (current === 0) { return 'outline'; }
  if (max && current < max) { return 'secondary'; }
  if (max && current > max) { return 'destructive'; }
  return 'default';
});

const statusText = computed(() => {
  const current = holderCount.value;
  const max = props.duty.places_to_occupy || 0;
  if (current === 0) { return $t('Neužimta'); }
  if (max && current < max) { return $t('Dalinai užimta'); }
  if (max && current > max) { return $t('Viršija limitą'); }
  return $t('Pilnai užimta');
});

const tenureLabel = computed(() => {
  const start = props.duty.pivot?.start_date;
  if (!start) { return ''; }
  const startLabel = formatStaticTime(new Date(start), { year: 'numeric', month: 'short' });
  const end = props.duty.pivot?.end_date;
  if (!end || new Date(end) >= new Date()) {
    return `${startLabel} – ${$t('dabar')}`;
  }
  return `${startLabel} – ${formatStaticTime(new Date(end), { year: 'numeric', month: 'short' })}`;
});
</script>

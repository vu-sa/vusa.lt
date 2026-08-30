<template>
  <SectionCard
    :title="$t('Paskutiniai susitikimai')"
    :icon="CalendarIcon"
    :count="totalCount || undefined"
    @action="$emit('view-all')"
  >
    <template #action>
      <div class="flex items-center gap-2">
        <Button v-if="requiresAction" variant="default" size="sm" class="gap-1.5" @click="$emit('schedule-meeting')">
          <Plus class="h-3.5 w-3.5" />
          {{ $t('Naujas') }}
        </Button>
        <button
          type="button"
          class="inline-flex shrink-0 items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
          @click="$emit('view-all')"
        >
          {{ $t('Visi susitikimai') }}
          <ChevronRight class="h-4 w-4" />
        </button>
      </div>
    </template>

    <!-- A plain divided list: inside a card, per-row cards would read as boxes in a box. -->
    <div class="-my-1 divide-y divide-border">
      <button
        v-for="meeting in meetings"
        :key="meeting.id"
        type="button"
        :class="[
          'flex w-full items-start gap-4 px-2 py-3 text-left',
          'transition-colors hover:bg-muted/60',
          'focus:outline-none focus-visible:bg-muted/60',
        ]"
        @click="$emit('view-meeting', meeting)"
      >
        <DateBadge :date="meeting.start_time" />

        <!-- Meeting info -->
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-foreground">
            {{ getMeetingTitle(meeting) }}
          </p>

          <ol v-if="meeting.agenda_item_titles?.length" class="mt-1.5 space-y-0.5">
            <li
              v-for="(item, index) in meeting.agenda_item_titles"
              :key="index"
              class="flex gap-2 text-xs text-muted-foreground"
            >
              <span class="tabular-nums opacity-70">{{ index + 1 }}.</span>
              <span class="truncate">{{ item }}</span>
            </li>
          </ol>

          <p v-if="remainingAgendaItems(meeting) > 0" class="mt-1 text-xs text-muted-foreground opacity-80">
            {{ $t('ir dar :count', { count: String(remainingAgendaItems(meeting)) }) }}
          </p>
          <p v-else-if="!meeting.agenda_item_titles?.length" class="mt-1 text-xs text-muted-foreground opacity-80">
            {{ $t('Nėra darbotvarkės') }}
          </p>
        </div>

        <!-- Status. Same palette as the dashboard meeting table. -->
        <span
          :class="[
            'inline-flex shrink-0 items-center rounded-full px-2 py-1 text-xs font-medium',
            isFutureMeeting(meeting)
              ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400'
              : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-400',
          ]"
        >
          {{ isFutureMeeting(meeting) ? $t('Būsimas') : $t('Įvykęs') }}
        </span>
      </button>
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import {
  Calendar as CalendarIcon,
  Plus,
  ChevronRight,
} from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';
import { DateBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { formatStaticTime } from '@/Utils/IntlTime';
import type { InstitutionPageMeeting } from '@/Types/InstitutionPage';

const props = defineProps<{
  meetings: InstitutionPageMeeting[];
  institution: { id: string | number; name: string };
  totalCount: number;
  requiresAction?: boolean;
}>();

const emit = defineEmits<{
  'view-all': [];
  'schedule-meeting': [];
  'view-meeting': [meeting: App.Entities.Meeting];
}>();

/** Items the preview could not fit — the count comes from the controller's withCount. */
const remainingAgendaItems = (meeting: InstitutionPageMeeting): number => {
  return (meeting.agenda_items_count ?? 0) - (meeting.agenda_item_titles?.length ?? 0);
};

const isFutureMeeting = (meeting: App.Entities.Meeting) => {
  return new Date(meeting.start_time) > new Date();
};

const getMeetingTitle = (meeting: App.Entities.Meeting) => {
  if (meeting.title) return meeting.title;
  return `${formatStaticTime(new Date(meeting.start_time), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })} ${$t('posėdis')}`;
};

</script>

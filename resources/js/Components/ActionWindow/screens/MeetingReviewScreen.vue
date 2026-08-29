<template>
  <ActionWindowScreen
    :title="$t('action_window.meeting.review.title')"
    :subtitle="$t('action_window.meeting.review.subtitle')"
  >
    <Alert v-if="errors.general" variant="destructive" class="mb-4">
      <AlertDescription>{{ errors.general }}</AlertDescription>
    </Alert>

    <dl class="divide-y divide-border overflow-hidden rounded-xl border border-border">
      <ReviewRow
        :label="$t('action_window.meeting.review.institution')"
        :value="draft.institution?.name"
        :editable="isOnStack('meeting.institution')"
        @edit="editFromHere('meeting.institution')"
      />
      <ReviewRow
        :label="$t('action_window.meeting.review.type')"
        :value="typeLabel"
        @edit="editFromHere('meeting.type')"
      />
      <ReviewRow
        :label="$t('action_window.meeting.review.when')"
        :value="whenLabel"
        @edit="editFromHere('meeting.when')"
      />
      <ReviewRow
        :label="$t('action_window.meeting.review.agenda')"
        :value="agendaLabel"
        @edit="editFromHere('meeting.agenda')"
      />
    </dl>

    <!-- Only VU SA's own bodies are announced publicly, so for anything VU SA merely
         delegates into the option is absent rather than offered and refused. -->
    <label
      v-if="canAnnounce"
      class="mt-4 flex cursor-pointer select-none items-start gap-3 rounded-2xl border border-border/70 bg-card p-4"
    >
      <Checkbox
        :model-value="!!draft.meeting.announce_in_calendar"
        @update:model-value="(value) => updateMeeting({ announce_in_calendar: Boolean(value) })"
      />
      <span class="flex-1">
        <span class="flex items-center gap-2 text-sm font-medium">
          <CalendarPlus class="size-4 text-muted-foreground" />
          {{ $t('meetings.announce.review_checkbox_label') }}
        </span>
        <span class="mt-0.5 block text-xs text-muted-foreground">
          {{ $t('meetings.announce.review_checkbox_hint') }}
        </span>
      </span>
    </label>

    <template #footer>
      <Button class="w-full" size="lg" :disabled="submitting" @click="submit">
        <Loader2 v-if="submitting" class="mr-2 size-4 animate-spin" />
        {{ submitting ? $t('action_window.meeting.review.submitting') : $t('action_window.meeting.review.submit') }}
      </Button>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { trans as $t, transChoice as $tChoice, getActiveLanguage } from 'laravel-vue-i18n';
import { CalendarPlus, Loader2 } from 'lucide-vue-next';

import ActionWindowScreen from '../ActionWindowScreen.vue';
import { useWindowDates } from '../useWindowDates';
import ReviewRow from '../ReviewRow.vue';

import { useActionWindow, type ScreenId } from '@/Composables/useActionWindow';
import { useMeetingCreation } from '@/Composables/useMeetingCreation';
import { invalidateActionWindowData, useActionWindowData } from '@/Composables/useActionWindowData';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { getMeetingTypeOptions, isDateOnlyMeetingType } from '@/Types/MeetingType';

const { draft, stack, editFromHere, close, updateMeeting } = useActionWindow();

// A screen the flow skipped (a caller-supplied institution) is not on the stack,
// so offering "change" on it would be a dead button.
const isOnStack = (id: ScreenId) => stack.some(frame => frame.id === id);
const { submitting, errors, submitMeeting } = useMeetingCreation();

const { institutions, load } = useActionWindowData();
const dates = useWindowDates();

onMounted(load);

/**
 * The seeded value when a caller knew it; otherwise resolved from the window's own
 * institution list. Unknown stays false — the server refuses it either way.
 */
const canAnnounce = computed(() => draft.institution?.isInternal
  ?? institutions.value.find(institution => institution.id === draft.institution?.id)?.is_internal
  ?? false);

const typeLabel = computed(() => {
  const options = getMeetingTypeOptions(getActiveLanguage() === 'en' ? 'en' : 'lt');
  return options.find(option => option.value === (draft.meeting.type ?? null))?.label;
});

const whenLabel = computed(() => {
  if (!draft.meeting.start_time) {
    return undefined;
  }

  // An email meeting is a deadline, not an appointment: the time would be noise.
  return isDateOnlyMeetingType(draft.meeting.type ?? null)
    ? dates.dayWithWeekday(draft.meeting.start_time)
    : dates.dayWithTime(draft.meeting.start_time);
});

const agendaLabel = computed(() => {
  if (draft.meeting.open_bulk_agenda) {
    return $t('action_window.meeting.review.agenda_bulk');
  }

  return $tChoice('action_window.meeting.review.agenda_count', draft.agendaItems.length, {
    count: String(draft.agendaItems.length),
  });
});

const submit = () => {
  submitMeeting({ meeting: draft.meeting, agendaItems: draft.agendaItems }, {
    onSuccess: () => {
      // The new meeting changes both lists the window offers, so drop the cache
      // before the window closes rather than showing stale advice next time.
      invalidateActionWindowData();
      close();
    },
  });
};
</script>

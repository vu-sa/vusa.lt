<template>
  <div v-if="completion || tag || parts.length || votesLead" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs" data-slot="meeting-outcome">
    <span
      v-if="completion"
      :class="['inline-flex items-center gap-1 font-medium', completionTone]"
      :data-status-role="completion.role"
      data-slot="meeting-completion"
    >
      <component :is="completion.icon" class="size-3.5 shrink-0" aria-hidden="true" />
      {{ $t(completion.label) }}
    </span>
    <StatusBadge v-if="tag" :status="tag" />
    <ul
      v-if="parts.length"
      :class="['text-muted-foreground', show === 'counts' ? 'flex flex-col gap-0.5' : 'flex flex-wrap items-center']"
      data-slot="meeting-outcome-counts"
    >
      <li v-if="votesLead" class="mr-1">
        {{ votesLead }}
      </li>
      <li v-for="(part, index) in parts" :key="part.label" class="flex items-center">
        <span v-if="index > 0 && show !== 'counts'" class="px-1.5" aria-hidden="true">·</span>
        <span :class="['mr-1 font-bold tabular-nums', part.tone ?? 'text-foreground']">{{ part.count }}</span>
        {{ part.label }}
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { StatusBadge } from '@/Components/Patterns';
import { meetingAlignmentStatuses, meetingCompletionStatuses, statusFor, statusRoleParts, type StatusPresentation } from '@/Constants/statuses';
import type { MeetingSearchResult } from '@/Shared/Search/types';

/**
 * How a meeting went, anywhere one is listed. Nothing is said twice: `summary` folds the vote split
 * into one tag and adds only what the tag cannot say. Tables split it across columns with the
 * single-part modes: `completion`, `alignment` (the verdict alone) and `counts` (one per line).
 * `votes` is the tag alone, for a pane that lists the agenda itself.
 */
const props = withDefaults(defineProps<{
  outcome: Partial<MeetingSearchResult>;
  show?: 'summary' | 'completion' | 'alignment' | 'votes' | 'counts';
  /** A complete record is quiet unless a column or fact exists to say so. */
  showComplete?: boolean;
}>(), {
  show: 'summary',
});

const alignmentStatus = computed(() => statusFor(meetingAlignmentStatuses, props.outcome.vote_alignment_status));
const aligned = computed(() => props.outcome.outcome_aligned ?? 0);
const compared = computed(() => aligned.value + (props.outcome.outcome_misaligned ?? 0));

const completion = computed(() => {
  const status = props.outcome.completion_status;

  if ((props.show !== 'summary' && props.show !== 'completion') || (status === 'complete' && !props.showComplete)) {
    return null;
  }

  return statusFor(meetingCompletionStatuses, status);
});

// The record's state is plain text and the vote a boxed tag: side by side they must not read as one kind of thing.
const completionTone = computed(() => (completion.value?.role === 'success' ? 'text-muted-foreground' : completion.value ? statusRoleParts[completion.value.role].text : ''));

const tag = computed<StatusPresentation | null>(() => {
  if (props.show === 'alignment') {
    return alignmentStatus.value;
  }
  if ((props.show !== 'summary' && props.show !== 'votes') || !alignmentStatus.value) {
    return null;
  }

  return {
    ...alignmentStatus.value,
    label: $tChoice('meetings.outcome.matched_of', compared.value, { matched: String(aligned.value), total: String(compared.value) }),
  };
});

const decisions = computed(() => (props.outcome.outcome_positive ?? 0) + (props.outcome.outcome_negative ?? 0) + (props.outcome.outcome_neutral ?? 0));

// A body with no student position has no tag, so its votes need naming here.
const votesLead = computed(() => (props.show === 'summary' && decisions.value > 0
  ? `${decisions.value} ${$tChoice('meetings.outcome.votes', decisions.value)}`
  : null));

const parts = computed(() => {
  const { outcome, show } = props;

  if (show !== 'summary' && show !== 'counts') {
    return [];
  }

  const discussed = outcome.outcome_discussed ?? 0;
  const deferred = outcome.outcome_deferred ?? 0;
  // "Neužpildyta" already says some items have no result.
  const unknownIsSaid = show === 'summary' && Boolean(completion.value);
  const candidates: { label: string; count?: number; tone?: string }[] = [
    ...(show === 'counts'
      ? [
          { label: $t('meetings.outcome.aligned'), count: outcome.outcome_aligned, tone: statusRoleParts.success.text },
          { label: $t('meetings.outcome.misaligned'), count: outcome.outcome_misaligned, tone: statusRoleParts.danger.text },
        ]
      : []),
    { label: $t('meetings.outcome.positive'), count: outcome.outcome_positive, tone: statusRoleParts.success.text },
    { label: $t('meetings.outcome.negative'), count: outcome.outcome_negative, tone: statusRoleParts.danger.text },
    { label: $t('meetings.outcome.neutral'), count: outcome.outcome_neutral },
    { label: $tChoice('meetings.outcome.discussed', discussed), count: discussed },
    { label: $tChoice('meetings.outcome.deferred', deferred), count: deferred },
    ...(unknownIsSaid ? [] : [{ label: $t('meetings.outcome.unknown'), count: outcome.outcome_unknown }]),
  ];

  return candidates.filter((part): part is { label: string; count: number; tone?: string } => Boolean(part.count));
});
</script>

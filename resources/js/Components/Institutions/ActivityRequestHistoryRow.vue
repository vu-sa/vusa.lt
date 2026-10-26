<template>
  <li class="flex flex-col gap-2 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-6" data-slot="activity-request-recipient">
    <div class="min-w-0 space-y-1">
      <p class="text-sm font-semibold">
        <template v-if="request.institution">
          <Link v-if="request.institution.url" :href="request.institution.url" class="underline underline-offset-4">
            {{ request.institution.name }}
          </Link>
          <span v-else>{{ request.institution.name ?? $t('activity_requests.personal.unavailable_institution') }}</span>
        </template>
        <template v-else>
          {{ request.recipient ?? '–' }}
        </template>
      </p>
      <template v-if="request.institution">
        <p class="text-sm">
          {{ $t(`activity_requests.campaigns.${request.campaign_type}`) }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ request.requester ? $t('activity_requests.history.sent_by', { name: request.requester }) : $t('activity_requests.automatic') }}
          · {{ formatDateTime(request.created_at) }}
        </p>
        <blockquote v-if="request.note" class="whitespace-pre-line border-l-2 border-brand pl-3 text-sm">
          {{ request.note }}
        </blockquote>
      </template>
      <p class="text-xs text-muted-foreground">
        {{ $t('activity_requests.history.period') }}: {{ formatDate(request.period_start) }} –
        {{ request.period_end ? formatDate(request.period_end) : $t('activity_requests.legacy_period') }}
      </p>
      <p v-if="outcome(request)" class="text-sm">
        {{ outcome(request) }}
      </p>
      <div v-if="request.meetings.length || request.check_ins.length" class="flex flex-wrap gap-2 pt-1">
        <template v-for="meeting in request.meetings" :key="meeting.id">
          <Link
            v-if="meeting.url"
            :href="meeting.url"
            :class="controlVariants({ size: 'sm', voice: 'sentence' })"
          >
            <CalendarDays class="size-3.5" aria-hidden="true" />
            {{ $t('activity_requests.history.meeting', { date: formatDate(meeting.date) }) }}
          </Link>
          <span v-else class="inline-flex min-h-9 items-center gap-1.5 text-xs text-muted-foreground">
            <CalendarDays class="size-3.5" aria-hidden="true" />
            {{ $t('activity_requests.history.meeting', { date: formatDate(meeting.date) }) }}
          </span>
        </template>
        <span v-for="checkIn in request.check_ins" :key="checkIn.id" class="inline-flex min-h-9 items-center gap-1.5 text-xs text-muted-foreground">
          <CalendarX class="size-3.5" aria-hidden="true" />
          {{ $t('activity_requests.no_meetings') }}: {{ formatDate(checkIn.start) }} – {{ formatDate(checkIn.end) }}
        </span>
      </div>
    </div>
    <div class="flex shrink-0 flex-col gap-1 sm:items-end">
      <Button v-if="request.answer_url" as-child variant="outline" voice="sentence">
        <a :href="request.answer_url" @click="emit('answer')">{{ $t('activity_requests.personal.answer') }}</a>
      </Button>
      <StatusBadge :status="activityRequestStatuses[request.status]" />
      <p class="text-xs text-muted-foreground">
        {{ statusDetail(request) }}
      </p>
    </div>
  </li>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, CalendarX } from '@lucide/vue';

import type { ActivityRequestHistoryItem } from './InstitutionActivityRequestHistory.vue';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { controlVariants } from '@/Components/ui/control';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { activityRequestStatuses } from '@/Constants/statuses';

defineProps<{ request: ActivityRequestHistoryItem }>();
const emit = defineEmits<{ answer: [] }>();
const { formatDate, formatDateTime } = useDateFormatter();

const outcome = (request: ActivityRequestHistoryItem): string => {
  if (request.answer) {
    return $t(`activity_requests.history.answers.${request.answer}`);
  }

  if (!request.resolved_at) {
    return '';
  }
  const resolution = $t(`activity_requests.resolution.${request.resolution_source ?? 'meeting'}`);

  return request.resolved_by ? `${resolution} · ${$t('activity_requests.history.resolved_by', { name: request.resolved_by })}` : resolution;
};

const statusDetail = (request: ActivityRequestHistoryItem): string => ({
  pending: `${$t('activity_requests.valid_until')} ${formatDate(request.expires_at)}`,
  answered: request.answered_at ? formatDateTime(request.answered_at) : '',
  resolved: request.resolved_at ? formatDateTime(request.resolved_at) : '',
  expired: `${$t('activity_requests.history.expired_at')} ${formatDate(request.expires_at)}`,
})[request.status];
</script>

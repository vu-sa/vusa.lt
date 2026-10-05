<template>
  <!-- No card: the record page's tab already titles it, and the history is read, not acted on. -->
  <div data-slot="activity-request-history">
    <p v-if="!sendings.length" class="text-sm text-muted-foreground">
      {{ $t('activity_requests.history_empty') }}
    </p>
    <ol class="space-y-10">
      <li v-for="sending in sendings" :key="sending.key" class="space-y-3" data-slot="activity-request-sending">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
          <h3 class="u-eyebrow text-foreground">
            {{ $t(`activity_requests.campaigns.${sending.campaign}`) }}
          </h3>
          <p class="text-xs text-muted-foreground">
            <span class="font-medium text-foreground">{{ sending.requester ? $t('activity_requests.history.sent_by', { name: sending.requester }) : $t('activity_requests.automatic') }}</span>
            · <time :datetime="sending.sentAt">{{ formatDateTime(sending.sentAt) }}</time>
          </p>
        </div>
        <blockquote v-for="note in sending.notes" :key="note" class="border-l-2 border-brand pl-3 text-sm leading-relaxed text-foreground">
          {{ note }}
        </blockquote>
        <ul class="divide-y divide-border border-y border-border">
          <li v-for="request in sending.requests" :key="request.id" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-6" data-slot="activity-request-recipient">
            <div class="min-w-0 space-y-1">
              <p class="text-sm font-semibold">
                {{ request.recipient ?? '–' }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ $t('activity_requests.history.period') }}: {{ formatDate(request.period_start) }} – {{ request.period_end ? formatDate(request.period_end) : $t('activity_requests.legacy_period') }}
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
              <StatusBadge :status="activityRequestStatuses[request.status]" />
              <p class="text-xs text-muted-foreground">
                {{ statusDetail(request) }}
              </p>
            </div>
          </li>
        </ul>
      </li>
    </ol>
    <Button v-if="history.next_page" variant="outline" voice="sentence" class="mt-4 min-h-11" :disabled="loading" @click="loadMore">
      {{ $t('Rodyti daugiau') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays, CalendarX } from 'lucide-vue-next';

import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { controlVariants } from '@/Components/ui/control';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { activityRequestStatuses, type ActivityRequestStatus } from '@/Constants/statuses';

export interface ActivityRequestHistoryItem {
  id: string;
  recipient: string | null;
  requester: string | null;
  period_start: string;
  period_end: string | null;
  note: string | null;
  status: ActivityRequestStatus;
  answer: string | null;
  created_at: string;
  answered_at: string | null;
  resolved_at: string | null;
  resolution_source: string | null;
  resolved_by: string | null;
  expires_at: string;
  meetings: Array<{ id: string; date: string; url: string | null }>;
  check_ins: Array<{ id: string; start: string; end: string }>;
}

export interface ActivityRequestHistoryBatch {
  id: string;
  campaigns: Record<string, ActivityRequestHistoryItem[]>;
}

export interface ActivityRequestHistory {
  data: ActivityRequestHistoryBatch[];
  next_page: number | null;
}

const props = defineProps<{ history: ActivityRequestHistory }>();
const batches = ref<ActivityRequestHistory['data']>([]);
const loading = ref(false);
const { formatDate, formatDateTime } = useDateFormatter();

watch(() => props.history, (value) => {
  const incoming = new Set(value.data.map(batch => batch.id));
  batches.value = [...(value.next_page === 2 ? [] : batches.value).filter(batch => !incoming.has(batch.id)), ...value.data];
}, { immediate: true });

// One sending per batch and request type: the sender, time and note are shared, so they head the recipients once.
const sendings = computed(() => batches.value.flatMap(batch => Object.entries(batch.campaigns)
  .filter(([, requests]) => requests.length > 0)
  .map(([campaign, requests]) => ({
    key: `${batch.id}-${campaign}`,
    campaign,
    requester: requests[0].requester,
    sentAt: requests.map(request => request.created_at).sort()[0],
    notes: [...new Set(requests.map(request => request.note).filter((note): note is string => Boolean(note)))],
    requests,
  }))));

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

const loadMore = () => {
  loading.value = true;
  router.reload({
    only: ['activityRequests'],
    data: { activity_requests_page: props.history.next_page },
    onFinish: () => {
      loading.value = false;
    },
  });
};
</script>

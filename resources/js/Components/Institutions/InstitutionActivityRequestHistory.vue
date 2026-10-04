<template>
  <SectionCard data-slot="activity-request-history" :title="$t('activity_requests.history_title')">
    <p v-if="!batches.length" class="text-sm text-muted-foreground">
      {{ $t('activity_requests.history_empty') }}
    </p>
    <div v-for="batch in batches" :key="batch.id" class="border-b border-border py-4">
      <div v-for="(requests, campaign) in batch.campaigns" :key="campaign" class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wide">
          {{ $t(`activity_requests.campaigns.${campaign}`) }}
        </h3>
        <article v-for="request in requests" :key="request.id" class="space-y-1 border-l border-border pl-3 text-sm">
          <p>{{ request.recipient }} · {{ $t(`activity_requests.${request.status}`) }}</p>
          <p class="text-xs text-muted-foreground">
            {{ request.period_start }} – {{ request.period_end ?? $t('activity_requests.legacy_period') }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ request.requester ?? $t('activity_requests.automatic') }} · {{ dates.dayWithTime(request.created_at) }}
          </p>
          <p v-if="request.note">
            {{ request.note }}
          </p>
          <p v-if="request.answer">
            {{ $t(`activity_requests.done.${request.answer}`) }} · {{ request.answered_at ? dates.dayWithTime(request.answered_at) : '' }}
          </p>
          <p v-if="request.resolved_at" class="text-xs text-muted-foreground">
            {{ $t(`activity_requests.resolution.${request.resolution_source ?? 'meeting'}`) }} · {{ dates.dayWithTime(request.resolved_at) }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ $t('activity_requests.valid_until') }}: {{ dates.dayWithTime(request.expires_at) }}
          </p>
          <template v-for="meeting in request.meetings" :key="meeting.id">
            <Link v-if="meeting.url" :href="meeting.url" class="inline-flex min-h-11 items-center text-brand underline">
              {{ meeting.date }}
            </Link>
            <span v-else class="inline-block py-2">{{ meeting.date }}</span>
          </template>
          <p v-for="checkIn in request.check_ins" :key="checkIn.id" class="text-xs text-muted-foreground">
            {{ $t('activity_requests.no_meetings') }}: {{ checkIn.start }} – {{ checkIn.end }}
          </p>
        </article>
      </div>
    </div>
    <Button v-if="history.next_page" variant="outline" voice="sentence" class="mt-4 min-h-11" :disabled="loading" @click="loadMore">
      {{ $t('Rodyti daugiau') }}
    </Button>
  </SectionCard>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

import { useWindowDates } from '@/Components/ActionWindow/useWindowDates';
import { SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';

export interface ActivityRequestHistoryItem {
  id: string;
  recipient: string | null;
  requester: string | null;
  period_start: string;
  period_end: string | null;
  note: string | null;
  status: string;
  answer: string | null;
  created_at: string;
  answered_at: string | null;
  resolved_at: string | null;
  resolution_source: string | null;
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
const dates = useWindowDates();

watch(() => props.history, (value) => {
  const incoming = new Set(value.data.map(batch => batch.id));
  batches.value = [...(value.next_page === 2 ? [] : batches.value).filter(batch => !incoming.has(batch.id)), ...value.data];
}, { immediate: true });

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

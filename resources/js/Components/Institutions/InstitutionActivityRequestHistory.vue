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
            <span class="font-medium text-foreground">
              {{ sending.requester ? $t('activity_requests.history.sent_by', { name: sending.requester }) : $t('activity_requests.automatic') }}
            </span>
            · <time :datetime="sending.sentAt">{{ formatDateTime(sending.sentAt) }}</time>
          </p>
        </div>
        <blockquote v-for="note in sending.notes" :key="note" class="border-l-2 border-brand pl-3 text-sm leading-relaxed text-foreground">
          {{ note }}
        </blockquote>
        <ul class="divide-y divide-border border-y border-border">
          <ActivityRequestHistoryRow v-for="request in sending.requests" :key="request.id" :request />
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
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import ActivityRequestHistoryRow from './ActivityRequestHistoryRow.vue';

import { Button } from '@/Components/ui/button';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import type { ActivityRequestStatus } from '@/Constants/statuses';

export interface ActivityRequestHistoryItem {
  id: string;
  institution?: { id: string; name: string | null; url: string | null };
  campaign_type?: string;
  answer_url?: string | null;
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
const { formatDateTime } = useDateFormatter();

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

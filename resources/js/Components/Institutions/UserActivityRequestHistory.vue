<template>
  <div data-slot="user-activity-request-history">
    <EmptyState v-if="!requests.length" :title="$t('activity_requests.personal.empty_title')" :description="$t('activity_requests.personal.empty_description')" />
    <ul v-else class="divide-y divide-border border-y border-border">
      <ActivityRequestHistoryRow v-for="request in requests" :key="request.id" :request @answer="spotlight.dismiss()" />
    </ul>
    <Button v-if="history.next_page" variant="outline" voice="sentence" class="mt-4" :disabled="loading" @click="loadMore">
      {{ $t('Rodyti daugiau') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import ActivityRequestHistoryRow from './ActivityRequestHistoryRow.vue';
import type { ActivityRequestHistoryItem } from './InstitutionActivityRequestHistory.vue';

import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

export interface UserActivityRequestHistory {
  data: ActivityRequestHistoryItem[];
  page: number;
  next_page: number | null;
}

const props = defineProps<{ history: UserActivityRequestHistory }>();
const requests = ref<ActivityRequestHistoryItem[]>([]);
const loading = ref(false);
const spotlight = useFeatureSpotlight('user-activity-requests-v1');

watch(() => props.history, (history) => {
  const incoming = new Set(history.data.map(request => request.id));
  requests.value = [...(history.page === 1 ? [] : requests.value).filter(request => !incoming.has(request.id)), ...history.data];
}, { immediate: true });

function loadMore(): void {
  loading.value = true;
  router.reload({
    only: ['activityRequests'],
    data: { activity_requests_page: props.history.next_page },
    onFinish: () => { loading.value = false; },
  });
}
</script>

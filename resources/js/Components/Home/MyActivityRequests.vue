<template>
  <OverviewSection v-if="requests.total > 0" :title="$t('activity_requests.personal.active_title')" :icon="MailQuestion" variant="home" data-slot="home-activity-requests">
    <ul class="divide-y divide-border">
      <li v-for="request in requests.data" :key="request.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
        <div class="min-w-0 flex-1 space-y-1">
          <p class="text-sm font-semibold">
            {{ request.institution_name }}
          </p>
          <p class="text-sm text-muted-foreground">
            {{ $t(`activity_requests.campaigns.${request.campaign_type}`) }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ formatDate(request.period_start) }} – {{ request.period_end ? formatDate(request.period_end) : $t('activity_requests.legacy_period') }}
            · {{ $t('activity_requests.valid_until') }} {{ formatDate(request.expires_at) }}
          </p>
        </div>
        <Button as-child variant="outline" voice="sentence">
          <a :href="request.answer_url" @click="spotlight.dismiss()">{{ $t('activity_requests.personal.answer') }}</a>
        </Button>
      </li>
    </ul>
    <SpotlightPopover
      :title="$t('activity_requests.personal.spotlight_title')"
      :description="$t('activity_requests.personal.spotlight_body')"
      :is-dismissed="spotlight.isDismissed.value"
      @dismiss="spotlight.dismiss()"
    >
      <Button as-child variant="ghost" voice="sentence">
        <Link :href="route('profile.activityRequests')" @click="spotlight.dismiss()">
          {{ $t('activity_requests.personal.view_all', { count: String(requests.total) }) }}
        </Link>
      </Button>
    </SpotlightPopover>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { MailQuestion } from '@lucide/vue';

import { OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

export interface ActiveActivityRequests {
  total: number;
  data: Array<{ id: string; institution_name: string; campaign_type: string; period_start: string; period_end: string | null; expires_at: string; answer_url: string }>;
}

defineProps<{ requests: ActiveActivityRequests }>();
const { formatDate } = useDateFormatter();
const spotlight = useFeatureSpotlight('user-activity-requests-v1');
</script>

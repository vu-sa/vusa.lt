<template>
  <OverviewSection v-if="requests.total > 0" :title="$t('activity_requests.personal.active_title')" :icon="MailQuestion" variant="home" data-slot="home-activity-requests">
    <template #actions>
      <SpotlightPopover
        :title="$t('activity_requests.personal.spotlight_title')"
        :description="$t('activity_requests.personal.spotlight_body')"
        :is-dismissed="spotlight.isDismissed.value"
        @dismiss="spotlight.dismiss()"
      >
        <SectionHeaderAction
          :href="route('profile.activityRequests')"
          :label="$t('activity_requests.personal.view_all', { count: String(requests.total) })"
          trailing-chevron
          @click="spotlight.dismiss()"
        />
      </SpotlightPopover>
    </template>

    <CollectionRowList :items="requests.data" frame="attached" :item-key="request => request.id">
      <template #default="{ item: request }">
        <div :class="['flex flex-wrap items-center gap-x-4 gap-y-2', ROW_PADDING_CLASS]">
          <div class="min-w-0 flex-1 basis-56">
            <p class="font-semibold text-foreground">
              {{ request.institution_name }}
            </p>
            <p class="mt-0.5 text-xs text-muted-foreground">
              {{ $t(`activity_requests.campaigns.${request.campaign_type}`) }}
              · {{ formatDate(request.period_start) }} – {{ request.period_end ? formatDate(request.period_end) : $t('activity_requests.legacy_period') }}
              · {{ $t('activity_requests.valid_until') }} {{ formatDate(request.expires_at) }}
            </p>
          </div>
          <CollectionRowActions :actions="[answerAction(request)]" stacked @navigate="spotlight.dismiss()" />
        </div>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { MailQuestion, MessageSquareReply } from '@lucide/vue';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_PADDING_CLASS } from '@/Components/Collection/rowList';
import { OverviewSection } from '@/Components/Patterns';
import SectionHeaderAction from '@/Components/Patterns/SectionHeaderAction.vue';
import SpotlightPopover from '@/Components/Patterns/SpotlightPopover.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';

export interface ActiveActivityRequests {
  total: number;
  data: Array<{ id: string; institution_name: string; campaign_type: string; period_start: string; period_end: string | null; expires_at: string; answer_url: string }>;
}

defineProps<{ requests: ActiveActivityRequests }>();
const { formatDate } = useDateFormatter();
const spotlight = useFeatureSpotlight('user-activity-requests-v1');

type ActivityRequestRow = ActiveActivityRequests['data'][number];

// The answer page lives outside the Inertia app, so the action is a plain same-tab link.
const answerAction = (request: ActivityRequestRow): AdminRecordAction => ({
  key: 'answer',
  label: $t('activity_requests.personal.answer'),
  icon: MessageSquareReply,
  href: request.answer_url,
  plain: true,
  primary: true,
});
</script>

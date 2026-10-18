<template>
  <ActionWindowScreen :title="$t('shell.actions.ask_activity.title')" :subtitle="$t('activity_requests.campaign_spotlight')">
    <ActionChoiceList>
      <template v-for="campaign in campaigns" :key="campaign">
        <SpotlightPopover
          v-if="campaign === 'activity_confirmation'"
          :title="$t('activity_requests.recent_check_in_spotlight_title')"
          :description="$t('activity_requests.recent_check_in_spotlight_body')"
          :is-dismissed="spotlight.isDismissed.value"
          class="block w-full"
          @dismiss="spotlight.dismiss()"
        >
          <ActionChoiceButton
            :title="$t(`activity_requests.campaigns.${campaign}`)"
            :description="$t(`activity_requests.descriptions.${campaign}`)"
            :icon="MailQuestion"
            @click="choose(campaign)"
          />
        </SpotlightPopover>
        <ActionChoiceButton
          v-else
          :title="$t(`activity_requests.campaigns.${campaign}`)"
          :description="$t(`activity_requests.descriptions.${campaign}`)"
          :icon="MailQuestion"
          @click="choose(campaign)"
        />
      </template>
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { MailQuestion } from '@lucide/vue';

import ActionWindowScreen from '../ActionWindowScreen.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionChoiceButton from '../ActionChoiceButton.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';

const { advance, updateActivityRequest } = useActionWindow();
const spotlight = useFeatureSpotlight('activity-request-ended-check-in-v1');
const campaigns = ['activity_confirmation', 'missing_meetings'] as const;
const choose = (campaignType: typeof campaigns[number]) => {
  if (campaignType === 'activity_confirmation') spotlight.dismiss();
  updateActivityRequest({ campaignType });
  advance('activity.mode');
};
</script>

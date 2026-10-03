<template>
  <ActionWindowScreen :title="$t('shell.actions.ask_activity.title')" :subtitle="$t('activity_requests.campaign_spotlight')">
    <ActionChoiceList>
      <ActionChoiceButton
        v-for="campaign in campaigns"
        :key="campaign"
        :title="$t(`activity_requests.campaigns.${campaign}`)"
        :description="$t(`activity_requests.email.${campaign}`)"
        :icon="MailQuestion"
        @click="choose(campaign)"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>
<script setup lang="ts">
import { MailQuestion } from 'lucide-vue-next';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionChoiceButton from '../ActionChoiceButton.vue';
import { useActionWindow } from '@/Composables/useActionWindow';

const { draft, advance, updateActivityRequest } = useActionWindow();
const campaigns = ['activity_confirmation', 'missing_meetings'] as const;
const choose = (campaignType: typeof campaigns[number]) => {
  updateActivityRequest({ campaignType });
  advance(draft.activityRequest.institutions.length > 0 && draft.activityRequest.institutions.length <= 100 ? 'activity.review' : 'activity.institutions');
};
</script>

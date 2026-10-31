<template>
  <OverviewPage
    :eyebrow="$t('shell.workspaces.pradzia.title')"
    :title="$t('activity_requests.personal.title')"
    :lead="$t('activity_requests.personal.lead')"
  >
    <UserActivityRequestHistory :history="activityRequests" />
  </OverviewPage>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import UserActivityRequestHistory, { type UserActivityRequestHistory as History } from '@/Components/Institutions/UserActivityRequestHistory.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

defineProps<{ activityRequests: History }>();
const spotlight = useFeatureSpotlight('user-activity-requests-v1');
onMounted(() => {
  spotlight.dismiss();
});
</script>

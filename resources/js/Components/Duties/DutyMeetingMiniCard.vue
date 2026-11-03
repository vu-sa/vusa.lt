<template>
  <EntityLinkCard
    :href="route('meetings.show', meeting.id)"
    :eyebrow="label"
    :title
  >
    <template #leading>
      <DateBadge :date="meeting.start_time" />
    </template>
  </EntityLinkCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { DateBadge, EntityLinkCard } from '@/Components/Patterns';
import { formatDate } from '@/Utils/dateTime';

export interface MiniMeeting {
  id: string | number;
  title?: string | null;
  start_time: string;
}

const props = defineProps<{
  meeting: MiniMeeting;
  label: string;
}>();

const title = computed(() => {
  if (props.meeting.title && props.meeting.title.trim() !== '') {
    return props.meeting.title;
  }
  return `${formatDate(props.meeting.start_time, { format: 'full' })} ${$t('posėdis')}`;
});
</script>

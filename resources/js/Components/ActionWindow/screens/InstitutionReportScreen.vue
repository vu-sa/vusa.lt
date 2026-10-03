<template>
  <ActionWindowScreen :title="$t('action_window.report.title')" :subtitle="draft.institution?.name">
    <ActionChoiceList>
      <ActionChoiceButton
        :title="$t('action_window.report.meeting.title')"
        :description="$t('action_window.report.meeting.description')"
        :icon="CalendarPlus"
        @click="goTo('meeting.type')"
      />
      <ActionChoiceButton
        :title="$t('action_window.report.no_meeting.title')"
        :description="$t('action_window.report.no_meeting.description')"
        :icon="CalendarOff"
        @click="goTo('checkin.until')"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarOff, CalendarPlus } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';

// The institution is already known (a periodicity task names it), so both answers skip its picker.
const { draft, goTo } = useActionWindow();
</script>

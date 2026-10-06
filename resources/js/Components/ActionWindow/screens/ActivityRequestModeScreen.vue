<template>
  <ActionWindowScreen :title="$t('action_window.activity_request.mode.title')" :subtitle="$t('action_window.activity_request.mode.subtitle')">
    <ActionChoiceList>
      <ActionChoiceButton
        :title="$t('action_window.activity_request.mode.institutions')"
        :description="$t('action_window.activity_request.mode.institutions_hint')"
        :icon="Landmark"
        @click="choose('institutions')"
      />
      <ActionChoiceButton
        :title="$t('action_window.activity_request.mode.people')"
        :description="$t('action_window.activity_request.mode.people_hint')"
        :icon="Users"
        @click="choose('people')"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { Landmark, Users } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';

const { advance, updateActivityRequest } = useActionWindow();
const choose = (mode: 'institutions' | 'people') => {
  updateActivityRequest({ mode, unchecked: [] });
  advance(mode === 'people' ? 'activity.people' : 'activity.institutions');
};
</script>

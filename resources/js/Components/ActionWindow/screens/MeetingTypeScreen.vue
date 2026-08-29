<template>
  <ActionWindowScreen
    :title="$t('action_window.meeting.type.title')"
    :subtitle="$t('action_window.meeting.type.subtitle')"
  >
    <ActionChoiceList>
      <ActionChoiceButton
        v-for="option in options"
        :key="option.key"
        :title="option.label"
        :description="option.description"
        :icon="option.icon"
        :gradient="option.gradient"
        :selected="draft.meeting.type === option.value"
        :show-chevron="false"
        @click="pick(option.value)"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t, getActiveLanguage } from 'laravel-vue-i18n';
import { CircleDashed, Mail, Users, Video } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useActionWindow } from '@/Composables/useActionWindow';
import { getMeetingTypeOptions, MeetingType, type MeetingTypeValue } from '@/Types/MeetingType';

const { draft, advance, updateMeeting } = useActionWindow();

/**
 * "Gyvas susitikimas" and "Nuotolinis susitikimas" say everything already; only the
 * two that carry a consequence — a date-only deadline, or no type at all — explain
 * themselves.
 */
const EXTRAS: Record<string, { icon: typeof Users; gradient: string; descriptionKey?: string }> = {
  [MeetingType.InPerson]: { icon: Users, gradient: 'from-amber-500/15 to-orange-500/15 dark:from-amber-400/12 dark:to-orange-400/12' },
  [MeetingType.Remote]: { icon: Video, gradient: 'from-sky-500/15 to-indigo-500/15 dark:from-sky-400/12 dark:to-indigo-400/12' },
  [MeetingType.Email]: { icon: Mail, gradient: 'from-emerald-500/15 to-teal-500/15 dark:from-emerald-400/12 dark:to-teal-400/12', descriptionKey: 'action_window.meeting.type.email' },
  other: { icon: CircleDashed, gradient: 'from-zinc-500/15 to-zinc-400/15 dark:from-zinc-400/12 dark:to-zinc-300/12', descriptionKey: 'action_window.meeting.type.other' },
};

const options = computed(() =>
  getMeetingTypeOptions(getActiveLanguage() === 'en' ? 'en' : 'lt').map((option) => {
    const extra = EXTRAS[option.value ?? 'other']!;

    return {
      key: option.value ?? 'other',
      value: option.value,
      label: option.label,
      description: extra.descriptionKey ? $t(extra.descriptionKey) : undefined,
      icon: extra.icon,
      gradient: extra.gradient,
    };
  }),
);

const pick = (type: MeetingTypeValue) => {
  updateMeeting({ type });
  advance('meeting.when');
};
</script>

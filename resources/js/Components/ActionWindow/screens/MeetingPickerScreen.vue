<template>
  <ActionWindowScreen
    :title="$t('action_window.meeting_picker.title')"
    :subtitle="$t('action_window.meeting_picker.subtitle')"
  >
    <div v-if="isLoading" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full rounded-xl" />
    </div>

    <EmptyState v-else-if="meetings.length === 0" :title="emptyTitle" description="">
      <template #icon>
        <CheckCircle2 class="size-10 text-muted-foreground" />
      </template>
    </EmptyState>

    <ActionChoiceList v-else>
      <ActionChoiceButton
        v-for="meeting in meetings"
        :key="meeting.id"
        :title="meeting.institution_name"
        :icon="meeting.completion_status === 'no_items' ? ListX : FileQuestion"
        :gradient="meeting.completion_status === 'no_items'
          ? 'from-red-500/20 to-rose-500/15 dark:from-red-400/15 dark:to-rose-400/12'
          : 'from-amber-500/20 to-orange-500/15 dark:from-amber-400/15 dark:to-orange-400/12'"
        @click="openMeeting(meeting)"
      >
        <template #description>
          {{ dates.fullDay(meeting.start_time) }} · {{ gapLabel(meeting) }}
        </template>
      </ActionChoiceButton>
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CheckCircle2, FileQuestion, ListX } from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData, type ActionWindowMeeting } from '@/Composables/useActionWindowData';
import { EmptyState } from '@/Components/Patterns';
import { Skeleton } from '@/Components/ui/skeleton';

const { close } = useActionWindow();
const { meetings, isLoading, error, load } = useActionWindowData();

onMounted(load);

const emptyTitle = computed(() =>
  error.value ? $t('action_window.common.error') : $t('action_window.meeting_picker.empty'),
);

const dates = useWindowDates();

const gapLabel = (meeting: ActionWindowMeeting) =>
  meeting.completion_status === 'no_items'
    ? $t('action_window.meeting_picker.missing_agenda')
    : $t('action_window.meeting_picker.missing_decisions');

const openMeeting = (meeting: ActionWindowMeeting) => {
  close();
  router.visit(route('meetings.show', meeting.id));
};
</script>

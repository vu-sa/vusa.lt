<template>
  <div class="flex flex-col gap-5">
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input
        v-model="options.title"
        type="text"
        :placeholder="$t('rich-content.timetable_heading_placeholder')"
      />
    </Field>

    <!-- Import from meeting — pre-fills rows as a static snapshot. The data lives in
         the page's content afterwards, so a later agenda edit never silently reflows
         a published timetable. -->
    <div class="flex items-center gap-3">
      <Button variant="outline" size="sm" @click="openImportDialog">
        <IFluentArrowImport24Regular class="size-4" />
        {{ $t('rich-content.import_from_meeting') }}
      </Button>
      <span v-if="importError" class="text-xs text-red-600 dark:text-red-400">{{ importError }}</span>
    </div>

    <DynamicListInput
      v-model="rows"
      :create-item="createRow"
      :empty-text="$t('rich-content.no_timetable_rows')"
      :add-first-text="$t('rich-content.add_first_timetable_row')"
      :add-text="$t('rich-content.add_timetable_row')"
      compact
      allow-empty>
      <template #item="{ item, update }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <Field>
            <FieldLabel>{{ $t('rich-content.start_time') }}</FieldLabel>
            <Input
              :model-value="item.startTime"
              type="time"
              @update:model-value="update({ ...item, startTime: $event })"
            />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.end_time') }}</FieldLabel>
            <Input
              :model-value="item.endTime"
              type="time"
              @update:model-value="update({ ...item, endTime: $event })"
            />
          </Field>
          <Field class="sm:flex-1">
            <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
            <Input
              :model-value="item.title"
              type="text"
              :placeholder="$t('rich-content.enter_title')"
              @update:model-value="update({ ...item, title: $event })"
            />
          </Field>
        </div>
      </template>
    </DynamicListInput>

    <Dialog v-model:open="importOpen">
      <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.import_from_meeting') }}</DialogTitle>
        </DialogHeader>

        <div v-if="importPending" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
          {{ $t('rich-content.loading_meetings') }}
        </div>
        <div v-else-if="recentMeetings.length === 0" class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
          {{ $t('rich-content.no_recent_meetings') }}
        </div>
        <ul v-else class="flex flex-col gap-1">
          <li v-for="meeting in recentMeetings" :key="meeting.id">
            <button
              type="button"
              class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
              @click="selectMeeting(meeting.id)"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate font-medium text-zinc-800 dark:text-zinc-100">{{ meeting.title }}</span>
                <span class="block text-xs text-zinc-400 dark:text-zinc-500">{{ meeting.institution_name }}</span>
              </span>
              <IFluentArrowImport24Regular class="size-4 shrink-0 text-zinc-400" />
            </button>
          </li>
        </ul>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ref, watch } from 'vue';

import type { Timetable } from '@/Types/contentParts';
import { useApi } from '@/Composables/useApi';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import IFluentArrowImport24Regular from '~icons/fluent/arrow-import-24-regular';

const options = defineModel<Timetable['options']>('options', { default: () => ({ title: undefined }) });
const rows = defineModel<Timetable['json_content']>({ default: () => [] });

function createRow(): Timetable['json_content'][number] {
  return { startTime: '', endTime: '', title: '' };
}

interface RecentMeeting {
  id: string;
  title: string;
  institution_name: string;
}

const importOpen = ref(false);
const importError = ref<string | null>(null);

const { data: recentMeetingsData, execute: fetchRecent, isFetching: recentFetching } = useApi<RecentMeeting[]>(
  route('api.v1.admin.meetings.recent'),
  { immediate: false },
);

const recentMeetings = ref<RecentMeeting[]>([]);
const importPending = ref(false);

watch(recentMeetingsData, (value) => {
  if (value) recentMeetings.value = value;
});

async function openImportDialog() {
  importError.value = null;
  importOpen.value = true;
  importPending.value = true;
  await fetchRecent();
  importPending.value = recentFetching.value;
}

const selectedMeetingId = ref<string | null>(null);
const { data: agendaData, execute: executeAgenda } = useApi<Timetable['json_content']>(
  // Reactive URL — re-evaluated when the selected meeting changes.
  () => selectedMeetingId.value
    ? route('api.v1.admin.meetings.agendaItems', { meeting: selectedMeetingId.value })
    : '',
  { immediate: false },
);

watch(agendaData, (value) => {
  if (!value) return;
  rows.value = value.map(item => ({
    startTime: item.startTime ?? '',
    endTime: item.endTime ?? '',
    title: item.title,
  }));
  importOpen.value = false;
});

async function selectMeeting(meetingId: string) {
  selectedMeetingId.value = meetingId;
  await executeAgenda();
}

<template>
  <div class="flex flex-col gap-5">
    <div v-if="importOpen && isMobile" class="flex flex-col gap-4">
      <Button variant="ghost" size="sm" class="self-start" @click="importOpen = false">
        <ArrowLeft class="size-4" />
        {{ $t('rich-content.back_to_timetable') }}
      </Button>
      <h3 class="text-sm font-semibold text-foreground">{{ $t('rich-content.import_from_meeting') }}</h3>
      <p v-if="importPending" class="py-8 text-center text-sm text-muted-foreground">{{ $t('rich-content.loading_meetings') }}</p>
      <p v-else-if="importError" role="alert" class="py-4 text-sm text-status-danger">{{ importError }}</p>
      <p v-else-if="recentMeetings.length === 0" class="py-8 text-center text-sm text-muted-foreground">{{ $t('rich-content.no_recent_meetings') }}</p>
      <ul v-else class="divide-y divide-border border-y border-border">
        <li v-for="meeting in recentMeetings" :key="meeting.id">
          <button type="button" class="flex min-h-11 w-full items-center justify-between gap-3 py-3 text-left hover:bg-accent" :disabled="agendaPending" @click="selectMeeting(meeting.id)">
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-medium text-foreground">{{ meeting.title }}</span>
              <span class="block text-xs text-muted-foreground">{{ meeting.institution_name }}</span>
            </span>
            <ArrowDownToLine class="size-4 shrink-0 text-muted-foreground" />
          </button>
        </li>
      </ul>
    </div>
    <template v-else>
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
        <ArrowDownToLine class="size-4" />
        {{ $t('rich-content.import_from_meeting') }}
      </Button>
      <span v-if="importError" role="alert" class="text-xs text-status-danger">{{ importError }}</span>
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

    </template>
    <Dialog v-if="!isMobile" v-model:open="importOpen">
      <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.import_from_meeting') }}</DialogTitle>
        </DialogHeader>

        <div v-if="importPending" class="py-8 text-center text-sm text-muted-foreground">
          {{ $t('rich-content.loading_meetings') }}
        </div>
        <div v-else-if="importError" role="alert" class="py-4 text-sm text-status-danger">{{ importError }}</div>
        <div v-else-if="recentMeetings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
          {{ $t('rich-content.no_recent_meetings') }}
        </div>
        <ul v-else class="divide-y divide-border border-y border-border">
          <li v-for="meeting in recentMeetings" :key="meeting.id">
            <button
              type="button"
              class="flex min-h-11 w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition-colors hover:bg-accent"
              :disabled="agendaPending"
              @click="selectMeeting(meeting.id)"
            >
              <span class="min-w-0 flex-1">
                <span class="block truncate font-medium text-foreground">{{ meeting.title }}</span>
                <span class="block text-xs text-muted-foreground">{{ meeting.institution_name }}</span>
              </span>
              <ArrowDownToLine class="size-4 shrink-0 text-muted-foreground" />
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
import { ArrowDownToLine, ArrowLeft } from 'lucide-vue-next';

import type { Timetable } from '@/Types/contentParts';
import { useApi } from '@/Composables/useApi';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { useIsMobile } from '@/Composables/useIsMobile';

const options = defineModel<Timetable['options']>('options', { default: () => ({ title: undefined }) });
const rows = defineModel<Timetable['json_content']>({ default: () => [] });
const isMobile = useIsMobile();

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

const { data: recentMeetingsData, execute: fetchRecent, error: recentError } = useApi<RecentMeeting[]>(
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
  importPending.value = false;
  importError.value = recentError.value;
}

const selectedMeetingId = ref<string | null>(null);
const { data: agendaData, execute: executeAgenda, error: agendaError, isFetching: agendaPending } = useApi<Timetable['json_content']>(
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
  importError.value = null;
  await executeAgenda();
  importError.value = agendaError.value;
}
</script>

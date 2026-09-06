<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <!-- Title -->
      <Field>
        <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
        <Input
          :model-value="options.title"
          type="text"
          :placeholder="$t('rich-content.timetable_heading_placeholder')"
          @update:model-value="updateTitle(String($event))"
        />
      </Field>

      <!-- Import from meeting -->
      <div class="flex items-center gap-2">
        <Button variant="outline" size="sm" class="w-full" @click="openImportDialog">
          <IFluentArrowImport24Regular class="size-3.5 mr-1" />
          {{ $t('rich-content.import_from_meeting') }}
        </Button>
      </div>

      <!-- Rows header and Add button -->
      <div class="flex items-center justify-between border-b border-border pb-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('rich-content.rows') }} ({{ rows.length }})
        </span>
        <Button variant="outline" size="sm" @click="addRow">
          <IFluentAdd12Regular class="mr-1 size-3.5" />
          {{ $t('rich-content.add_timetable_row') }}
        </Button>
      </div>

      <!-- Rows list -->
      <div v-if="rows.length > 0" class="flex flex-col gap-1.5 max-h-48 overflow-y-auto pr-0.5">
        <div
          v-for="(row, index) in rows"
          :key="index"
          class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 p-1.5 text-xs"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-foreground">
              {{ row.title || `${$t('rich-content.row')} ${index + 1}` }}
            </p>
            <p class="truncate text-xs text-muted-foreground tabular-nums">
              {{ row.startTime || '--:--' }} – {{ row.endTime || '--:--' }}
            </p>
          </div>

          <div class="flex items-center gap-0.5 shrink-0">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-6"
              :disabled="index === 0"
              :title="$t('rich-content.move_up')"
              @click="moveRow(index, -1)"
            >
              <IFluentArrowUp12Regular class="size-3" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-6"
              :disabled="index === rows.length - 1"
              :title="$t('rich-content.move_down')"
              @click="moveRow(index, 1)"
            >
              <IFluentArrowDown12Regular class="size-3" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-6 text-muted-foreground hover:text-destructive"
              :title="$t('rich-content.delete')"
              @click="removeRow(index)"
            >
              <IFluentDelete12Regular class="size-3" />
            </Button>
          </div>
        </div>
      </div>

      <!-- Width Picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <!-- Meeting import dialog -->
      <Dialog v-model:open="importOpen">
        <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{{ $t('rich-content.import_from_meeting') }}</DialogTitle>
          </DialogHeader>

          <div v-if="importPending" class="py-8 text-center text-sm text-muted-foreground">
            {{ $t('rich-content.loading_meetings') }}
          </div>
          <div v-else-if="recentMeetings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
            {{ $t('rich-content.no_recent_meetings') }}
          </div>
          <ul v-else class="flex flex-col gap-1">
            <li v-for="meeting in recentMeetings" :key="meeting.id">
              <button
                type="button"
                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors hover:bg-muted"
                @click="selectMeeting(meeting.id)"
              >
                <span class="min-w-0 flex-1">
                  <span class="block truncate font-medium text-foreground">{{ meeting.title }}</span>
                  <span class="block text-xs text-muted-foreground">{{ meeting.institution_name }}</span>
                </span>
                <IFluentArrowImport24Regular class="size-4 shrink-0 text-muted-foreground" />
              </button>
            </li>
          </ul>
        </DialogContent>
      </Dialog>
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import { useApi } from '@/Composables/useApi';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import type { Timetable } from '@/Types/contentParts';
import IFluentArrowImport24Regular from '~icons/fluent/arrow-import-24-regular';
import IFluentAdd12Regular from '~icons/fluent/add-12-regular';
import IFluentArrowUp12Regular from '~icons/fluent/arrow-up-12-regular';
import IFluentArrowDown12Regular from '~icons/fluent/arrow-down-12-regular';
import IFluentDelete12Regular from '~icons/fluent/delete-12-regular';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const options = computed<NonNullable<Timetable['options']>>(
  () => (props.content.options ?? {}) as NonNullable<Timetable['options']>,
);

const rows = computed<Timetable['json_content']>(
  () => (props.content.json_content ?? []) as Timetable['json_content'],
);

const contentType = computed(() => getContentType('timetable'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (options.value.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function updateTitle(title: string): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...options.value,
      title,
    },
  });
}

function addRow(): void {
  emit('update:content', {
    ...props.content,
    json_content: [
      ...rows.value,
      { startTime: '09:00', endTime: '10:00', title: '' },
    ],
  });
}

function removeRow(index: number): void {
  const next = [...rows.value];
  next.splice(index, 1);
  emit('update:content', { ...props.content, json_content: next });
}

function moveRow(fromIndex: number, delta: number): void {
  const toIndex = fromIndex + delta;
  if (toIndex < 0 || toIndex >= rows.value.length) return;
  const next = [...rows.value];
  const item = next.splice(fromIndex, 1)[0];
  if (item) {
    next.splice(toIndex, 0, item);
    emit('update:content', { ...props.content, json_content: next });
  }
}

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

// Meeting import dialog logic
interface RecentMeeting {
  id: string;
  title: string;
  institution_name: string;
}

const importOpen = ref(false);
const recentMeetings = ref<RecentMeeting[]>([]);
const importPending = ref(false);
const selectedMeetingId = ref<string | null>(null);

const { data: recentMeetingsData, execute: fetchRecent, isFetching: recentFetching } = useApi<RecentMeeting[]>(
  route('api.v1.admin.meetings.recent'),
  { immediate: false },
);

const { data: agendaData, execute: executeAgenda } = useApi<Timetable['json_content']>(
  () => (selectedMeetingId.value
    ? route('api.v1.admin.meetings.agendaItems', { meeting: selectedMeetingId.value })
    : ''),
  { immediate: false },
);

watch(recentMeetingsData, (value) => {
  if (value) recentMeetings.value = value;
});

watch(agendaData, (value) => {
  if (!value) return;
  const importedRows = value.map(item => ({
    startTime: item.startTime ?? '',
    endTime: item.endTime ?? '',
    title: item.title,
  }));
  emit('update:content', { ...props.content, json_content: importedRows });
  importOpen.value = false;
});

async function openImportDialog() {
  importOpen.value = true;
  importPending.value = true;
  await fetchRecent();
  importPending.value = recentFetching.value;
}

async function selectMeeting(meetingId: string) {
  selectedMeetingId.value = meetingId;
  await executeAgenda();
}
</script>

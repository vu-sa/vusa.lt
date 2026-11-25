<template>
  <div class="space-y-6">
    <MeetingAgendaList
      v-model:editing="editing"
      :agenda-items="meeting.agenda_items ?? []"
      :meeting-id="meeting.id"
      :can-add
      :can-reorder
      :requires-student-perspective
      :loading-item-id="editorLoadingId"
      @add="emit('add', $event)"
      @edit="openEditor"
      @delete="requestDelete"
    />
    <AgendaItemEditSheet
      v-if="editorItem"
      :key="editorKey"
      v-model:open="editorOpen"
      :item="editorItem"
      :default-start-time="previousItemEnd(editorItem.order)"
      :meeting-start-time="isEmailMeeting(meeting) ? null : formatTime(meeting.start_time)"
      :requires-student-perspective
      :is-public
      :can-delete="itemBeingEdited?.can?.delete ?? false"
      :except="['documents', 'tasks', 'pendingDocuments']"
      @delete="deleteFromEditor"
    />
    <ConfirmDialog
      v-model:open="deleteDialogOpen"
      :title="$t('Šalinti darbotvarkės punktą?')"
      :description="$t('Ar tikrai nori ištrinti šį darbotvarkės punktą? Šis veiksmas negrįžtamas.')"
      :confirm-label="$t('Šalinti')"
      destructive
      @confirm="confirmDelete"
    >
      <p v-if="pendingDelete" class="border border-border bg-secondary/60 px-3 py-2 text-sm font-medium text-foreground">
        {{ pendingDelete.title }}
      </p>
    </ConfirmDialog>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import AgendaItemEditSheet from '@/Components/AgendaItems/AgendaItemEditSheet.vue';
import MeetingAgendaList from '@/Components/Meetings/MeetingAgendaList.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { useApi } from '@/Composables/useApi';
import type { EditableAgendaItemSource } from '@/Composables/useAgendaItemAutosave';
import type { AgendaListItem, FullAgendaItem, MeetingRecord } from '@/Types/MeetingPage';
import { formatTime } from '@/Utils/dateTime';
import { isEmailMeeting } from '@/Utils/MeetingDisplay';

import type { AddAgendaMode } from './AddAgendaItemsSheet.vue';

type EditableItem = EditableAgendaItemSource & { id: string; order: number };

/** A meeting's agenda tab: the list, its edit sheet (each translation loaded on open) and delete confirmation. */
const props = defineProps<{
  meeting: MeetingRecord;
  canAdd: boolean;
  canReorder: boolean;
  requiresStudentPerspective: boolean;
  isPublic: boolean;
}>();

/** Read-only by default; on while adding, reordering or deleting items. */
const editing = defineModel<boolean>('editing', { default: false });

const emit = defineEmits<{ add: [mode: AddAgendaMode] }>();

const editorOpen = ref(false);
const itemBeingEdited = ref<AgendaListItem | null>(null);
const editorItem = ref<EditableItem | null>(null);
const editorKey = ref(0);
const editorLoadingId = ref<string | null>(null);
const editorUrl = ref('');
const editorApi = useApi<EditableItem>(editorUrl, { immediate: false, refetch: false });

async function openEditor({ id }: Pick<AgendaListItem, 'id'>): Promise<void> {
  const item = props.meeting.agenda_items.find(candidate => candidate.id === id);
  if (!item) return;

  editorLoadingId.value = item.id;
  editorUrl.value = route('api.v1.admin.agendaItems.editable', item.id);
  await editorApi.execute();

  // Another item was opened while this one loaded.
  if (editorLoadingId.value !== item.id) return;
  editorLoadingId.value = null;

  if (editorApi.isSuccess.value && editorApi.data.value) {
    itemBeingEdited.value = item;
    editorItem.value = editorApi.data.value;
    editorKey.value++;
    editorOpen.value = true;
  }
}

function previousItemEnd(order: number): string | null {
  const previous = (props.meeting.agenda_items ?? [])
    .filter((item): item is FullAgendaItem => !item.is_redacted && item.order < order && !!item.end_time)
    .sort((a, b) => b.order - a.order)[0];

  return previous?.end_time ? String(previous.end_time).slice(0, 5) : null;
}

const deleteDialogOpen = ref(false);
const pendingDelete = ref<AgendaListItem | null>(null);

function requestDelete(item: AgendaListItem): void {
  pendingDelete.value = item;
  deleteDialogOpen.value = true;
}

function deleteFromEditor(): void {
  editorOpen.value = false;
  if (itemBeingEdited.value) {
    requestDelete(itemBeingEdited.value);
  }
}

function confirmDelete(): void {
  const target = pendingDelete.value;
  if (!target) return;
  router.delete(route('agendaItems.destroy', target.id), {
    preserveScroll: true,
    except: ['documents', 'pendingDocuments'],
    onFinish: () => {
      // A second confirmation may have opened meanwhile; leave its target alone.
      if (pendingDelete.value === target) {
        pendingDelete.value = null;
      }
    },
  });
}
</script>

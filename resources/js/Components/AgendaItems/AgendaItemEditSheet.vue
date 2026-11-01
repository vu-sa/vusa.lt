<template>
  <AgendaItemSheetForm
    :open
    :created-at="item.created_at"
    :updated-at="item.updated_at"
    :form
    :save-then
    :default-start-time
    :meeting-start-time
    :requires-student-perspective
    :is-public
    :can-delete
    :order="item.order"
    @update:open="emit('update:open', $event)"
    @delete="emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import AgendaItemSheetForm from './AgendaItemSheetForm.vue';

import { agendaItemFormData, useAgendaItemAutosave, type AgendaItemFormData, type EditableAgendaItemSource } from '@/Composables/useAgendaItemAutosave';

/** The agenda item sheet for a page that lists items and has no form of its own. */
const props = withDefaults(defineProps<{
  open: boolean;
  item: EditableAgendaItemSource & { id: string; order: number; created_at?: string | null; updated_at?: string | null };
  defaultStartTime?: string | null;
  meetingStartTime?: string | null;
  requiresStudentPerspective?: boolean;
  isPublic?: boolean;
  canDelete?: boolean;
  except?: string[];
}>(), {
  defaultStartTime: null,
  meetingStartTime: null,
  requiresStudentPerspective: true,
  isPublic: false,
  canDelete: false,
  except: () => [],
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'delete': [];
}>();

const form = useForm<AgendaItemFormData>(agendaItemFormData(props.item));
const { saveThen } = useAgendaItemAutosave(form, props.item.id, 700, props.except);
</script>

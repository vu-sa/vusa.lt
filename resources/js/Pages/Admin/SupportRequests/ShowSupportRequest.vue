<template>
  <RecordPage v-model:section="section" :title="supportRequest.title" entity-type="support_request" :status="statusPresentation" :facts :sections :primary-action :overflow-actions @action="handleAction">
    <template #alert>
      <div v-if="permissions.can_update_status || permissions.can_assign" class="flex flex-wrap items-center gap-3 border border-border bg-card p-3">
        <label v-if="permissions.can_update_status" class="flex items-center gap-2 text-sm font-medium">
          {{ $t('Būsena') }}
          <Select :model-value="statusValue" @update:model-value="changeStatus">
            <SelectTrigger class="w-44"><SelectValue /></SelectTrigger>
            <SelectContent><SelectItem v-for="status in availableStatuses" :key="status.value" :value="status.value">{{ status.label }}</SelectItem></SelectContent>
          </Select>
        </label>
        <label v-if="permissions.can_assign" class="flex items-center gap-2 text-sm font-medium">
          {{ $t('Priskirta') }}
          <Select :model-value="supportRequest.assignedTo?.id ?? 'unassigned'" @update:model-value="assign">
            <SelectTrigger class="w-52"><SelectValue /></SelectTrigger>
            <SelectContent><SelectItem value="unassigned">{{ $t('Nepriskirta') }}</SelectItem><SelectItem v-for="assignee in assignees" :key="assignee.id" :value="assignee.id">{{ assignee.name }}</SelectItem></SelectContent>
          </Select>
        </label>
      </div>
    </template>
    <template #description>
      <div class="max-w-3xl space-y-6">
        <div v-if="supportRequest.context_url || supportRequest.selected_text" class="border border-border bg-card p-4 text-sm">
          <a v-if="supportRequest.context_url" :href="supportRequest.context_url" target="_blank" rel="noopener noreferrer" class="break-all underline underline-offset-4">{{ supportRequest.context_url }}</a>
          <blockquote v-if="supportRequest.selected_text" class="mt-3 border-l-2 border-primary pl-3 italic">
            „{{ supportRequest.selected_text }}“
          </blockquote>
        </div>
        <p class="whitespace-pre-wrap text-sm leading-relaxed">
          {{ supportRequest.description }}
        </p>
      </div>
    </template>
    <template #evidence>
      <div class="grid max-w-4xl gap-px border border-border bg-border sm:grid-cols-2 lg:grid-cols-3">
        <a v-for="file in supportRequest.media ?? []" :key="file.id" :href="file.original_url" target="_blank" rel="noopener noreferrer" class="bg-card p-3 hover:bg-accent">
          <img :src="file.thumb_url || file.original_url" :alt="file.name" class="mb-3 h-32 w-full object-cover">
          <p class="truncate text-sm font-medium">{{ file.file_name }}</p>
        </a>
        <p v-if="!supportRequest.media?.length" class="bg-card p-6 text-sm text-muted-foreground">
          {{ $t('Prisegtų failų nėra.') }}
        </p>
      </div>
    </template>
    <template #discussion>
      <DiscussionPanel commentable-type="supportRequest" :commentable-id="supportRequest.id" />
    </template>
  </RecordPage>
  <ConfirmDialog v-model:open="deleteOpen" :title="$t('Šalinti pranešimą?')" :description="$t('Pranešimas bus perkeltas į šiukšlinę.')" :confirm-label="$t('Šalinti')" destructive @confirm="router.delete(route('supportRequests.destroy', supportRequest.id))" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Edit, Trash2 } from 'lucide-vue-next';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { supportRequestStatuses } from '@/Constants/statuses';
import type { SupportRequestStatus } from '@/Types/enums';
import type { SupportRequestItem } from '@/Types/supportRequests';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  supportRequest: SupportRequestItem;
  availableStatuses: Array<{ value: string; label: string; badgeVariant: string }>;
  assignees: Array<{ id: string; name: string }>;
  permissions: { can_update: boolean; can_update_status: boolean; can_assign: boolean; can_delete: boolean; can_restore: boolean };
}>();
const section = ref('description');
const deleteOpen = ref(false);
const statusValue = computed(() => typeof props.supportRequest.status === 'object' ? props.supportRequest.status.value : props.supportRequest.status);
const statusPresentation = computed(() => supportRequestStatuses[statusValue.value as SupportRequestStatus]);
const page = usePage();
const locale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale ?? 'lt');
const creatorName = computed(() => props.supportRequest.creator?.name ?? props.supportRequest.reporter_name ?? $t('Svečias'));
const facts = computed<RecordFact[]>(() => [
  { key: 'creator', label: $t('Pateikė'), value: creatorName.value },
  { key: 'created', label: $t('Pateikta'), value: formatDate(props.supportRequest.created_at) },
  { key: 'assignee', label: $t('Priskirta'), value: props.supportRequest.assignedTo?.name ?? $t('Nepriskirta') },
  { key: 'type', label: $t('Tipas'), value: typeof props.supportRequest.type?.name === 'string' ? props.supportRequest.type.name : props.supportRequest.type?.name?.[locale.value] ?? '—' },
]);
const sections = computed<RecordPageSection[]>(() => [
  { value: 'description', label: $t('Aprašymas') },
  { value: 'evidence', label: $t('Failai'), count: props.supportRequest.media?.length },
  { value: 'discussion', label: $t('Diskusija') },
]);
const primaryAction = computed<RecordAction | undefined>(() => props.permissions.can_update ? { key: 'edit', label: $t('Redaguoti'), icon: Edit } : undefined);
const overflowActions = computed<RecordAction[]>(() => props.permissions.can_delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []);
function changeStatus(value: string): void {
  if (value !== statusValue.value) {
    router.patch(route('supportRequests.status.update', props.supportRequest.id), { status: value }, { preserveScroll: true });
  }
}
function assign(value: string): void {
  router.patch(route('supportRequests.assign', props.supportRequest.id), { assigned_to: value === 'unassigned' ? null : value }, { preserveScroll: true });
}
function handleAction(action: string): void {
  if (action === 'edit') {
    router.visit(route('supportRequests.edit', props.supportRequest.id));
  }

  if (action === 'delete') {
    deleteOpen.value = true;
  }
}
usePageBreadcrumbs(() => [BreadcrumbHelpers.createRouteBreadcrumb($t('vusa.lt pagalba'), 'mySupportRequests.index'), BreadcrumbHelpers.createBreadcrumbItem(props.supportRequest.title)]);
</script>

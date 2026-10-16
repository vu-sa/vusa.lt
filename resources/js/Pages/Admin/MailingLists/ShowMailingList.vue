<template>
  <RecordPage
    v-model:section="currentSection"
    :aria-busy="processing"
    :history-subject="canView ? { type: 'mailing_list', id: mailingList.id } : undefined"
    :title="mailingList.display_name"
    :entity-type="ModelEnum.MAILING_LIST"
    :status="mailingListModes[mailingList.mode]"
    :facts
    :sections
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleAction"
  >
    <template #subtitle>
      <span class="text-sm text-muted-foreground">{{ mailingList.address }}</span>
    </template>

    <template v-if="can.update && !sources.length" #next-actions>
      <RecordNextActions :actions="[{ key: 'sources', label: $t('mailing_lists.sources.add'), icon: Layers, run: showSources }]" />
    </template>

    <template v-if="canView && (!mailmanConfigured || mailingList.last_sync_status === 'needs_review' || mailingList.last_sync_status === 'failed')" #alert>
      <p class="border-l-2 border-status-attention-border pl-3 text-sm">
        {{ !mailmanConfigured ? $t('mailing_lists.not_configured') : mailingList.last_sync_status === 'failed'
          ? (mailingList.last_sync_error ?? $t('mailing_lists.messages.sync_failed'))
          : $t('mailing_lists.diff.needs_review') }}
      </p>
    </template>

    <template v-if="canView" #gavejai>
      <Deferred data="roster">
        <template #fallback>
          <CollectionSkeleton :rows="4" />
        </template>
        <MailingListRecipients
          :mailing-list-id="mailingList.id"
          :roster="roster ?? []"
          :diff="diff ?? null"
          :managed
          :can-update="can.update"
        />
      </Deferred>
    </template>

    <template v-if="canView" #saltiniai>
      <MailingListSources
        :mailing-list-id="mailingList.id"
        :sources
        :exclusions
        :can-update="can.update"
        :source-options
      />
    </template>

    <template #archyvas>
      <Deferred data="messages">
        <template #fallback>
          <CollectionSkeleton :rows="4" />
        </template>
        <p v-if="!mailingList.archive_complete" class="mb-4 text-sm text-muted-foreground">
          {{ $t('mailing_lists.archive.importing', { count: String(mailingList.archive_imported), total: String(mailingList.archive_total) }) }}
        </p>
        <InfiniteScroll data="messages" manual only-next preserve-url>
          <MailingListArchive :messages="messages?.data ?? []" />
          <template #next="{ loading, fetch, hasMore }">
            <Button v-if="hasMore" variant="outline" voice="sentence" :disabled="loading" class="mt-4" @click="fetch">
              {{ $t(loading ? 'Įkeliama…' : 'Rodyti daugiau') }}
            </Button>
          </template>
        </InfiniteScroll>
      </Deferred>
    </template>
  </RecordPage>

  <MailingListSheetForm v-model:open="editOpen" :tenants :mailing-list />

  <ConfirmDialog
    v-model:open="confirmManagedOpen"
    :title="$t('mailing_lists.confirm_managed.title')"
    :description="$t('mailing_lists.confirm_managed.description', { add: String(confirmationDiff?.add?.length ?? 0), remove: String(confirmationDiff?.remove?.length ?? 0) })"
    :confirm-label="$t('mailing_lists.confirm_managed.confirm')"
    @confirm="applyReviewed"
  />

  <ConfirmDialog
    v-model:open="confirmUnmanagedOpen"
    :title="$t('mailing_lists.confirm_unmanaged.title')"
    :description="$t('mailing_lists.confirm_unmanaged.description')"
    :confirm-label="$t('mailing_lists.confirm_unmanaged.confirm')"
    @confirm="setMode('unmanaged')"
  />
</template>

<script setup lang="ts">
import { Deferred, InfiniteScroll, router } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Copy, Hand, Layers, Pencil, RefreshCw } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { CollectionSkeleton, ConfirmDialog, RecordNextActions } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { mailingListModes, mailingListSyncStatuses } from '@/Constants/statuses';
import MailingListArchive from '@/Features/Admin/MailingLists/MailingListArchive.vue';
import MailingListRecipients from '@/Features/Admin/MailingLists/MailingListRecipients.vue';
import MailingListSheetForm from '@/Features/Admin/MailingLists/MailingListSheetForm.vue';
import MailingListSources from '@/Features/Admin/MailingLists/MailingListSources.vue';
import type {
  MailingListArchivePage,
  MailingListDiffPayload,
  MailingListExclusionItem,
  MailingListRosterEntry,
  MailingListSourceItem,
  MailingListSourceOptions,
  MailingListSummary,
} from '@/Features/Admin/MailingLists/types';
import { MailingListMode, ModelEnum } from '@/Types/enums';
import { formatDate, formatNearDate } from '@/Utils/dateTime';

const props = defineProps<{
  mailingList: MailingListSummary & {
    last_sync_error: string | null;
    archive_fetched_at: string | null;
    archive_complete: boolean;
    archive_imported: number;
    archive_total: number;
  };
  sources: MailingListSourceItem[];
  exclusions: MailingListExclusionItem[];
  roster?: MailingListRosterEntry[];
  diff?: MailingListDiffPayload | null;
  messages?: MailingListArchivePage;
  sourceOptions?: MailingListSourceOptions | null;
  mailmanConfigured: boolean;
  tenants: App.Entities.Tenant[];
  can: { view?: boolean; update: boolean };
}>();

const canView = computed(() => props.can.view !== false);
const currentSection = ref(!canView.value ? 'archyvas' : (!props.sources.length && props.can.update ? 'saltiniai' : 'gavejai'));
const editOpen = ref(false);
const confirmManagedOpen = ref(false);
const confirmUnmanagedOpen = ref(false);
const confirmationDiff = ref<MailingListDiffPayload | null>(null);
const processing = ref(false);
const previewReady = computed(() => !!props.diff?.preview_token && !props.diff.error);
const reload = { preserveScroll: true, except: ['messages'], onFinish: finishProcessing };

watch(() => props.diff?.preview_token, () => {
  confirmManagedOpen.value = false;
});

const managed = computed(() => props.mailingList.mode === MailingListMode.Managed);

const facts = computed<RecordFact[]>(() => [
  { key: 'members', label: $t('mailing_lists.fields.members'), value: props.mailingList.member_count },
  { key: 'tenant', label: $tChoice('entities.tenant.model', 1), value: props.mailingList.tenant?.shortname ?? '—' },
  {
    key: 'last_post',
    label: $t('mailing_lists.fields.last_post'),
    value: props.mailingList.last_post_at ? formatDate(props.mailingList.last_post_at) : '—',
  },
  ...(managed.value
    ? [{
        key: 'synced',
        label: $t('mailing_lists.fields.last_synced'),
        value: props.mailingList.last_synced_at ? formatNearDate(props.mailingList.last_synced_at) : '—',
        status: props.mailingList.last_sync_status ? mailingListSyncStatuses[props.mailingList.last_sync_status] : undefined,
      }]
    : []),
  {
    key: 'archive',
    label: $t('mailing_lists.fields.archive_policy'),
    value: props.mailingList.archive_policy ? $t(`mailing_lists.archive_policies.${props.mailingList.archive_policy}`) : '—',
  },
]);

const sections = computed<RecordPageSection[]>(() => [
  ...(canView.value
    ? [
        { value: 'gavejai', label: $t('mailing_lists.sections.recipients'), count: props.mailingList.member_count },
        { value: 'saltiniai', label: $t('mailing_lists.sections.sources'), count: props.sources.length },
      ]
    : []),
  { value: 'archyvas', label: $t('mailing_lists.sections.archive') },
]);

const primaryAction = computed<RecordAction | undefined>(() => {
  if (!props.can.update || !props.mailmanConfigured) {
    return undefined;
  }

  if (processing.value) {
    return { key: 'pending', label: $t('Įkeliama…'), icon: RefreshCw };
  }
  if (!previewReady.value && (!managed.value || props.mailingList.last_sync_status === 'needs_review')) {
    return { key: 'preview', label: $t('mailing_lists.actions.load_preview'), icon: RefreshCw };
  }

  return managed.value
    ? { key: 'sync', label: $t('mailing_lists.actions.sync'), icon: RefreshCw }
    : { key: 'enable-managed', label: $t('mailing_lists.actions.enable_managed'), icon: RefreshCw };
});

const overflowActions = computed<RecordAction[]>(() => [
  { key: 'copy', label: $t('mailing_lists.actions.copy_address'), icon: Copy },
  ...(props.can.update ? [{ key: 'edit', label: $t('mailing_lists.actions.edit'), icon: Pencil }] : []),
  ...(props.can.update && managed.value ? [{ key: 'disable-managed', label: $t('mailing_lists.actions.disable_managed'), icon: Hand }] : []),
]);

function finishProcessing(): void {
  processing.value = false;
}

function showSources(): void {
  currentSection.value = 'saltiniai';
}

function handleAction(key: string): void {
  if (processing.value) return;
  if (key === 'preview') {
    currentSection.value = 'gavejai';
    processing.value = true;
    router.reload({ only: ['diff'], onFinish: finishProcessing });
  }
  if (key === 'sync') {
    if (props.diff?.needs_review || props.mailingList.last_sync_status === 'needs_review') {
      openReview();
    }
    else {
      processing.value = true;
      router.post(route('mailingLists.sync', props.mailingList.id), {}, reload);
    }
  }
  if (key === 'enable-managed') openReview();
  if (key === 'disable-managed') confirmUnmanagedOpen.value = true;
  if (key === 'edit') editOpen.value = true;
  if (key === 'copy') {
    if (!navigator.clipboard) {
      toast.error($t('mailing_lists.actions.copy_failed'));
      return;
    }
    void navigator.clipboard.writeText(props.mailingList.address)
      .then(() => toast.success($t('mailing_lists.actions.address_copied')))
      .catch(() => toast.error($t('mailing_lists.actions.copy_failed')));
  }
}

function openReview(): void {
  if (!previewReady.value) return;
  currentSection.value = 'gavejai';
  confirmationDiff.value = props.diff ?? null;
  confirmManagedOpen.value = true;
}

function applyReviewed(): void {
  const token = confirmationDiff.value?.preview_token;
  if (processing.value || !token || token !== props.diff?.preview_token) return;
  processing.value = true;
  if (managed.value) {
    router.post(route('mailingLists.sync', props.mailingList.id), { preview_token: token }, reload);
  }
  else {
    router.patch(route('mailingLists.mode', props.mailingList.id), { mode: 'managed', preview_token: token }, reload);
  }
}

function setMode(mode: 'unmanaged'): void {
  if (processing.value) return;
  processing.value = true;
  router.patch(route('mailingLists.mode', props.mailingList.id), { mode }, reload);
}
</script>

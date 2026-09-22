<template>
  <CollectionPage
    :source
    collection="documents"
    entity-type="document"
    :eyebrow
    :title="$t('Dokumentai')"
    :lead="$t('VU SA dokumentų archyvas ir sinchronizacija su SharePoint.')"
    default-view="table"
    :item-key="documentKey"
    :quick-filters
    :columns
    :search-placeholder="$t('Ieškoti dokumentų...')"
    @quick-filter="toggleQuickFilter"
  >
    <template #actions>
      <FilePicker
        v-if="sharepointPickerAvailable && canCreate"
        :loading="uploadLoading"
        @pick="handleDocumentPick"
      >
        <template #trigger>
          <Button variant="brand">
            <ExternalLink aria-hidden="true" />
            {{ $t('Įkelti iš SharePoint') }}
          </Button>
        </template>
      </FilePicker>

      <Button
        v-if="canUpdate"
        variant="outline"
        :disabled="bulkSyncLoading"
        @click="handleBulkSync"
      >
        <RefreshCw :class="['size-4', bulkSyncLoading && 'animate-spin']" aria-hidden="true" />
        {{ $t('Sinchronizuoti visus') }}
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex min-h-14 items-center justify-between gap-4 px-3 py-2.5 sm:px-4" data-slot="document-collection-row">
        <div class="flex min-w-0 flex-1 items-center gap-3">
          <div class="flex size-10 shrink-0 items-center justify-center border border-border bg-muted text-muted-foreground">
            <DocumentIcon class="size-5" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <a
                v-if="item.anonymous_url"
                :href="item.anonymous_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 truncate font-medium hover:text-brand"
              >
                <span class="truncate">{{ item.title || $t('Be pavadinimo') }}</span>
                <ExternalLink class="size-3 shrink-0 text-muted-foreground" aria-hidden="true" />
              </a>
              <span v-else class="truncate font-medium text-foreground">
                {{ item.title || $t('Be pavadinimo') }}
              </span>
              <span
                v-if="item.language"
                class="border border-border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
              >
                {{ item.language === 'Lietuvių' ? 'LT' : item.language === 'Anglų' ? 'EN' : item.language }}
              </span>
            </div>
            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
              <span v-if="item.content_type" class="border border-border bg-muted/60 px-1 py-0.5">
                {{ item.content_type }}
              </span>
              <span v-if="institutionName(item)">
                {{ institutionName(item) }}
              </span>
              <span v-if="item.document_date" class="tabular-nums">
                {{ formatDocDate(item.document_date) }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <StatusBadge
            v-if="item.sync_status && syncStatuses[item.sync_status]"
            :status="syncStatuses[item.sync_status]"
          />
          <Button
            v-if="canUpdate"
            variant="ghost"
            size="icon-sm"
            :title="$t('Atnaujinti iš SharePoint')"
            :disabled="refreshingId === item.id"
            @click="refreshDocument(item)"
          >
            <RefreshCw :class="['size-4', refreshingId === item.id && 'animate-spin']" />
          </Button>
          <Button
            v-if="canDelete"
            variant="ghost"
            size="icon-sm"
            class="text-destructive hover:text-destructive"
            :title="$t('Ištrinti')"
            @click="confirmDelete(item)"
          >
            <Trash2 class="size-4" />
          </Button>
        </div>
      </article>
    </template>

    <template #cell="{ item, column }">
      <span v-if="column.key === 'date'" class="tabular-nums text-xs">
        {{ formatDocDate(item.document_date) }}
      </span>

      <div v-else-if="column.key === 'title'" class="min-w-0">
        <a
          v-if="item.anonymous_url"
          :href="item.anonymous_url"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1.5 font-medium hover:text-brand"
        >
          <span class="line-clamp-2">{{ item.title || $t('Be pavadinimo') }}</span>
          <ExternalLink class="size-3 shrink-0 text-muted-foreground" aria-hidden="true" />
        </a>
        <span v-else class="line-clamp-2 font-medium">
          {{ item.title || $t('Be pavadinimo') }}
        </span>
      </div>

      <span v-else-if="column.key === 'content_type'" class="truncate text-xs">
        <span v-if="item.content_type" class="border border-border bg-muted/60 px-1.5 py-0.5">
          {{ item.content_type }}
        </span>
        <span v-else class="text-muted-foreground">—</span>
      </span>

      <span v-else-if="column.key === 'institution'" class="truncate text-xs">
        {{ institutionName(item) || '—' }}
      </span>

      <span v-else-if="column.key === 'language'">
        <span
          v-if="item.language"
          class="border border-border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
        >
          {{ item.language === 'Lietuvių' ? 'LT' : item.language === 'Anglų' ? 'EN' : item.language }}
        </span>
        <span v-else class="text-muted-foreground">—</span>
      </span>

      <div v-else-if="column.key === 'sync_status'">
        <StatusBadge
          v-if="item.sync_status && syncStatuses[item.sync_status]"
          :status="syncStatuses[item.sync_status]"
        />
        <span v-else class="text-muted-foreground">—</span>
      </div>

      <div v-else-if="column.key === 'actions'" class="flex items-center justify-end gap-1">
        <Button
          v-if="canUpdate"
          variant="ghost"
          size="icon-sm"
          :title="$t('Atnaujinti iš SharePoint')"
          :disabled="refreshingId === item.id"
          @click="refreshDocument(item)"
        >
          <RefreshCw :class="['size-4', refreshingId === item.id && 'animate-spin']" />
        </Button>
        <Button
          v-if="canDelete"
          variant="ghost"
          size="icon-sm"
          class="text-destructive hover:text-destructive"
          :title="$t('Ištrinti')"
          @click="confirmDelete(item)"
        >
          <Trash2 class="size-4" />
        </Button>
      </div>
    </template>

    <template #preview="{ item }">
      <DocumentDetailPreview :key="item.id" :document="item" />
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="DocumentIcon"
        :title="$t('Dokumentų dar nėra')"
        :description="$t('Dokumentai automatiškai sinchronizuojami iš SharePoint archyvo.')"
      />
    </template>
  </CollectionPage>

  <ConfirmDialog
    :open="documentToDelete !== null"
    :title="$t('Ištrinti dokumentą?')"
    :description="
      $t(
        'Ar tikrai norite pašalinti dokumentą „:title“? SharePoint failas nebus ištrintas, tačiau bus panaikinta vieša prieiga.',
        { title: documentToDelete?.title ?? '' },
      )
    "
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (documentToDelete = null)"
    @confirm="handleDelete"
  />
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  CircleCheck,
  CircleX,
  Clock3,
  ExternalLink,
  Inbox,
  LoaderCircle,
  RefreshCw,
  Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import { DocumentIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import type { StatusPresentation } from '@/Constants/statuses';
import DocumentDetailPreview from '@/Features/Admin/AdminSearch/Components/Detail/DocumentDetailPreview.vue';
import type { Item } from '@/Features/Admin/SharepointFilePicker/picker';
import FilePicker from '@/Features/Admin/SharepointFilePicker/FilePicker.vue';
import type { DocumentSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  importantContentTypes: string[];
}>();

const page = usePage();

const eyebrow = computed(() => `${$t('shell.workspaces.svetaine.title')} · ${$t('shell.sections.dokumentai')}`);

const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.document ?? true));
const canUpdate = computed(() => Boolean(page.props.auth?.can?.update?.document ?? true));
const canDelete = computed(() => Boolean(page.props.auth?.can?.delete?.document ?? true));

const sharepointPickerAvailable = computed(() =>
  typeof window !== 'undefined' && window.isSecureContext && String(page.props.app?.url ?? '').startsWith('https'),
);

const source = useTypesenseCollectionSource<DocumentSearchResult>({
  collection: 'documents',
  preserveUrlKeys: ['view', 'item'],
});

const documentKey = (item: DocumentSearchResult) => String(item.id);

const syncStatuses: Record<string, StatusPresentation> = {
  pending: { label: 'Laukiama', role: 'neutral', icon: Clock3 },
  imported: { label: 'Importuota', role: 'info', icon: Inbox },
  syncing: { label: 'Sinchronizuojama', role: 'progress', icon: LoaderCircle },
  success: { label: 'Sinchronizuota', role: 'success', icon: CircleCheck },
  failed: { label: 'Nepavyko', role: 'danger', icon: CircleX },
};

const columns = computed<CollectionColumn[]>(() => [
  { key: 'date', label: $t('Data'), class: 'w-28' },
  { key: 'title', label: $t('Pavadinimas') },
  { key: 'content_type', label: $t('Rūšis'), class: 'w-44' },
  { key: 'institution', label: $t('Institucija'), class: 'w-44' },
  { key: 'language', label: $t('Kalba'), class: 'w-20' },
  { key: 'sync_status', label: $t('Būsena'), class: 'w-36' },
  { key: 'actions', label: '', class: 'w-20' },
]);

function institutionName(item: DocumentSearchResult): string | undefined {
  return item.institution_name_lt || item.institution_name_en || item.tenant_shortname;
}

function formatDocDate(timestamp?: number | null): string {
  if (!timestamp) return '—';
  return formatDate(new Date(timestamp * 1000));
}

// Quick filters
const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const chosenCategories = asList(source.filters.value.content_type_category);
  return (props.importantContentTypes || []).map(type => ({
    id: `ct_${type}`,
    label: type,
    active: chosenCategories.includes(type),
  }));
});

function toggleQuickFilter(id: string): void {
  if (id.startsWith('ct_')) {
    const type = id.replace('ct_', '');
    source.toggleFilter('content_type_category', type);
  }
}

// Actions
const refreshingId = ref<string | null>(null);

function refreshDocument(item: DocumentSearchResult): void {
  refreshingId.value = item.id;
  router.post(route('documents.refresh', item.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      refreshingId.value = null;
    },
  });
}

const bulkSyncLoading = ref(false);

function handleBulkSync(): void {
  bulkSyncLoading.value = true;
  router.post(route('documents.bulk-sync'), {}, {
    preserveScroll: true,
    onFinish: () => {
      bulkSyncLoading.value = false;
    },
  });
}

const uploadLoading = ref(false);

function handleDocumentPick(items: Item[]): void {
  uploadLoading.value = true;
  const documents = items.map(item => ({
    name: item.name,
    site_id: item.sharepointIds?.siteId,
    list_id: item.sharepointIds?.listId,
    list_item_unique_id: item.sharepointIds?.listItemUniqueId,
  }));

  router.post(route('documents.store'), { documents }, {
    preserveScroll: true,
    onSuccess: () => {
      source.refresh();
    },
    onFinish: () => {
      uploadLoading.value = false;
    },
  });
}

const documentToDelete = ref<DocumentSearchResult | null>(null);

function confirmDelete(item: DocumentSearchResult): void {
  documentToDelete.value = item;
}

function handleDelete(): void {
  if (!documentToDelete.value) return;
  router.delete(route('documents.destroy', documentToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      documentToDelete.value = null;
      source.refresh();
    },
  });
}
</script>

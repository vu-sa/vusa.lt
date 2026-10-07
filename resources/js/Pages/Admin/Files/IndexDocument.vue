<template>
  <DocumentFolderBrowser
    v-if="discovery"
    ref="browser"
    :eyebrow
    :title="$t('Dokumentai')"
    :lead
    :pending-count="discovery.counts.pending"
    :removed-count="discovery.counts.removed"
    :refresh-key="discovery.lastRunAt"
  >
    <template #actions>
      <FilePicker v-if="pickerAvailable" @pick="handlePick">
        <template #trigger>
          <Button variant="outline" voice="sentence" :disabled="picking">
            <Spinner v-if="picking" aria-hidden="true" />
            <FileUp v-else aria-hidden="true" />
            {{ $t('Pasirinkti iš SharePoint') }}
          </Button>
        </template>
      </FilePicker>
      <Button variant="outline" voice="sentence" :disabled="discoverLoading" @click="handleDiscover">
        <RefreshCw :class="['size-4', discoverLoading && 'animate-spin']" aria-hidden="true" />
        {{ $t('Tikrinti SharePoint') }}
      </Button>
    </template>
  </DocumentFolderBrowser>

  <CollectionPage
    v-else
    :source="source!"
    collection="documents"
    entity-type="document"
    :eyebrow
    :title="$t('Dokumentai')"
    :lead
    default-view="table"
    :item-key="documentKey"
    :quick-filters
    :columns
    :search-placeholder="$t('Ieškoti dokumentų...')"
    @quick-filter="toggleQuickFilter"
  >
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
                <span class="truncate"><SearchMatch inline :match="matchTitle(item.title || $t('Be pavadinimo'), item._searchTitleMatches)" :title="item.title || $t('Be pavadinimo')" /></span>
                <ExternalLink class="size-3 shrink-0 text-muted-foreground" aria-hidden="true" />
              </a>
              <span v-else class="truncate font-medium text-foreground">
                <SearchMatch inline :match="matchTitle(item.title || $t('Be pavadinimo'), item._searchTitleMatches)" :title="item.title || $t('Be pavadinimo')" />
              </span>
              <span
                v-if="item.language"
                class="border border-border bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
              >
                {{ item.language === 'Lietuvių' ? 'LT' : item.language === 'Anglų' ? 'EN' : item.language }}
              </span>
            </div>
            <SearchMatch compact :match="item._searchMatch" :title="item.title || $t('Be pavadinimo')" />
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
          <CollectionRowActions :actions="rowActions(item)" @select="key => selectRowAction(key, item)" />
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
          <span class="line-clamp-2"><SearchMatch inline :match="matchTitle(item.title || $t('Be pavadinimo'), item._searchTitleMatches)" :title="item.title || $t('Be pavadinimo')" /></span>
          <ExternalLink class="size-3 shrink-0 text-muted-foreground" aria-hidden="true" />
        </a>
        <span v-else class="line-clamp-2 font-medium">
          <SearchMatch inline :match="matchTitle(item.title || $t('Be pavadinimo'), item._searchTitleMatches)" :title="item.title || $t('Be pavadinimo')" />
        </span>
        <SearchMatch compact :match="item._searchMatch" :title="item.title || $t('Be pavadinimo')" />
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

      <CollectionRowActions v-else-if="column.key === 'actions'" :actions="rowActions(item)" @select="key => selectRowAction(key, item)" />
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
    :open="documentToHide !== null"
    :title="$t('Slėpti dokumentą?')"
    :description="hideDescription"
    :confirm-label="$t('Slėpti')"
    @update:open="!$event && (documentToHide = null)"
    @confirm="handleHide"
  />
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, EyeOff, FileUp, RefreshCw } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';

import { matchTitle } from '@/Shared/Search/matches';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import type { CollectionColumn, CollectionQuickFilter } from '@/Components/Collection/types';
import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import { DocumentFolderBrowser } from '@/Components/Files';
import { DocumentIcon } from '@/Components/icons';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { useAdminNavigation } from '@/Composables/useAdminNavigation';
import { useTypesenseCollectionSource } from '@/Composables/useCollectionSource';
import DocumentDetailPreview from '@/Features/Admin/AdminSearch/Components/Detail/DocumentDetailPreview.vue';
import FilePicker from '@/Features/Admin/SharepointFilePicker/FilePicker.vue';
import { isPickerAvailable, pickedDocuments, type Item } from '@/Features/Admin/SharepointFilePicker/picker';
import type { DocumentSearchResult } from '@/Shared/Search/types';
import { formatDate, formatNearDate } from '@/Utils/dateTime';

const props = defineProps<{
  importantContentTypes: string[];
  abilities: { create: boolean; update: boolean; updateTenantShortnames: string[] | null };
  /** The user's padaliniai plus VU SA: a first visit starts filtered to them. */
  defaultTenantShortnames: string[];
  /** Managers get every SharePoint file from the database; null for members who browse the published archive. */
  discovery: { lastRunAt: string | null; counts: { pending: number; removed: number } } | null;
}>();

const { activeWorkspace } = useAdminNavigation();

// Listed in both ViSAK and Svetainė; the eyebrow names whichever the user came through.
const eyebrow = computed(() => `${$t(activeWorkspace.value?.label ?? 'shell.workspaces.svetaine.title')} · ${$t('shell.sections.dokumentai')}`);

const lead = computed(() => {
  if (!props.discovery) {
    return $t('VU SA ir padalinių dokumentų archyvas.');
  }

  const checked = props.discovery.lastRunAt
    ? $t('SharePoint tikrintas :time.', { time: formatNearDate(props.discovery.lastRunAt) })
    : $t('SharePoint dar netikrintas.');

  return `${$t('Visi SharePoint archyvo failai. Nuspręsk, kurie rodomi vusa.lt.')} ${checked}`;
});

const browser = ref<InstanceType<typeof DocumentFolderBrowser> | null>(null);

// Members who may update a padalinys' documents act on archive rows by its shortname, as in DocumentPolicy.
const canUpdateItem = (item: DocumentSearchResult): boolean => props.abilities.update && (
  props.abilities.updateTenantShortnames === null
  || (!!item.tenant_shortname && props.abilities.updateTenantShortnames.includes(item.tenant_shortname))
);

const rowActions = (item: DocumentSearchResult): CollectionRowAction[] => canUpdateItem(item)
  ? [
      { key: 'refresh', label: $t('Atnaujinti iš SharePoint'), icon: RefreshCw, loading: refreshingId.value === String(item.id) },
      // Hiding, not deleting: SharePoint is the source, and a deleted record would only be rediscovered.
      { key: 'hide', label: $t('Slėpti'), icon: EyeOff },
    ]
  : [];

function selectRowAction(key: string, item: DocumentSearchResult): void {
  if (key === 'refresh') refreshDocument(item);
  if (key === 'hide') documentToHide.value = item;
}

// Only the archive gets a search source: it syncs its state into the URL on mount and would
// otherwise erase the managers' view's `path`, `layout` and `status`.
const source = props.discovery
  ? null
  : useTypesenseCollectionSource<DocumentSearchResult>({
      collection: 'documents',
      preserveUrlKeys: ['view', 'item'],
      defaultFilters: { tenant_shortname: props.defaultTenantShortnames },
    });

const documentKey = (item: DocumentSearchResult) => String(item.id);

const columns = computed<CollectionColumn[]>(() => [
  { key: 'date', label: $t('Data'), class: 'w-28' },
  { key: 'title', label: $t('Pavadinimas') },
  { key: 'content_type', label: $t('Rūšis'), class: 'w-44' },
  { key: 'institution', label: $t('Institucija'), class: 'w-44' },
  { key: 'language', label: $t('Kalba'), class: 'w-20' },
  ...(props.abilities.update ? [{ key: 'actions', label: $t('Veiksmai'), class: 'w-px text-right', pinned: true }] : []),
]);

function institutionName(item: DocumentSearchResult): string | undefined {
  return item.institution_name_lt || item.institution_name_en || item.tenant_shortname;
}

function formatDocDate(timestamp?: number | null): string {
  if (!timestamp) return '—';
  return formatDate(new Date(timestamp * 1000));
}

const asList = (value: unknown): string[] => (Array.isArray(value) ? value.map(String) : []);

const quickFilters = computed<CollectionQuickFilter[]>(() => {
  const chosenCategories = asList(source?.filters.value.content_type_category);

  return (props.importantContentTypes || []).map(type => ({
    id: `ct_${type}`,
    label: type,
    active: chosenCategories.includes(type),
  }));
});

function toggleQuickFilter(id: string): void {
  if (id.startsWith('ct_')) {
    source?.toggleFilter('content_type_category', id.replace('ct_', ''));
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

const pickerAvailable = computed(() => isPickerAvailable(usePage().props.app?.url));
const picking = ref(false);

/** Coordinators' habit from before discovery: pick the file in SharePoint, and it is shown on vusa.lt. */
function handlePick(items: Item[]): void {
  picking.value = true;
  router.post(route('documents.pick'), { documents: pickedDocuments(items) }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => browser.value?.refresh(),
    onFinish: () => {
      picking.value = false;
    },
  });
}

const discoverLoading = ref(false);

// The check runs in the background; watch for it to finish so new files and counts appear without a reload.
const DISCOVERY_POLL_MS = 5000;
const DISCOVERY_POLL_LIMIT = 36;
let discoveryPoll: ReturnType<typeof setTimeout> | null = null;

function stopDiscoveryPolling(): void {
  if (discoveryPoll) clearTimeout(discoveryPoll);
  discoveryPoll = null;
  discoverLoading.value = false;
}

function waitForDiscovery(startedFrom: string | null, attempt = 1): void {
  if (attempt > DISCOVERY_POLL_LIMIT || (props.discovery?.lastRunAt ?? null) !== startedFrom) {
    stopDiscoveryPolling();
    return;
  }

  discoveryPoll = setTimeout(() => {
    router.reload({ only: ['discovery'], onFinish: () => waitForDiscovery(startedFrom, attempt + 1) });
  }, DISCOVERY_POLL_MS);
}

onBeforeUnmount(stopDiscoveryPolling);

function handleDiscover(): void {
  const startedFrom = props.discovery?.lastRunAt ?? null;

  discoverLoading.value = true;
  router.post(route('documents.discover'), {}, {
    preserveScroll: true,
    // The same page instance keeps watching; a fresh one would lose the poll.
    preserveState: true,
    onSuccess: () => waitForDiscovery(startedFrom),
    onError: () => stopDiscoveryPolling(),
  });
}

const documentToHide = ref<DocumentSearchResult | null>(null);

const hideDescription = computed(() => $t('„:title“ nebebus rodomas vusa.lt, o jo vieša SharePoint nuoroda bus atšaukta.', {
  title: documentToHide.value?.title ?? '',
}));

function handleHide(): void {
  if (!documentToHide.value) return;
  router.post(route('documents.status'), { document_ids: [Number(documentToHide.value.id)], status: 'hidden' }, {
    preserveScroll: true,
    onSuccess: () => {
      documentToHide.value = null;
      source?.refresh();
    },
  });
}
</script>

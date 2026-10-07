<template>
  <div class="flex flex-col gap-3 lg:gap-4" data-slot="document-folder-browser">
    <Head :title />

    <CollectionTitleBand :eyebrow :title :lead entity-type="document">
      <template v-if="$slots.actions" #actions>
        <slot name="actions" />
      </template>
    </CollectionTitleBand>

    <section class="flex flex-col gap-2 border-y border-border py-3 lg:gap-3 lg:py-4" :aria-label="$t('Paieška ir filtrai')">
      <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <SpotlightPopover
          :title="$t('Visi SharePoint failai vienoje vietoje')"
          :description="$t('Žiūrėk failus sąrašu arba SharePoint aplankais. Būseną keisk paspaudęs ją eilutėje.')"
          :is-dismissed="layoutSpotlight.isDismissed.value"
          @dismiss="layoutSpotlight.dismiss()"
        >
          <div :class="segmentGroupClass" role="group" :aria-label="$t('Rodinys')" data-slot="document-layout-switch">
            <button
              v-for="option in layoutOptions"
              :key="option.value"
              type="button"
              :class="segmentVariants({ active: effectiveLayout === option.value })"
              :aria-pressed="effectiveLayout === option.value"
              :disabled="option.value === 'folders' && status === 'removed'"
              @click="setLayout(option.value)"
            >
              <component :is="option.icon" aria-hidden="true" />
              {{ option.label }}
            </button>
          </div>
        </SpotlightPopover>

        <!-- Files gone from SharePoint are listed from the whole archive, so no folder applies. -->
        <nav v-if="status !== 'removed'" :aria-label="$t('Aplanko kelias')" class="flex min-w-0 basis-full flex-wrap items-center gap-1 text-sm sm:basis-auto sm:flex-1">
          <PathBreadcrumb
            :crumbs="listing?.breadcrumbs ?? []"
            :root-label="$t('SharePoint')"
            root-path=""
            @navigate="openFolder"
          />
        </nav>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-0 flex-1 basis-64">
          <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
          <input
            v-model="search"
            type="search"
            :placeholder="$t('Ieškoti šiame aplanke...')"
            :aria-label="$t('Ieškoti šiame aplanke...')"
            autocomplete="off"
            :class="[searchFieldClass, 'bg-secondary/40 text-base md:text-sm']"
          >
        </div>
        <Select v-if="contentTypes.length > 0" :model-value="contentType ?? ALL_TYPES" @update:model-value="setContentType">
          <SelectTrigger class="w-full sm:w-56" :aria-label="$t('Rūšis')">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="ALL_TYPES">
              {{ $t('Visos rūšys') }}
            </SelectItem>
            <SelectItem v-for="type in contentTypes" :key="type" :value="type">
              {{ type }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div data-slot="document-status-filter">
        <CollectionQuickFilters :filters="statusFilters" @toggle="toggleStatus" />
      </div>
    </section>

    <p v-if="error" class="border-l-2 border-status-danger bg-status-danger-surface px-4 py-3 text-sm text-status-danger" role="alert">
      {{ error }}
    </p>

    <CollectionSkeleton v-else-if="!listing" />

    <!-- The bar overlays the list, so loading a folder never pushes it down. -->
    <div v-else class="relative -mt-3 lg:-mt-4">
      <TopProgressBar :active="loading" class="absolute inset-x-0 top-0 z-10" />

      <template v-if="listing.folders.length > 0 || listing.files.length > 0">
        <!-- Fixed layout: a long title or path wraps or truncates instead of widening the table. -->
        <Table v-if="isAtLeastMd" class="table-fixed" data-slot="document-folder-table">
          <TableHeader>
            <TableRow>
              <TableHead v-if="selectableFiles.length > 0" class="w-10">
                <Checkbox :model-value="allSelected" :aria-label="$t('Pažymėti visus')" @update:model-value="toggleAll" />
              </TableHead>
              <TableHead>{{ $t('Pavadinimas') }}</TableHead>
              <TableHead class="w-28">
                {{ $t('Data') }}
              </TableHead>
              <TableHead class="w-36 xl:w-44">
                {{ $t('Rūšis') }}
              </TableHead>
              <TableHead class="w-36 xl:w-44">
                {{ $t('Institucija') }}
              </TableHead>
              <TableHead class="w-40">
                {{ $t('Būsena') }}
              </TableHead>
              <TableHead class="w-28">
                <span class="sr-only">{{ $t('Veiksmai') }}</span>
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="folder in listing.folders" :key="folder.path" class="cursor-pointer" data-slot="document-folder" @click="openFolder(folder.path)">
              <TableCell v-if="selectableFiles.length > 0" />
              <TableCell colspan="4">
                <!-- The row is clickable for pointers; the button makes it reachable by keyboard. -->
                <button type="button" class="inline-flex min-h-11 max-w-full items-center gap-2 font-medium hover:text-brand" @click.stop="openFolder(folder.path)">
                  <Folder class="size-4 shrink-0 text-brand" aria-hidden="true" />
                  <span class="truncate">{{ folder.name }}</span>
                </button>
              </TableCell>
              <TableCell colspan="2" class="text-right text-xs text-muted-foreground">
                {{ folderSummary(folder.counts) }}
              </TableCell>
            </TableRow>
            <TableRow v-for="file in listing.files" :key="file.id" data-slot="document-file">
              <TableCell v-if="selectableFiles.length > 0">
                <Checkbox
                  v-if="isSelectable(file)"
                  :model-value="selectedIds.has(file.id)"
                  :aria-label="$t('Pažymėti')"
                  @update:model-value="toggleSelected(file.id)"
                />
              </TableCell>
              <TableCell>
                <p class="line-clamp-2 font-medium break-words">
                  {{ file.title || file.name }}
                </p>
                <p
                  v-if="folderOf(file)"
                  class="flex min-w-0 items-center gap-1 text-xs text-muted-foreground"
                  :title="file.sharepoint_path ?? undefined"
                  data-slot="document-file-folder"
                >
                  <Folder class="size-3 shrink-0" aria-hidden="true" />
                  <span class="truncate">{{ folderOf(file) }}</span>
                </p>
                <p v-if="linkState(file) === 'failed'" class="flex flex-wrap items-center gap-x-2 text-xs text-status-danger" data-slot="document-link-failed">
                  {{ $t('Nepavyko sukurti viešos nuorodos') }}
                  <button
                    v-if="file.can.update"
                    type="button"
                    class="font-semibold underline underline-offset-2 pointer-coarse:min-h-11 disabled:opacity-50"
                    :disabled="busyIds.has(file.id)"
                    :title="file.sync_error_message ?? undefined"
                    @click="retryLink(file)"
                  >
                    {{ $t('Bandyti dar kartą') }}
                  </button>
                </p>
                <p v-else-if="linkState(file) === 'pending'" class="text-xs text-muted-foreground" data-slot="document-link-pending">
                  {{ $t('Vieša nuoroda dar nesukurta') }}
                </p>
                <p v-if="file.problems.length > 0" class="text-xs text-status-attention" data-slot="document-problems">
                  {{ problemSummary(file) }}
                </p>
              </TableCell>
              <TableCell class="text-xs tabular-nums">
                {{ file.document_date ? formatDate(file.document_date) : '—' }}
              </TableCell>
              <TableCell class="text-xs break-words">
                {{ file.content_type ?? '—' }}
              </TableCell>
              <TableCell class="text-xs break-words">
                {{ file.institution?.name ?? '—' }}
              </TableCell>
              <TableCell>
                <DocumentFileStatus :file :busy="busyIds.has(file.id)" @status="requestStatus" />
              </TableCell>
              <TableCell>
                <DocumentFileActions :file @delete="requestDelete([$event])" />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- The table needs width; on a phone the rows are the view. -->
        <ul v-else class="divide-y divide-border border-b border-border" data-slot="document-folder-list">
          <li v-for="folder in listing.folders" :key="folder.path">
            <button
              type="button"
              class="flex min-h-14 w-full items-center gap-3 px-3 py-2.5 text-left transition-colors hover:bg-secondary/60"
              data-slot="document-folder"
              @click="openFolder(folder.path)"
            >
              <Folder class="size-5 shrink-0 text-brand" aria-hidden="true" />
              <span class="min-w-0 flex-1 truncate font-medium">{{ folder.name }}</span>
              <ChevronRight class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            </button>
          </li>

          <li
            v-for="file in listing.files"
            :key="file.id"
            class="flex min-h-14 flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2.5"
            data-slot="document-file"
          >
            <Checkbox
              v-if="isSelectable(file)"
              class="pointer-coarse:size-6"
              :model-value="selectedIds.has(file.id)"
              :aria-label="$t('Pažymėti')"
              @update:model-value="toggleSelected(file.id)"
            />
            <component :is="getFileIcon(file.name)" v-else class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium">
                {{ file.title || file.name }}
              </p>
              <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                <span v-if="folderOf(file)" class="inline-flex min-w-0 items-center gap-1" :title="file.sharepoint_path ?? undefined">
                  <Folder class="size-3 shrink-0" aria-hidden="true" />
                  <span class="truncate">{{ folderOf(file) }}</span>
                </span>
                <span v-if="file.document_date" class="tabular-nums">{{ formatDate(file.document_date) }}</span>
                <span v-if="file.content_type">{{ file.content_type }}</span>
                <span v-if="file.institution">{{ file.institution.name }}</span>
                <span v-if="linkState(file) === 'failed'" class="inline-flex flex-wrap items-center gap-x-2 text-status-danger" data-slot="document-link-failed">
                  {{ $t('Nepavyko sukurti viešos nuorodos') }}
                  <button
                    v-if="file.can.update"
                    type="button"
                    class="font-semibold underline underline-offset-2 pointer-coarse:min-h-11 disabled:opacity-50"
                    :disabled="busyIds.has(file.id)"
                    @click="retryLink(file)"
                  >
                    {{ $t('Bandyti dar kartą') }}
                  </button>
                </span>
                <span v-else-if="linkState(file) === 'pending'" data-slot="document-link-pending">
                  {{ $t('Vieša nuoroda dar nesukurta') }}
                </span>
                <span v-if="file.problems.length > 0" class="inline-flex items-center gap-1 text-status-attention" data-slot="document-problems">
                  <TriangleAlert class="size-3 shrink-0" aria-hidden="true" />
                  {{ problemSummary(file) }}
                </span>
              </div>
            </div>
            <div class="ml-8 flex items-center gap-2">
              <DocumentFileStatus :file :busy="busyIds.has(file.id)" @status="requestStatus" />
              <DocumentFileActions :file @delete="requestDelete([$event])" />
            </div>
          </li>
        </ul>
      </template>

      <EmptyState
        v-else
        mode="empty"
        :icon="DocumentIcon"
        :title="emptyTitle"
      />

      <div v-if="listing.next_offset !== null" class="mt-3 flex justify-center">
        <Button variant="outline" voice="sentence" :disabled="loading" data-slot="document-folder-more" @click="loadMore">
          {{ $t('Rodyti daugiau') }}
        </Button>
      </div>
    </div>

    <CollectionSelectionBar
      v-if="selectedFiles.length > 0"
      :count="selectedFiles.length"
      :count-label="$t('Pažymėta')"
      @clear="selectedIds = new Set()"
    >
      <Button v-if="status === 'removed'" variant="destructive" size="sm" voice="sentence" data-slot="document-bulk-delete" @click="requestDelete(selectedFiles)">
        <Trash2 aria-hidden="true" />
        {{ $t('Ištrinti') }}
      </Button>
      <template v-else>
        <Button
          v-if="bulkChoices.publish"
          variant="outline"
          size="sm"
          voice="sentence"
          data-slot="document-bulk-publish"
          @click="requestStatus(selectedFiles, DocumentStatus.Published)"
        >
          <Eye aria-hidden="true" />
          {{ $t('Paskelbti') }}
        </Button>
        <Button
          v-if="bulkChoices.hide"
          variant="outline"
          size="sm"
          voice="sentence"
          data-slot="document-bulk-hide"
          @click="requestStatus(selectedFiles, DocumentStatus.Hidden)"
        >
          <EyeOff aria-hidden="true" />
          {{ $t('Paslėpti') }}
        </Button>
      </template>
    </CollectionSelectionBar>
  </div>

  <ConfirmDialog
    :open="publishCandidates.length > 0"
    :title="$t('Paskelbti be visų duomenų?')"
    :description="publishWarning"
    :confirm-label="$t('Vis tiek paskelbti')"
    @update:open="!$event && (publishCandidates = [])"
    @confirm="updateStatus(publishCandidates, DocumentStatus.Published)"
  />

  <ConfirmDialog
    :open="deleteCandidates.length > 0"
    :title="$t('Ištrinti dokumentus (:count)?', { count: String(deleteCandidates.length) })"
    :description="$t('Jų failų SharePoint nebėra. Įrašai ir jų sąsajos su posėdžiais bus ištrinti visam laikui.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @update:open="!$event && (deleteCandidates = [])"
    @confirm="deleteFiles(deleteCandidates)"
  />
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { refDebounced, useMediaQuery, useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Eye, EyeOff, Folder, FolderTree, List, Search, Trash2, TriangleAlert } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import DocumentFileActions from './DocumentFileActions.vue';
import DocumentFileStatus from './DocumentFileStatus.vue';
import { linkState } from './linkState';
import type { DocumentFolderListing, DocumentFolderRow, DocumentProblem, DocumentStatusFilter } from './types';

import CollectionQuickFilters from '@/Components/Collection/CollectionQuickFilters.vue';
import CollectionSelectionBar from '@/Components/Collection/CollectionSelectionBar.vue';
import CollectionTitleBand from '@/Components/Collection/CollectionTitleBand.vue';
import type { CollectionQuickFilter } from '@/Components/Collection/types';
import { DocumentIcon } from '@/Components/icons';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { CollectionSkeleton, ConfirmDialog, EmptyState, TopProgressBar } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { searchFieldClass, segmentGroupClass, segmentVariants } from '@/Components/ui/control';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { useApiMutation } from '@/Composables/useApi';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import PathBreadcrumb from '@/Features/Admin/FileManager/Components/PathBreadcrumb.vue';
import { DocumentStatus } from '@/Types/enums';
import { formatDate } from '@/Utils/dateTime';
import { getFileIcon } from '@/Utils/fileIcons';

const props = defineProps<{
  eyebrow: string;
  title: string;
  lead?: string;
  /** Files waiting for a decision, and files gone from SharePoint, across everything the user manages. */
  pendingCount: number;
  removedCount: number;
  /** Changes when a SharePoint check finishes, so newly found files show up. */
  refreshKey?: string | null;
}>();

defineSlots<{
  actions?: () => unknown;
}>();

defineExpose({ refresh: () => load({ refresh: true }) });

type Layout = 'list' | 'folders';
const STATUSES: DocumentStatusFilter[] = ['all', 'pending', 'published', 'hidden', 'removed'];
const ALL_TYPES = '__all';
/** Files per answer; a refresh asks again for everything shown, up to the API's cap. */
const PAGE_SIZE = 100;
const REFRESH_LIMIT = 500;

const initialParams = new URLSearchParams(window.location.search);
// Absent until the first answer: the server opens the folder holding the user's files.
const path = ref<string | null>(initialParams.get('path'));
const status = ref<DocumentStatusFilter>(STATUSES.find(value => value === initialParams.get('status')) ?? 'all');
const contentType = ref<string | null>(initialParams.get('type'));
const contentTypes = ref<string[]>([]);

const storedLayout = useStorage<Layout>('documents-layout', 'list');
const urlLayout = initialParams.get('layout');
if (urlLayout === 'list' || urlLayout === 'folders') storedLayout.value = urlLayout;
// Files gone from SharePoint have no folder worth browsing.
const effectiveLayout = computed<Layout>(() => (status.value === 'removed' ? 'list' : storedLayout.value));

const layoutSpotlight = useFeatureSpotlight('documents-layout-v1');
const layoutOptions = computed(() => [
  { value: 'list' as const, label: $t('Sąrašas'), icon: List },
  { value: 'folders' as const, label: $t('Aplankai'), icon: FolderTree },
]);

const isAtLeastMd = useMediaQuery('(min-width: 768px)');
const search = ref('');
const debouncedSearch = refDebounced(search, 300);
/**
 * Where a file lives, relative to the open folder (whose path the breadcrumb already shows): nothing
 * for its own files, the subfolder for one level down, "first / … / last" deeper. The full path is on hover.
 */
function folderOf(file: DocumentFolderRow): string | null {
  const base = listing.value?.path ?? '';
  const full = file.sharepoint_path ?? '';
  const relative = base === '' ? full : full.startsWith(`${base}/`) ? full.slice(base.length + 1) : full === base ? '' : full;
  const segments = relative.split('/').filter(Boolean);

  if (segments.length === 0) return null;

  return segments.length <= 2 ? segments.join(' / ') : `${segments[0]} / … / ${segments.at(-1)}`;
}

const listing = ref<DocumentFolderListing | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
let latestRequest = 0;

/**
 * `offset` appends the next files. `refresh` re-reads everything shown, without the loading bar,
 * so files found or changed meanwhile appear and the scroll position stays.
 */
async function load(options: { offset?: number; refresh?: boolean } = {}): Promise<void> {
  // A refresh never cancels, nor outlives, a load the user asked for.
  if (options.refresh && loading.value) return;
  const current = options.refresh ? latestRequest : ++latestRequest;
  const url = new URL(route('api.v1.admin.documents.folder'), window.location.origin);

  if (path.value !== null) url.searchParams.set('path', path.value);
  if (effectiveLayout.value === 'list') url.searchParams.set('flat', '1');
  if (status.value !== 'all') url.searchParams.set('show', status.value);
  if (contentType.value) url.searchParams.set('content_type', contentType.value);
  if (debouncedSearch.value.trim()) url.searchParams.set('search', debouncedSearch.value.trim());
  if (options.offset) url.searchParams.set('offset', String(options.offset));
  if (options.refresh && listing.value) {
    url.searchParams.set('limit', String(Math.min(REFRESH_LIMIT, Math.max(PAGE_SIZE, listing.value.files.length))));
  }

  if (!options.refresh) {
    loading.value = true;
    error.value = null;
  }

  try {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json() as { success: boolean; data?: DocumentFolderListing; message?: string };

    if (!response.ok || !payload.success || !payload.data) {
      throw new Error(payload.message ?? $t('Nepavyko įkelti aplanko.'));
    }

    if (current !== latestRequest) return;

    if (options.offset && listing.value) {
      listing.value = { ...listing.value, files: [...listing.value.files, ...payload.data.files], next_offset: payload.data.next_offset };
      return;
    }

    listing.value = payload.data;
    path.value = payload.data.path;
    // Search answers carry no type list; keep the folder's.
    if (!debouncedSearch.value.trim()) contentTypes.value = payload.data.content_types;
    // Files no longer listed cannot stay selected.
    const listed = new Set(payload.data.files.map(file => file.id));
    selectedIds.value = new Set([...selectedIds.value].filter(id => listed.has(id)));
    if (!options.refresh) syncUrl();
  }
  catch (cause) {
    // A failed background refresh keeps what is shown; the next one may succeed.
    if (current === latestRequest && !options.refresh) error.value = cause instanceof Error ? cause.message : $t('Nepavyko įkelti aplanko.');
  }
  finally {
    if (current === latestRequest && !options.refresh) loading.value = false;
  }
}

function syncUrl(): void {
  const url = new URL(window.location.href);
  url.searchParams.set('path', path.value ?? '');
  url.searchParams.set('layout', effectiveLayout.value);
  if (status.value !== 'all') url.searchParams.set('status', status.value);
  else url.searchParams.delete('status');
  if (contentType.value) url.searchParams.set('type', contentType.value);
  else url.searchParams.delete('type');
  window.history.replaceState(window.history.state, '', url.toString());
}

/** A new view of the files: nothing selected carries over. */
function reset(): void {
  selectedIds.value = new Set();
  void load();
}

function openFolder(next: string): void {
  search.value = '';
  path.value = next;
  reset();
}

function setLayout(next: Layout): void {
  storedLayout.value = next;
  void layoutSpotlight.dismiss();
  reset();
}

/** Choosing the active status again goes back to every file. */
function toggleStatus(id: string): void {
  status.value = status.value === id ? 'all' : STATUSES.find(value => value === id) ?? 'all';
  reset();
}

function setContentType(next: unknown): void {
  contentType.value = typeof next === 'string' && next !== ALL_TYPES ? next : null;
  reset();
}

const statusFilters = computed<CollectionQuickFilter[]>(() => [
  { id: 'pending', label: countLabel($t('Laukia'), props.pendingCount), active: status.value === 'pending' },
  { id: 'published', label: $t('Paskelbti'), active: status.value === 'published' },
  { id: 'hidden', label: $t('Paslėpti'), active: status.value === 'hidden' },
  // Offered only while there is something to clear up.
  ...(props.removedCount > 0 || status.value === 'removed'
    ? [{ id: 'removed', label: countLabel($t('Pašalinti iš SharePoint'), props.removedCount), active: status.value === 'removed' }]
    : []),
]);

function countLabel(label: string, count: number): string {
  return count > 0 ? `${label} · ${count}` : label;
}

const emptyTitle = computed(() => {
  if (search.value) return $t('Nieko nerasta');

  return {
    all: effectiveLayout.value === 'folders' ? $t('Aplankas tuščias') : $t('Failų čia nėra'),
    pending: $t('Laukiančių failų čia nėra'),
    published: $t('Paskelbtų failų čia nėra'),
    hidden: $t('Paslėptų failų čia nėra'),
    removed: $t('Iš SharePoint pašalintų failų nėra'),
  }[status.value];
});

function loadMore(): void {
  if (listing.value?.next_offset) void load({ offset: listing.value.next_offset });
}

watch(debouncedSearch, () => reset());
watch(() => props.refreshKey, () => void load({ refresh: true }));
onMounted(() => void load());

function folderSummary(counts: Record<'published' | 'pending' | 'hidden', number>): string {
  return [
    counts.pending > 0 ? $t(':count laukia', { count: counts.pending }) : null,
    counts.published > 0 ? $t(':count paskelbta', { count: counts.published }) : null,
    counts.hidden > 0 ? $t(':count paslėpta', { count: counts.hidden }) : null,
  ].filter(Boolean).join(' · ');
}

const problemLabels: Record<DocumentProblem, string> = {
  institution: 'padalinys',
  unknown_institution: 'padalinys',
  content_type: 'turinio rūšis',
  document_date: 'data',
  language: 'kalba',
};

function problemSummary(file: DocumentFolderRow): string {
  return `${$t('Trūksta')}: ${[...new Set(file.problems.map(problem => $t(problemLabels[problem])))].join(', ')}`;
}

// Selection
const selectedIds = ref(new Set<number>());
const isSelectable = (file: DocumentFolderRow): boolean => file.can.update && (status.value === 'removed') === Boolean(file.removed_from_sharepoint_at);
const selectableFiles = computed(() => listing.value?.files.filter(isSelectable) ?? []);
const selectedFiles = computed(() => selectableFiles.value.filter(file => selectedIds.value.has(file.id)));
const allSelected = computed(() => selectableFiles.value.length > 0 && selectedFiles.value.length === selectableFiles.value.length);
/** Offer only a change that does something: a waiting file can go either way. */
const bulkChoices = computed(() => ({
  publish: selectedFiles.value.some(file => file.status !== DocumentStatus.Published),
  hide: selectedFiles.value.some(file => file.status !== DocumentStatus.Hidden),
}));

function toggleSelected(id: number): void {
  const next = new Set(selectedIds.value);
  if (!next.delete(id)) next.add(id);
  selectedIds.value = next;
}

function toggleAll(): void {
  selectedIds.value = allSelected.value ? new Set() : new Set(selectableFiles.value.map(file => file.id));
}

// Mutations
/** Files with a request in flight; their controls wait, so a double click sends nothing twice. */
const busyIds = ref(new Set<number>());
let mutations: Promise<unknown> = Promise.resolve();

/**
 * One request at a time, each read before the next starts: concurrent requests on a shared
 * fetch would abort one another.
 */
function mutate<T>(ids: number[], url: string, method: 'POST' | 'DELETE', body?: unknown): Promise<T | null> {
  if (ids.some(id => busyIds.value.has(id))) return Promise.resolve(null);
  busyIds.value = new Set([...busyIds.value, ...ids]);

  const run = async (): Promise<T | null> => {
    try {
      const request = useApiMutation<T>(url, method, body);
      await request.execute();

      return request.data.value ?? null;
    }
    finally {
      busyIds.value = new Set([...busyIds.value].filter(id => !ids.includes(id)));
    }
  };
  const result = mutations.then(run, run);
  mutations = result.catch(() => null);

  return result;
}

function replaceRows(rows: DocumentFolderRow[]): void {
  if (!listing.value) return;

  const byId = new Map(rows.map(row => [row.id, row]));
  listing.value = { ...listing.value, files: listing.value.files.map(file => byId.get(file.id) ?? file) };
}

/** Folder counts, filters and the waiting count follow; the changed rows are already shown. */
async function afterChange(): Promise<void> {
  await load({ refresh: true });
  router.reload({ only: ['discovery'] });
  pollLinks();
}

const publishCandidates = ref<DocumentFolderRow[]>([]);

const publishWarning = computed(() => {
  const incomplete = publishCandidates.value.filter(file => file.problems.length > 0);

  return $t('Trūksta SharePoint duomenų: :titles. Be jų dokumentą sunkiau rasti, o be padalinio jį tvarkyti galės tik visų padalinių dokumentų valdytojai.', {
    titles: incomplete.slice(0, 3).map(file => `„${file.title || file.name}“ (${problemSummary(file).toLowerCase()})`).join(', ') + (incomplete.length > 3 ? '…' : ''),
  });
});

/** Missing metadata never blocks publishing, but it is said out loud first. */
function requestStatus(files: DocumentFolderRow | DocumentFolderRow[], next: DocumentStatus): void {
  const list = Array.isArray(files) ? files : [files];

  if (next === DocumentStatus.Published && list.some(file => file.problems.length > 0)) {
    publishCandidates.value = list;
    return;
  }

  void updateStatus(list, next);
}

/** A plain request, not a page visit, so the folder and scroll position stay put. */
async function updateStatus(files: DocumentFolderRow[], next: DocumentStatus): Promise<void> {
  publishCandidates.value = [];
  const ids = files.map(file => file.id);
  const rows = await mutate<DocumentFolderRow[]>(ids, route('api.v1.admin.documents.status'), 'POST', { document_ids: ids, status: next });

  if (!rows) return;
  replaceRows(rows);
  selectedIds.value = new Set();
  await afterChange();
}

const deleteCandidates = ref<DocumentFolderRow[]>([]);

function requestDelete(files: DocumentFolderRow[]): void {
  deleteCandidates.value = files;
}

async function deleteFiles(files: DocumentFolderRow[]): Promise<void> {
  deleteCandidates.value = [];
  const ids = files.map(file => file.id);
  const result = await mutate<{ ids: number[] }>(ids, route('api.v1.admin.documents.destroyRemoved'), 'DELETE', { document_ids: ids });

  if (!result) return;
  selectedIds.value = new Set();
  await afterChange();
}

// The link is created by a queued job, usually within seconds. Poll briefly rather than forever.
const LINK_POLL_MS = 3000;
const LINK_POLL_LIMIT = 20;
let linkPoll: ReturnType<typeof setTimeout> | null = null;

function stopLinkPolling(): void {
  if (linkPoll) clearTimeout(linkPoll);
  linkPoll = null;
}

function pollLinks(attempt = 1): void {
  stopLinkPolling();
  if (attempt > LINK_POLL_LIMIT || !listing.value?.files.some(file => linkState(file) === 'pending')) return;

  linkPoll = setTimeout(async () => {
    await load({ refresh: true });
    pollLinks(attempt + 1);
  }, LINK_POLL_MS);
}

onBeforeUnmount(stopLinkPolling);

async function retryLink(file: DocumentFolderRow): Promise<void> {
  const row = await mutate<DocumentFolderRow>([file.id], route('api.v1.admin.documents.refresh', file.id), 'POST');

  if (row) replaceRows([row]);
  pollLinks();
}
</script>

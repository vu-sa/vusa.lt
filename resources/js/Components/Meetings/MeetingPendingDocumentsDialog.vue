<template>
  <SearchSelectDialog
    v-model:open="open"
    multiple
    :title="$t('Laukia SharePoint')"
    :description="$t('Šio padalinio failai, dar nepaskelbti vusa.lt. Susietas failas bus paskelbtas.')"
    :confirm-label="$t('Paskelbti ir susieti')"
    @confirm="hits => $emit('confirm', hits.map(hit => Number(hit.recordId)))"
  >
    <template #trigger>
      <Button type="button" variant="outline" size="sm" voice="sentence" data-slot="meeting-pending-trigger">
        <FileClock class="mr-1.5 size-3.5" />
        {{ $t('Laukia SharePoint') }}
        <span class="ml-1.5 tabular-nums text-muted-foreground">{{ suggestions.length }}{{ suggestions.length >= SUGGESTION_COUNT ? '+' : '' }}</span>
      </Button>
    </template>

    <template #default="{ selectedIds, toggle }">
      <div class="flex min-h-0 flex-1 flex-col gap-3" data-slot="meeting-pending-documents">
        <div class="flex h-11 shrink-0 items-center gap-2 border border-border bg-background px-3 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20">
          <LoaderCircle v-if="isFetching" class="size-4 shrink-0 animate-spin text-brand" aria-hidden="true" />
          <Search v-else class="size-4 shrink-0 text-muted-foreground/60" />
          <input
            v-model="search"
            type="search"
            :placeholder="$t('Ieškoti failo pagal pavadinimą...')"
            :aria-label="$t('Ieškoti failo pagal pavadinimą...')"
            autocomplete="off"
            class="h-full min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground/70 md:text-sm"
            data-slot="meeting-pending-search"
          >
        </div>
        <div class="min-h-0 flex-1 border border-border bg-background">
          <!-- No detail pane here, so a row click toggles instead of highlighting. -->
          <SearchResultList
            :hits
            :is-loading="isFetching"
            has-searched
            :empty-message="$t('Nieko nerasta')"
            selectable
            multiple
            :selected-ids
            @select="toggle"
            @toggle="toggle"
          />
        </div>
      </div>
    </template>
  </SearchSelectDialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { refDebounced } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { FileClock, FileText, LoaderCircle, Search } from '@lucide/vue';

import type { DocumentFolderRow } from '@/Components/Files';
import { Button } from '@/Components/ui/button';
import { useApi } from '@/Composables/useApi';
import SearchResultList from '@/Features/Admin/AdminSearch/Components/SearchResultList.vue';
import SearchSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/SearchSelectDialog.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

const props = defineProps<{
  meetingId: string;
  /** The page's first suggestions (MeetingController::pendingDocumentsFor); the rest are a search away. */
  suggestions: DocumentFolderRow[];
}>();

defineEmits<{
  confirm: [documentIds: number[]];
}>();

const SUGGESTION_COUNT = 8;

const open = ref(false);
const search = ref('');
const debouncedSearch = refDebounced(search, 300);
const searching = computed(() => debouncedSearch.value.trim() !== '');

watch(open, (isOpen) => {
  if (!isOpen) {
    search.value = '';
  }
});

const searchUrl = computed(() => route('api.v1.admin.meetings.pendingDocuments', {
  meeting: props.meetingId,
  search: debouncedSearch.value.trim(),
}));
const { data: found, isFetching } = useApi<DocumentFolderRow[]>(searchUrl, { immediate: false, refetch: true });

const toHit = (row: DocumentFolderRow): NormalizedSearchHit => ({
  id: `documents-${row.id}`,
  recordId: String(row.id),
  collection: 'documents',
  icon: FileText,
  title: row.title || row.name,
  subtitle: [row.sharepoint_path, row.document_date].filter(Boolean).join(' · '),
  raw: row,
});

const hits = computed(() => (searching.value ? found.value ?? [] : props.suggestions).map(toHit));
</script>

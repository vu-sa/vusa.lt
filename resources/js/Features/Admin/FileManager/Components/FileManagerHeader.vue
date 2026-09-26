<template>
  <div class="space-y-0">
    <!-- Toolbar (Search, Type filter, Sort, View mode) -->
    <div
      class="flex flex-col gap-3 border-b border-border bg-background p-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <!-- Search input -->
      <div class="flex flex-col gap-1 w-full sm:max-w-xs">
        <label
          class="flex min-h-10 w-full items-center gap-2 border border-border bg-secondary/40 px-3 text-muted-foreground transition-colors focus-within:border-brand"
        >
          <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <input
            type="search"
            :value="search"
            :placeholder="$t('files.ui.search_placeholder')"
            class="w-full bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
            @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
          >
          <Spinner v-if="searching" class="size-4 shrink-0" />
          <button
            v-else-if="search"
            type="button"
            class="text-muted-foreground hover:text-foreground p-0.5"
            @click="$emit('update:search', '')"
          >
            <X class="size-3.5" aria-hidden="true" />
          </button>
        </label>

        <!-- Search Everywhere Checkbox -->
        <label class="flex cursor-pointer items-center gap-2 px-1 text-xs text-muted-foreground select-none">
          <Checkbox
            :model-value="searchEverywhere"
            @update:model-value="$emit('update:searchEverywhere', $event === true)"
          />
          <span>{{ $t('files.ui.search_everywhere') }}</span>
        </label>
      </div>

      <!-- Action & View Controls -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Upload Mode Toggle (if not selection mode or allowed) -->
        <div v-if="!selectionMode || allowUploadInSelection" class="flex">
          <button
            type="button"
            :class="[
              'inline-flex min-h-10 items-center gap-2 border px-3 text-xs font-semibold uppercase tracking-wider transition-colors',
              isUploadMode
                ? 'border-brand bg-brand text-brand-foreground'
                : 'border-border bg-secondary/40 text-foreground hover:bg-secondary',
            ]"
            @click="$emit('update:isUploadMode', !isUploadMode)"
          >
            <Upload class="size-3.5" aria-hidden="true" />
            <span>{{ isUploadMode ? $t('files.ui.browse') : $t('files.ui.upload') }}</span>
          </button>
        </div>

        <!-- Type Filter Dropdown -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button
              type="button"
              :class="[
                'inline-flex min-h-10 items-center gap-2 border px-3 text-xs font-semibold transition-colors',
                typeFilter !== 'all'
                  ? 'border-brand bg-brand/10 text-brand'
                  : 'border-border bg-secondary/40 text-foreground hover:bg-secondary',
              ]"
            >
              <Filter class="size-3.5" aria-hidden="true" />
              <span>{{ activeFilterLabel }}</span>
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-48 rounded-none">
            <DropdownMenuItem
              v-for="filter in typeFilters"
              :key="filter.id"
              class="flex items-center justify-between text-xs cursor-pointer"
              @click="$emit('update:typeFilter', filter.id)"
            >
              <span>{{ filter.label }}</span>
              <Check v-if="typeFilter === filter.id" class="size-3.5 text-brand" aria-hidden="true" />
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Sort Control -->
        <div class="flex border border-border">
          <button
            type="button"
            class="inline-flex min-h-10 items-center gap-2 bg-secondary/40 px-3 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="cycleSortKey"
          >
            <ArrowUpDown class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ activeSortLabel }}</span>
          </button>
          <button
            type="button"
            :title="sortDir === 'asc' ? 'Didėjimo tvarka' : 'Mažėjimo tvarka'"
            :aria-label="sortDir === 'asc' ? 'Didėjimo tvarka' : 'Mažėjimo tvarka'"
            class="flex size-10 items-center justify-center border-l border-border bg-secondary/40 text-foreground transition-colors hover:bg-secondary"
            @click="$emit('update:sortDir', sortDir === 'asc' ? 'desc' : 'asc')"
          >
            <ArrowUp v-if="sortDir === 'asc'" class="size-3.5" aria-hidden="true" />
            <ArrowDown v-else class="size-3.5" aria-hidden="true" />
          </button>
        </div>

        <!-- View mode toggle -->
        <div class="flex border border-border">
          <button
            type="button"
            :aria-label="$t('Tinklelis')"
            :aria-pressed="viewMode === 'grid'"
            :class="[
              'flex size-10 items-center justify-center transition-colors',
              viewMode === 'grid'
                ? 'bg-brand-fill text-brand-foreground font-bold'
                : 'text-foreground bg-secondary/40 hover:bg-secondary',
            ]"
            @click="$emit('update:viewMode', 'grid')"
          >
            <LayoutGrid class="size-4" aria-hidden="true" />
          </button>
          <button
            type="button"
            :aria-label="$t('Sąrašas')"
            :aria-pressed="viewMode === 'list'"
            :class="[
              'flex size-10 items-center justify-center border-l border-border transition-colors',
              viewMode === 'list'
                ? 'bg-brand-fill text-brand-foreground font-bold'
                : 'text-foreground bg-secondary/40 hover:bg-secondary',
            ]"
            @click="$emit('update:viewMode', 'list')"
          >
            <List class="size-4" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>

    <!-- Breadcrumb + Selection Sub-bar -->
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/30 px-4 py-2.5"
    >
      <!-- Left: Select all checkbox + Breadcrumb -->
      <div class="flex items-center gap-2 min-w-0">
        <button
          v-if="!selectionMode"
          type="button"
          class="text-muted-foreground transition-colors hover:text-brand p-1 shrink-0"
          :title="allSelected ? $t('files.ui.clear') : $t('files.ui.select_all')"
          :aria-label="allSelected ? $t('files.ui.clear') : $t('files.ui.select_all')"
          @click="$emit('toggleSelectAll')"
        >
          <CheckSquare v-if="allSelected" class="size-4 text-brand" aria-hidden="true" />
          <Square v-else class="size-4" aria-hidden="true" />
        </button>

        <nav aria-label="Failų kelias" class="flex flex-wrap items-center gap-1 text-sm min-w-0">
          <template v-if="activeView === 'browse' && !search">
            <button
              type="button"
              :class="[
                'inline-flex items-center gap-1.5 px-1.5 py-0.5 font-medium transition-colors hover:text-brand',
                path === 'public/files' ? 'text-brand font-semibold' : 'text-muted-foreground',
              ]"
              @click="$emit('navigateToPath', 'public/files')"
            >
              <Home class="size-3.5 shrink-0" aria-hidden="true" />
              <span>{{ $t('files.ui.root') }}</span>
            </button>
            <template v-for="(part, index) in breadcrumbParts" :key="part.path">
              <ChevronRight class="size-3.5 text-muted-foreground shrink-0" aria-hidden="true" />
              <button
                type="button"
                :class="[
                  'px-1.5 py-0.5 font-medium transition-colors hover:text-brand truncate max-w-[160px]',
                  index === breadcrumbParts.length - 1 ? 'text-foreground font-semibold' : 'text-muted-foreground',
                ]"
                @click="$emit('navigateToPath', part.path)"
              >
                {{ part.name }}
              </button>
            </template>
          </template>
          <span v-else class="px-1.5 py-0.5 font-medium text-foreground">
            {{ searchTitle }}
          </span>

          <span class="ml-1 text-xs text-muted-foreground">· {{ totalItems }}</span>
        </nav>
      </div>

      <!-- Right: Bulk Actions when items are selected -->
      <div v-if="selectedCount > 0" class="flex items-center gap-2">
        <span class="text-xs font-semibold text-muted-foreground">
          {{ $t('files.ui.selected_count', { count: String(selectedCount) }) }}
        </span>

        <button
          type="button"
          class="inline-flex size-8 items-center justify-center border border-border bg-background text-foreground transition-colors hover:bg-secondary"
          :title="$t('Pažymėti žvaigždute')"
          :aria-label="$t('Pažymėti žvaigždute')"
          @click="$emit('starSelected')"
        >
          <Star class="size-4" aria-hidden="true" />
        </button>

        <button
          type="button"
          class="inline-flex size-8 items-center justify-center border border-border bg-background text-destructive transition-colors hover:bg-destructive/10"
          :title="$t('files.ui.delete')"
          :aria-label="$t('files.ui.delete')"
          @click="$emit('deleteSelected')"
        >
          <Trash2 class="size-4" aria-hidden="true" />
        </button>

        <button
          type="button"
          class="inline-flex size-8 items-center justify-center border border-border bg-background text-muted-foreground transition-colors hover:text-foreground hover:bg-secondary"
          :title="$t('files.ui.clear')"
          :aria-label="$t('files.ui.clear')"
          @click="$emit('clearSelection')"
        >
          <X class="size-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  Check,
  CheckSquare,
  ChevronRight,
  Filter,
  Home,
  LayoutGrid,
  List,
  Search,
  Square,
  Star,
  Trash2,
  Upload,
  X,
} from 'lucide-vue-next';

import type { ActiveView, SortDir, SortKey, TypeFilter } from '../types';

import { Checkbox } from '@/Components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Spinner } from '@/Components/ui/spinner';

const props = withDefaults(
  defineProps<{
    path: string;
    search: string;
    searchEverywhere?: boolean;
    searching?: boolean;
    isUploadMode: boolean;
    selectionMode?: boolean;
    small?: boolean;
    allowUploadInSelection?: boolean;
    typeFilter?: TypeFilter;
    sortKey?: SortKey;
    sortDir?: SortDir;
    viewMode?: 'grid' | 'list';
    totalItems?: number;
    selectedCount?: number;
    allSelected?: boolean;
    activeView?: ActiveView;
  }>(),
  {
    searchEverywhere: false,
    searching: false,
    selectionMode: false,
    small: false,
    allowUploadInSelection: false,
    typeFilter: 'all',
    sortKey: 'name',
    sortDir: 'asc',
    viewMode: 'grid',
    totalItems: 0,
    selectedCount: 0,
    allSelected: false,
    activeView: 'browse',
  },
);

const emit = defineEmits<{
  'update:search': [value: string];
  'update:searchEverywhere': [value: boolean];
  'update:typeFilter': [value: TypeFilter];
  'update:sortKey': [value: SortKey];
  'update:sortDir': [value: SortDir];
  'update:viewMode': [value: 'grid' | 'list'];
  'update:isUploadMode': [value: boolean];
  'navigateToPath': [path: string];
  'showCreateFolder': [];
  'toggleSelectAll': [];
  'starSelected': [];
  'downloadSelected': [];
  'deleteSelected': [];
  'clearSelection': [];
}>();

const typeFilters = computed<{ id: TypeFilter; label: string }[]>(() => [
  { id: 'all', label: $t('Visi tipai') },
  { id: 'folder', label: $t('files.ui.folders') },
  { id: 'image', label: $t('Nuotraukos') },
  { id: 'document', label: $t('Dokumentai') },
  { id: 'media', label: $t('Garsas ir vaizdas') },
]);

const activeFilterLabel = computed(() => {
  return typeFilters.value.find(t => t.id === props.typeFilter)?.label || $t('Visi tipai');
});

const activeSortLabel = computed(() => {
  if (props.sortKey === 'modified') return $t('files.ui.modified');
  if (props.sortKey === 'size') return $t('files.ui.size');
  return $t('files.ui.name');
});

function cycleSortKey() {
  const next: SortKey = props.sortKey === 'name' ? 'modified' : props.sortKey === 'modified' ? 'size' : 'name';
  emit('update:sortKey', next);
}

const breadcrumbParts = computed(() => {
  if (props.path === 'public/files' || !props.path) return [];

  const pathWithoutPublicFiles = props.path.replace(/^public\/files\/?/, '');
  const parts = pathWithoutPublicFiles.split('/').filter(Boolean);

  return parts.map((part, index) => {
    const pathUpToIndex = `public/files/${parts.slice(0, index + 1).join('/')}`;
    return {
      name: part,
      path: pathUpToIndex,
    };
  });
});

const searchTitle = computed(() => {
  if (props.search) {
    return `${$t('Paieška')}: „${props.search}“`;
  }
  if (props.activeView === 'starred') {
    return $t('Pažymėti failai');
  }
  if (props.activeView === 'recent') {
    return $t('Naujausi failai');
  }
  return $t('files.ui.root');
});
</script>

<template>
  <div
    :class="[
      'z-20 border-b border-border bg-background',
      sticky && 'sticky top-[var(--shell-chrome-height,0px)]',
    ]"
    data-slot="file-manager-toolbar"
  >
    <div class="flex flex-col gap-2 p-3 md:flex-row md:flex-wrap md:items-center">
      <div class="flex min-w-0 flex-1 items-center gap-2 md:max-w-md">
        <label
          class="flex h-11 min-w-0 flex-1 items-center gap-2 border border-border bg-background px-3 text-muted-foreground transition-colors focus-within:border-brand"
        >
          <Search class="size-4 shrink-0" aria-hidden="true" />
          <span class="sr-only">{{ $t('files.ui.search_placeholder') }}</span>
          <input
            ref="searchInput"
            type="search"
            :value="search"
            :placeholder="$t('files.ui.search_placeholder')"
            class="w-full min-w-0 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground [&::-webkit-search-cancel-button]:hidden"
            @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
            @keydown.escape.stop="$emit('update:search', '')"
          >
          <Spinner v-if="searching" class="size-4 shrink-0" />
          <button
            v-else-if="search"
            type="button"
            class="-mr-2 flex size-9 shrink-0 items-center justify-center hover:text-foreground"
            :aria-label="$t('files.ui.clear_search')"
            @click="$emit('update:search', '')"
          >
            <X class="size-4" aria-hidden="true" />
          </button>
        </label>

        <button
          type="button"
          :aria-pressed="searchEverywhere"
          :class="controlVariants({ voice: 'sentence', active: searchEverywhere })"
          :title="$t('files.ui.search_everywhere_hint')"
          @click="$emit('update:searchEverywhere', !searchEverywhere)"
        >
          <FolderTree class="size-4" aria-hidden="true" />
          <span class="hidden sm:inline">{{ $t('files.ui.search_everywhere') }}</span>
          <span class="sr-only sm:hidden">{{ $t('files.ui.search_everywhere') }}</span>
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-2 md:ml-auto">
        <TypeFilterMenu
          :model-value="typeFilter"
          :counts="typeCounts"
          :folder-count
          :total-count
          @update:model-value="$emit('update:typeFilter', $event)"
        />

        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button type="button" :class="controlVariants({ voice: 'sentence', active: false })">
              <ArrowUpDown class="size-4" aria-hidden="true" />
              <span>{{ activeSortLabel }}</span>
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56 rounded-none">
            <DropdownMenuItem
              v-for="option in sortOptions"
              :key="`${option.key}:${option.dir}`"
              class="min-h-9 cursor-pointer justify-between pointer-coarse:min-h-11"
              @select="$emit('update:sort', option.key, option.dir)"
            >
              {{ option.label }}
              <Check v-if="option.key === sortKey && option.dir === sortDir" class="size-4 text-brand" aria-hidden="true" />
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <CollectionViewToggle
          :model-value="viewMode"
          :views="['cards', 'table']"
          @update:model-value="$emit('update:viewMode', $event as FileViewMode)"
        />

        <Button
          v-if="showUploadToggle"
          :variant="isUploadMode ? 'outline' : 'brand'"
          :voice="isUploadMode ? 'sentence' : 'brand'"
          @click="$emit('update:isUploadMode', !isUploadMode)"
        >
          <component :is="isUploadMode ? LayoutGrid : Upload" class="size-4" aria-hidden="true" />
          {{ isUploadMode ? $t('files.ui.browse') : $t('files.ui.upload') }}
        </Button>
      </div>
    </div>

    <div class="flex min-h-11 items-center gap-2 border-t border-border bg-secondary/30 px-3">
      <nav :aria-label="$t('files.ui.path')" class="flex min-w-0 flex-1 flex-wrap items-center gap-0.5 text-sm">
        <PathBreadcrumb
          v-if="!title"
          :crumbs="breadcrumbParts"
          :root-label="$t('files.ui.root')"
          :root-path="ROOT_PATH"
          @navigate="$emit('navigateToPath', $event)"
        />
        <span v-else class="truncate px-1.5 font-semibold text-foreground">{{ title }}</span>
      </nav>
      <span class="shrink-0 text-xs text-muted-foreground tabular-nums">
        {{ $t('files.ui.found_count', { count: String(resultCount) }) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  ArrowUpDown,
  Check,
  FolderTree,
  LayoutGrid,
  Search,
  Upload,
  X,
} from '@lucide/vue';

import type { FileViewMode, SortDir, SortKey, TypeFilter } from '../types';
import { relativeToRoot, ROOT_PATH } from '../utils';

import PathBreadcrumb from './PathBreadcrumb.vue';
import TypeFilterMenu from './TypeFilterMenu.vue';

import CollectionViewToggle from '@/Components/Collection/CollectionViewToggle.vue';
import { Button } from '@/Components/ui/button';
import { controlVariants } from '@/Components/ui/control';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Spinner } from '@/Components/ui/spinner';

const props = withDefaults(defineProps<{
  path: string;
  search: string;
  searchEverywhere?: boolean;
  searching?: boolean;
  isUploadMode?: boolean;
  showUploadToggle?: boolean;
  typeFilter?: TypeFilter;
  typeCounts?: Partial<Record<TypeFilter, number>>;
  folderCount?: number;
  totalCount?: number;
  sortKey?: SortKey;
  sortDir?: SortDir;
  viewMode?: FileViewMode;
  resultCount?: number;
  /** Replaces the breadcrumb (search, starred, recent). */
  title?: string;
  sticky?: boolean;
}>(), {
  typeFilter: 'all',
  typeCounts: () => ({}),
  folderCount: 0,
  totalCount: 0,
  sortKey: 'name',
  sortDir: 'asc',
  viewMode: 'cards',
  resultCount: 0,
  title: undefined,
});

defineEmits<{
  'update:search': [value: string];
  'update:searchEverywhere': [value: boolean];
  'update:typeFilter': [value: TypeFilter];
  'update:sort': [key: SortKey, dir: SortDir];
  'update:viewMode': [value: FileViewMode];
  'update:isUploadMode': [value: boolean];
  'navigateToPath': [path: string];
}>();

const searchInput = ref<HTMLInputElement | null>(null);

defineExpose({
  focusSearch: () => searchInput.value?.focus(),
});

const sortOptions = computed<{ key: SortKey; dir: SortDir; label: string }[]>(() => [
  { key: 'name', dir: 'asc', label: $t('files.sort.name_asc') },
  { key: 'name', dir: 'desc', label: $t('files.sort.name_desc') },
  { key: 'modified', dir: 'desc', label: $t('files.sort.modified_desc') },
  { key: 'modified', dir: 'asc', label: $t('files.sort.modified_asc') },
  { key: 'size', dir: 'desc', label: $t('files.sort.size_desc') },
  { key: 'size', dir: 'asc', label: $t('files.sort.size_asc') },
]);

const activeSortLabel = computed(() => sortOptions.value
  .find(option => option.key === props.sortKey && option.dir === props.sortDir)?.label ?? $t('files.sort.name_asc'));

const breadcrumbParts = computed(() => {
  const parts = relativeToRoot(props.path).split('/').filter(Boolean);

  return parts.map((part, index) => ({
    name: part,
    path: `${ROOT_PATH}/${parts.slice(0, index + 1).join('/')}`,
  }));
});
</script>

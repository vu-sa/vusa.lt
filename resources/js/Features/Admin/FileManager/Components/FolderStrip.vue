<template>
  <Collapsible v-if="directories.length > 0" v-model:open="isOpen" data-slot="folder-strip">
    <section class="border-b border-border" :aria-label="$t('files.ui.folders')">
      <div class="flex flex-wrap items-center justify-between gap-2 px-4 pt-3">
        <CollapsibleTrigger as-child>
          <Button variant="ghost" size="sm" voice="sentence" class="-ml-2 gap-2 px-2 font-semibold">
            <ChevronRight
              class="size-4 transition-transform duration-200"
              :class="{ 'rotate-90': isOpen }"
              aria-hidden="true"
            />
            {{ $t('files.ui.folders') }}
            <span class="text-muted-foreground tabular-nums">{{ directories.length }}</span>
          </Button>
        </CollapsibleTrigger>

        <Input
          v-if="isOpen && directories.length > FILTER_THRESHOLD"
          v-model="filter"
          class="h-9 w-full sm:w-56"
          :placeholder="$t('files.ui.filter_folders')"
          :aria-label="$t('files.ui.filter_folders')"
        />
      </div>

      <CollapsibleContent>
        <div class="px-4 pt-2 pb-4">
          <p v-if="filteredDirectories.length === 0" class="py-2 text-sm text-muted-foreground">
            {{ $t('files.ui.no_folders_match') }}
          </p>

          <ul v-else class="grid grid-cols-1 gap-2 min-[480px]:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
            <!-- eslint-disable-next-line vuejs-accessibility/no-static-element-interactions -->
            <li
              v-for="directory in filteredDirectories"
              :key="directory.path"
              :class="[
                'group flex min-w-0 items-center border bg-background transition-colors',
                dropTarget === directory.path ? 'border-brand bg-brand/5' : 'border-border hover:border-brand',
              ]"
              @dragover.prevent.stop="dropTarget = acceptsDrop ? directory.path : null"
              @dragleave="dropTarget = null"
              @drop.prevent.stop="handleDrop($event, directory)"
            >
              <button
                type="button"
                class="flex min-h-11 min-w-0 flex-1 items-center gap-2.5 px-3 py-2 text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40"
                @click="$emit('open', directory)"
              >
                <Folder class="size-4 shrink-0 text-brand" aria-hidden="true" />
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-medium text-foreground" :title="directory.name">{{ directory.name }}</span>
                  <span v-if="directory.items !== undefined" class="block text-xs text-muted-foreground">
                    {{ directory.items === 0 ? $t('files.ui.folder_empty') : $t('files.ui.folder_items', { count: String(directory.items) }) }}
                  </span>
                </span>
              </button>
              <ItemActionsMenu
                v-if="actionsFor"
                :label="directory.name"
                v-bind="actionsFor(directory)"
                trigger-class="mr-1"
                @select="$emit('action', $event, directory)"
              />
            </li>
          </ul>
        </div>
      </CollapsibleContent>
    </section>
  </Collapsible>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Folder } from '@lucide/vue';

import type { DirectoryEntry, ItemAction } from '../types';

import ItemActionsMenu from './ItemActionsMenu.vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';

type FolderLike = Pick<DirectoryEntry, 'path' | 'name' | 'items'>;

const props = defineProps<{
  directories: FolderLike[];
  /** Folder ⋯ menu; omitted in the picker, which only browses. */
  actionsFor?: (directory: FolderLike) => { actions: ItemAction[]; note?: string };
  /** Dropping OS files onto a folder uploads them there. */
  acceptsDrop?: boolean;
}>();

const emit = defineEmits<{
  open: [directory: FolderLike];
  action: [key: string, directory: FolderLike];
  drop: [directory: FolderLike, files: File[]];
}>();

/**
 * Above this many folders the strip starts collapsed. The manager's root holds ~50, which
 * previously filled the entire first page and pushed every file out of sight.
 */
const COLLAPSE_THRESHOLD = 12;

/** Short lists are scanned faster than filtered. */
const FILTER_THRESHOLD = 4;

const filter = ref('');
const dropTarget = ref<string | null>(null);

/**
 * Only long lists remember being collapsed. A single global flag meant that collapsing the
 * ~50-folder root also hid the two subfolders of every directory you opened afterwards.
 */
const collapsePreference = useStorage<boolean>('fileManager-foldersCollapsed', true);

const isLongList = computed(() => props.directories.length > COLLAPSE_THRESHOLD);

const isOpen = ref(!isLongList.value || !collapsePreference.value);

watch(isOpen, (open) => {
  if (isLongList.value) {
    collapsePreference.value = !open;
  }
});

watch(() => props.directories.map(d => d.path).join('|'), () => {
  filter.value = '';
  isOpen.value = !isLongList.value || !collapsePreference.value;
});

const filteredDirectories = computed(() => {
  if (!filter.value.trim()) return props.directories;
  const needle = filter.value.toLowerCase();
  return props.directories.filter(d => d.name.toLowerCase().includes(needle));
});

function handleDrop(event: DragEvent, directory: FolderLike) {
  dropTarget.value = null;
  const files = Array.from(event.dataTransfer?.files ?? []);
  if (props.acceptsDrop && files.length > 0) {
    emit('drop', directory, files);
  }
}
</script>

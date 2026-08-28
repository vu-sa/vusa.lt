<template>
  <Collapsible v-if="directories.length > 0" v-model:open="isOpen" data-slot="folder-strip">
    <div class="rounded-md border border-border shadow-xs">
      <div class="flex flex-col gap-3 border-b border-border bg-muted/50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <CollapsibleTrigger as-child>
          <Button variant="ghost" size="sm" class="h-8 justify-start gap-2 px-2 font-medium">
            <IFluentChevronRight24Regular
              class="h-4 w-4 transition-transform duration-200"
              :class="{ 'rotate-90': isOpen }"
            />
            <IFluentFolder24Filled class="h-4 w-4 text-muted-foreground" />
            {{ $t('files.ui.folders') }} ({{ directories.length }})
          </Button>
        </CollapsibleTrigger>

        <Input
          v-if="isOpen"
          v-model="filter"
          class="h-8 w-full sm:w-64"
          :placeholder="$t('files.ui.filter_folders')"
        />
      </div>

      <CollapsibleContent>
        <div class="p-3">
          <div v-if="filteredDirectories.length === 0" class="px-1 py-2 text-sm text-muted-foreground">
            {{ $t('files.ui.no_folders_match') }}
          </div>

          <div v-else class="flex flex-wrap gap-2">
            <button
              v-for="directory in filteredDirectories"
              :key="directory.path"
              type="button"
              class="flex max-w-full items-center gap-2 rounded-md border border-border bg-background px-3 py-2 text-sm transition-colors hover:border-vusa-red hover:bg-muted/50"
              @click="$emit('open', directory)"
            >
              <IFluentFolder24Filled class="h-4 w-4 flex-shrink-0 text-muted-foreground" />
              <span class="truncate">{{ directory.name }}</span>
            </button>
          </div>
        </div>
      </CollapsibleContent>
    </div>
  </Collapsible>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useStorage } from '@vueuse/core';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';

import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';
import IFluentChevronRight24Regular from '~icons/fluent/chevron-right-24-regular';

interface DirectoryEntry {
  path: string;
  name: string;
}

const props = defineProps<{
  directories: DirectoryEntry[];
  loading?: boolean;
}>();

defineEmits<{
  open: [directory: DirectoryEntry];
}>();

/**
 * Above this many folders the strip starts collapsed. The manager's root holds ~50, which
 * previously filled the entire first page and pushed every file out of sight.
 */
const COLLAPSE_THRESHOLD = 12;

const filter = ref('');

/**
 * Only long lists remember being collapsed. A single global flag meant that collapsing the
 * ~50-folder root also hid the two subfolders of every directory you opened afterwards — and
 * an invisible subfolder is exactly what makes "delete folder" fail for no visible reason.
 */
const collapsePreference = useStorage<boolean>('fileManager-foldersCollapsed', true);

const isLongList = computed(() => props.directories.length > COLLAPSE_THRESHOLD);

const isOpen = ref(!isLongList.value || !collapsePreference.value);

watch(isOpen, (open) => {
  if (isLongList.value) {
    collapsePreference.value = !open;
  }
});

watch(() => props.directories, () => {
  filter.value = '';
  isOpen.value = !isLongList.value || !collapsePreference.value;
});

const filteredDirectories = computed(() => {
  const needle = filter.value.trim().toLowerCase();
  if (!needle) return props.directories;

  return props.directories.filter(directory => directory.name.toLowerCase().includes(needle));
});
</script>

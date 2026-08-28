<template>
  <div class="space-y-4">
    <!-- Header with actions and search -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
      <div v-if="!selectionMode || allowUploadInSelection" class="flex gap-2">
        <!-- Toggle between browse and upload modes -->
        <Button
          :variant="isUploadMode ? 'outline' : 'default'"
          size="sm"
          @click="$emit('update:isUploadMode', false)"
        >
          <IFluentFolder24Regular class="mr-2 h-4 w-4" />
          {{ $t('files.ui.browse') }}
        </Button>
        <Button
          :variant="isUploadMode ? 'default' : 'outline'"
          size="sm"
          @click="$emit('update:isUploadMode', true)"
        >
          <IFluentCloudArrowUp24Regular class="mr-2 h-4 w-4" />
          {{ $t('files.ui.upload') }}
        </Button>
        <Button variant="outline" size="sm" @click="$emit('showCreateFolder')">
          <IFluentFolderAdd24Regular class="mr-2 h-4 w-4" />
          {{ $t('files.ui.add_folder') }}
        </Button>
      </div>
      <div class="flex-1" />
      <!-- Fixed width and height container to prevent layout shifts -->
      <div v-if="!isUploadMode" class="flex w-full flex-col gap-1 sm:w-[320px]">
        <div class="flex h-10 items-center gap-2">
          <Input
            :model-value="search"
            class="w-full"
            :placeholder="$t('files.ui.search_placeholder')"
            @update:model-value="$emit('update:search', $event)"
          />
          <Spinner v-if="searching" class="h-4 w-4 flex-shrink-0" />
        </div>
        <!-- Local filtering only ever sees the folder you are standing in; the toggle is what
             makes a file findable when you don't already know which of ~50 folders holds it. -->
        <label class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
          <Checkbox
            :model-value="searchEverywhere"
            @update:model-value="$emit('update:searchEverywhere', $event === true)"
          />
          {{ $t('files.ui.search_everywhere') }}
        </label>
      </div>
      <div v-else class="w-full sm:w-[320px]" />
    </div>

    <!-- Interactive breadcrumb navigation -->
    <div class="flex items-center gap-2 text-sm bg-muted/30 rounded-md px-3 py-2">
      <IFluentFolder24Filled class="h-4 w-4 text-muted-foreground flex-shrink-0" />
      <nav class="flex items-center gap-1 text-foreground min-w-0 flex-1">
        <!-- Upload mode indicator -->
        <span v-if="isUploadMode && (!selectionMode || allowUploadInSelection)" class="text-xs text-muted-foreground mr-2">
          {{ $t('files.ui.uploading_into') }}
        </span>
        <button
          class="font-medium transition-colors truncate"
          :class="{
            'text-vusa-red hover:text-vusa-red': path === 'public/files',
            'hover:text-vusa-red': !isUploadMode,
            'cursor-default': isUploadMode
          }"
          @click="!isUploadMode ? $emit('navigateToPath', 'public/files') : undefined"
        >
          {{ $t('files.ui.root') }}
        </button>
        <template v-if="breadcrumbParts.length > 0">
          <template v-for="(part, index) in breadcrumbParts" :key="index">
            <span class="text-muted-foreground flex-shrink-0">/</span>
            <button
              class="transition-colors truncate"
              :class="{
                'text-vusa-red font-medium': index === breadcrumbParts.length - 1,
                'text-muted-foreground': index < breadcrumbParts.length - 1,
                'hover:text-vusa-red': !isUploadMode,
                'cursor-default': isUploadMode
              }"
              @click="!isUploadMode ? $emit('navigateToPath', part.path) : undefined"
            >
              {{ part.name }}
            </button>
          </template>
        </template>
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { Spinner } from '@/Components/ui/spinner';

// Import icons
import IFluentFolder24Regular from '~icons/fluent/folder-24-regular';
import IFluentCloudArrowUp24Regular from '~icons/fluent/cloud-arrow-up-24-regular';
import IFluentFolderAdd24Regular from '~icons/fluent/folder-add-24-regular';
import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';

const props = defineProps<{
  path: string;
  search: string;
  searchEverywhere?: boolean;
  searching?: boolean;
  isUploadMode: boolean;
  selectionMode?: boolean;
  small?: boolean;
  // Allow upload toggle even in selection mode
  allowUploadInSelection?: boolean;
}>();

const emit = defineEmits<{
  'update:search': [value: string];
  'update:searchEverywhere': [value: boolean];
  'update:isUploadMode': [value: boolean];
  'navigateToPath': [path: string];
  'showCreateFolder': [];
}>();

const breadcrumbParts = computed(() => {
  if (props.path === 'public/files') return [];

  const pathWithoutPublicFiles = props.path.replace('public/files/', '');
  const parts = pathWithoutPublicFiles.split('/');

  return parts.map((part, index) => {
    const pathUpToIndex = `public/files/${parts.slice(0, index + 1).join('/')}`;
    return {
      name: part,
      path: pathUpToIndex,
    };
  });
});
</script>

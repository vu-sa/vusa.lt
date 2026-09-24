<template>
  <div
    :class="[
      'group relative flex flex-col border text-left transition-colors cursor-pointer select-none bg-background',
      isSelected || isMultiSelected
        ? 'border-brand bg-brand/5 ring-1 ring-brand'
        : 'border-border hover:bg-secondary/60 hover:border-muted-foreground/30',
    ]"
    role="button"
    tabindex="0"
    :aria-selected="isSelected || isMultiSelected"
    :aria-label="item.name"
    @click="handleClick"
    @dblclick="handleDoubleClick"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <!-- Checkbox Selector (Top-Left) -->
    <button
      v-if="!isFolder"
      type="button"
      :class="[
        'absolute left-2 top-2 z-10 flex size-5 cursor-pointer items-center justify-center border transition-all',
        isMultiSelected || isSelected
          ? 'border-brand bg-brand-fill text-brand-foreground opacity-100'
          : 'border-border bg-background/90 text-foreground opacity-0 group-hover:opacity-100',
      ]"
      :title="isMultiSelected ? $t('Atžymėti') : $t('Pažymėti')"
      :aria-label="isMultiSelected ? $t('Atžymėti') : $t('Pažymėti')"
      @click.stop="$emit('toggleSelect', item)"
    >
      <Check v-if="isMultiSelected || isSelected" class="size-3.5" aria-hidden="true" />
    </button>

    <!-- Quick Preview Eye Button (Top-Right, Images only) -->
    <button
      v-if="isImage"
      type="button"
      class="absolute right-2 top-2 z-10 flex size-6 cursor-pointer items-center justify-center border border-border bg-background/90 text-muted-foreground opacity-0 transition-opacity hover:text-brand hover:border-brand group-hover:opacity-100"
      :title="$t('Peržiūrėti')"
      :aria-label="$t('Peržiūrėti')"
      @click.stop="$emit('preview', item)"
    >
      <Eye class="size-3.5" aria-hidden="true" />
    </button>

    <!-- Media Frame (4:3 aspect ratio) -->
    <div
      class="relative flex aspect-[4/3] w-full items-center justify-center overflow-hidden border-b border-border bg-secondary/30"
    >
      <!-- Image Thumbnail -->
      <img
        v-if="isImage && !thumbnailFailed"
        :src="thumbnailSrc"
        :alt="item.name"
        loading="lazy"
        decoding="async"
        class="size-full object-cover transition-transform duration-200 group-hover:scale-105"
        @error="handleThumbnailError"
      />

      <!-- Folder Icon -->
      <Folder
        v-else-if="isFolder"
        class="size-10 text-brand transition-colors"
        aria-hidden="true"
      />

      <!-- Type Icon Fallback -->
      <component
        :is="typeIcon"
        v-else
        class="size-9 text-muted-foreground transition-colors group-hover:text-foreground"
        aria-hidden="true"
      />
    </div>

    <!-- Caption / Details -->
    <div class="flex items-start justify-between gap-1.5 p-2.5 min-w-0">
      <div class="min-w-0 flex-1">
        <p class="truncate text-xs font-semibold text-foreground leading-tight" :title="item.name">
          {{ item.name }}
        </p>
        <p class="mt-1 text-[11px] text-muted-foreground leading-none">
          <span v-if="isFolder">{{ $t('files.ui.folders') }}</span>
          <span v-else>{{ formattedSize }}</span>
        </p>
      </div>

      <!-- Star Indicator -->
      <button
        v-if="!isFolder"
        type="button"
        :class="[
          'p-0.5 shrink-0 transition-colors',
          isStarred
            ? 'text-status-attention fill-status-attention opacity-100'
            : 'text-muted-foreground/40 hover:text-status-attention opacity-0 group-hover:opacity-100',
        ]"
        :title="isStarred ? $t('Pašalinti iš pažymėtų') : $t('Pažymėti žvaigždute')"
        :aria-label="isStarred ? $t('Pašalinti iš pažymėtų') : $t('Pažymėti žvaigždute')"
        @click.stop="$emit('toggleStar', item)"
      >
        <Star class="size-3.5" :class="{ 'fill-status-attention': isStarred }" aria-hidden="true" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, Eye, Folder, Star } from 'lucide-vue-next';

import type { FileEntry } from '../types';
import { formatBytes } from '../utils';

import { getFileIcon } from '@/Utils/fileIcons';

const props = withDefaults(
  defineProps<{
    item: FileEntry;
    isSelected: boolean;
    isMultiSelected: boolean;
    selectionMode?: boolean;
    isMultiSelectMode?: boolean;
    isFolder?: boolean;
    isStarred?: boolean;
  }>(),
  {
    selectionMode: false,
    isMultiSelectMode: false,
    isFolder: false,
    isStarred: false,
  },
);

const emit = defineEmits<{
  click: [item: FileEntry, event?: MouseEvent];
  doubleClick: [item: FileEntry];
  toggleSelect: [item: FileEntry];
  toggleStar: [item: FileEntry];
  preview: [item: FileEntry];
}>();

const isFolder = computed(() => props.isFolder || false);

const isImage = computed(() => {
  if (isFolder.value) return false;
  return /\.(jpg|jpeg|png|webp|gif|svg|avif)$/i.test(props.item?.name ?? '');
});

const typeIcon = computed(() => getFileIcon(props.item?.name ?? props.item?.path ?? ''));

const originalSrc = computed(() => `/uploads/${props.item?.path?.replace(/^public\//, '') || ''}`);

const thumbnailFailed = ref(false);

const thumbnailSrc = computed(() => {
  if (thumbnailFailed.value) return originalSrc.value;
  return route('api.v1.admin.files.thumbnail', { path: props.item?.path, w: 320 });
});

function handleThumbnailError() {
  thumbnailFailed.value = true;
}

const formattedSize = computed(() => formatBytes(props.item?.size));

function handleClick(e: MouseEvent) {
  emit('click', props.item, e);
}

function handleDoubleClick() {
  emit('doubleClick', props.item);
}
</script>

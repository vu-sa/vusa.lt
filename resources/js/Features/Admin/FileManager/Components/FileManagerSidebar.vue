<template>
  <aside
    class="flex flex-col gap-6 bg-background p-4 border-b border-border lg:border-b-0 lg:border-r lg:min-w-[240px] lg:max-w-[260px]"
  >
    <!-- View Navigation -->
    <nav class="flex flex-row lg:flex-col gap-1 overflow-x-auto pb-1 lg:pb-0" aria-label="Failų rodiniai">
      <button
        v-for="item in navItems"
        :key="item.id"
        type="button"
        :class="[
          'flex min-h-10 items-center gap-2.5 px-3 text-sm font-medium transition-colors shrink-0',
          activeView === item.id
            ? 'bg-brand/10 text-brand font-semibold'
            : 'text-foreground hover:bg-secondary',
        ]"
        @click="selectView(item.id)"
      >
        <component :is="item.icon" class="size-4 shrink-0 text-muted-foreground" :class="{ 'text-brand': activeView === item.id }" aria-hidden="true" />
        <span class="flex-1 text-left">{{ item.label }}</span>
        <span
          v-if="item.badge"
          class="inline-flex min-w-5 items-center justify-center bg-secondary px-1.5 py-0.5 text-[10px] font-bold text-muted-foreground"
        >
          {{ item.badge }}
        </span>
      </button>
    </nav>

    <!-- Folders Section -->
    <div class="border-t border-border pt-4">
      <div class="flex items-center justify-between">
        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
          {{ $t('files.ui.folders') }}
        </p>
        <button
          type="button"
          class="text-muted-foreground transition-colors hover:text-brand p-1"
          :title="$t('files.ui.add_folder')"
          :aria-label="$t('files.ui.add_folder')"
          @click="$emit('openCreateFolder')"
        >
          <FolderPlus class="size-3.5" aria-hidden="true" />
        </button>
      </div>

      <div class="mt-2 flex flex-col gap-0.5 max-h-64 lg:max-h-80 overflow-y-auto">
        <!-- Root Directory -->
        <button
          type="button"
          :class="[
            'flex min-h-9 items-center gap-2 px-2.5 py-1.5 text-left text-sm transition-colors',
            activeView === 'browse' && isRoot
              ? 'bg-brand/10 text-brand font-medium'
              : 'text-foreground hover:bg-secondary',
          ]"
          @click="$emit('goHome')"
        >
          <Home class="size-4 shrink-0 text-muted-foreground" :class="{ 'text-brand': activeView === 'browse' && isRoot }" aria-hidden="true" />
          <span class="truncate">{{ $t('files.ui.root') }}</span>
        </button>

        <!-- Up / Parent Navigation if not at root -->
        <button
          v-if="!isRoot"
          type="button"
          class="flex min-h-9 items-center gap-2 px-2.5 py-1.5 text-left text-xs text-muted-foreground hover:text-foreground hover:bg-secondary transition-colors"
          @click="$emit('goUp')"
        >
          <ArrowUp class="size-3.5 shrink-0" aria-hidden="true" />
          <span class="truncate">.. ({{ $t('files.ui.go_back') }})</span>
        </button>

        <!-- Current subdirectories -->
        <div v-if="directories.length === 0 && !isRoot" class="px-2.5 py-2 text-xs text-muted-foreground">
          {{ $t('files.ui.no_folders_match') }}
        </div>

        <button
          v-for="dir in directories"
          :key="dir.path"
          type="button"
          :class="[
            'flex min-h-9 items-center gap-2 px-2.5 py-1.5 text-left text-sm transition-colors group',
            currentPath === dir.path && activeView === 'browse'
              ? 'bg-brand/10 text-brand font-medium'
              : 'text-foreground hover:bg-secondary',
          ]"
          @click="$emit('openFolder', dir.path)"
        >
          <Folder class="size-4 shrink-0 text-muted-foreground group-hover:text-brand transition-colors" aria-hidden="true" />
          <span class="truncate flex-1">{{ dir.name }}</span>
        </button>
      </div>
    </div>

    <!-- Storage / Summary Info -->
    <div class="mt-auto border-t border-border pt-4">
      <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
        {{ $t('files.ui.location') }}
      </p>
      <p class="mt-1 text-xs text-foreground font-medium truncate" :title="currentPath">
        {{ displayPath }}
      </p>
      <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
        <span>{{ $t('files.ui.files_count', { count: String(filesCount) }) }}</span>
        <span>{{ formattedTotalSize }}</span>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUp, Clock, Folder, FolderPlus, Home, Star } from 'lucide-vue-next';

import type { ActiveView, DirectoryEntry } from '../types';
import { formatBytes } from '../utils';

const props = withDefaults(
  defineProps<{
    activeView: ActiveView;
    directories: DirectoryEntry[];
    currentPath: string;
    starredCount?: number;
    filesCount?: number;
    totalSize?: number;
  }>(),
  {
    starredCount: 0,
    filesCount: 0,
    totalSize: 0,
  },
);

const emit = defineEmits<{
  'update:activeView': [view: ActiveView];
  'openFolder': [path: string];
  'openCreateFolder': [];
  'goHome': [];
  'goUp': [];
}>();

const isRoot = computed(() => props.currentPath === 'public/files' || props.currentPath === 'public/files/');

const displayPath = computed(() => {
  if (isRoot.value) return $t('files.ui.root');
  return props.currentPath.replace(/^public\/files\/?/, '');
});

const formattedTotalSize = computed(() => formatBytes(props.totalSize));

const navItems = computed(() => [
  { id: 'browse' as const, label: $t('files.ui.root'), icon: Home },
  { id: 'starred' as const, label: $t('Pažymėti'), icon: Star, badge: props.starredCount || undefined },
  { id: 'recent' as const, label: $t('Naujausi'), icon: Clock },
]);

function selectView(view: ActiveView) {
  emit('update:activeView', view);
}
</script>

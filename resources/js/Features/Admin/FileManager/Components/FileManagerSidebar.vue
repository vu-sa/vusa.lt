<template>
  <aside
    :class="[
      'flex flex-col border-b border-border bg-background',
      'lg:sticky lg:top-[var(--shell-chrome-height,0px)] lg:self-start lg:border-r lg:border-b-0',
      'lg:h-[calc(var(--shell-scroll-height,100svh)-var(--shell-chrome-height,0px))]',
    ]"
    data-slot="file-manager-sidebar"
  >
    <nav class="flex gap-1 p-2 lg:flex-col lg:p-3" :aria-label="$t('files.ui.views')">
      <button
        v-for="item in navItems"
        :key="item.id"
        type="button"
        :aria-current="activeView === item.id ? 'page' : undefined"
        :class="[
          'flex min-h-9 flex-1 items-center gap-2.5 px-3 text-sm transition-colors pointer-coarse:min-h-11 lg:flex-none',
          activeView === item.id
            ? 'bg-brand/10 font-semibold text-brand'
            : 'text-foreground hover:bg-secondary',
        ]"
        @click="$emit('update:activeView', item.id)"
      >
        <component :is="item.icon" :class="['size-4 shrink-0', activeView === item.id ? 'text-brand' : 'text-muted-foreground']" aria-hidden="true" />
        <span class="flex-1 truncate text-left">{{ item.label }}</span>
        <span v-if="item.badge" class="font-mono text-xs text-muted-foreground tabular-nums">{{ item.badge }}</span>
      </button>
    </nav>

    <div class="hidden min-h-0 flex-1 flex-col border-t border-border lg:flex">
      <div class="flex items-center justify-between py-1 pr-1 pl-4">
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
          {{ $t('files.ui.folders') }}
        </p>
        <Button
          v-if="canCreateFolder"
          variant="ghost"
          size="icon-sm"
          :title="$t('files.ui.add_folder')"
          :aria-label="$t('files.ui.add_folder')"
          @click="$emit('createFolder')"
        >
          <FolderPlus class="size-4" aria-hidden="true" />
        </Button>
      </div>

      <ul class="min-h-0 flex-1 overflow-y-auto px-2 pb-3" :aria-label="$t('files.ui.folder_tree')">
        <li v-for="node in tree" :key="node.path" class="group flex items-center">
          <button
            type="button"
            :aria-current="node.current ? 'location' : undefined"
            :style="{ paddingLeft: `${0.5 + node.depth * 0.875}rem` }"
            :class="[
              'flex min-h-9 min-w-0 flex-1 items-center gap-2 pr-2 text-left text-sm transition-colors pointer-coarse:min-h-11',
              node.current ? 'bg-brand/10 font-semibold text-brand' : 'text-foreground hover:bg-secondary',
            ]"
            @click="$emit('openFolder', node.path)"
          >
            <component
              :is="node.depth === 0 ? Home : node.current || node.ancestor ? FolderOpen : Folder"
              :class="['size-4 shrink-0', node.current ? 'text-brand' : 'text-muted-foreground']"
              aria-hidden="true"
            />
            <span class="truncate" :title="node.name">{{ node.name }}</span>
            <span v-if="node.items === 0" class="ml-auto shrink-0 text-xs text-muted-foreground">{{ $t('files.ui.folder_empty_short') }}</span>
          </button>
          <ItemActionsMenu
            v-if="node.child && actionsFor"
            :label="node.name"
            v-bind="actionsFor(node)"
            trigger-class="text-muted-foreground"
            @select="$emit('folderAction', $event, node)"
          />
        </li>
      </ul>

      <div class="border-t border-border px-4 py-3 text-xs text-muted-foreground">
        <p class="truncate font-medium text-foreground" :title="displayPath">
          {{ displayPath }}
        </p>
        <p class="mt-1 flex justify-between gap-2 tabular-nums">
          <span>{{ $t('files.ui.files_count', { count: String(filesCount) }) }}</span>
          <span>{{ formatBytes(totalSize) }}</span>
        </p>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Clock, Files, Folder, FolderOpen, FolderPlus, Home, Star } from '@lucide/vue';

import type { ActiveView, DirectoryEntry, ItemAction } from '../types';
import { formatBytes, relativeToRoot, ROOT_PATH } from '../utils';

import ItemActionsMenu from './ItemActionsMenu.vue';

import { Button } from '@/Components/ui/button';

interface TreeNode {
  path: string;
  name: string;
  depth: number;
  items?: number;
  current: boolean;
  ancestor: boolean;
  child: boolean;
}

const props = withDefaults(defineProps<{
  activeView: ActiveView;
  directories: DirectoryEntry[];
  currentPath: string;
  starredCount?: number;
  filesCount?: number;
  totalSize?: number;
  canCreateFolder?: boolean;
  actionsFor?: (directory: Pick<DirectoryEntry, 'path' | 'name' | 'items'>) => { actions: ItemAction[]; note?: string };
}>(), {
  starredCount: 0,
  filesCount: 0,
  totalSize: 0,
  actionsFor: undefined,
});

defineEmits<{
  'update:activeView': [view: ActiveView];
  'openFolder': [path: string];
  'folderAction': [key: string, directory: Pick<DirectoryEntry, 'path' | 'name' | 'items'>];
  'createFolder': [];
}>();

const displayPath = computed(() => relativeToRoot(props.currentPath) || $t('files.ui.root'));

/** Root → each ancestor → the open folder → its subfolders, so every level up stays one click away. */
const tree = computed<TreeNode[]>(() => {
  const segments = relativeToRoot(props.currentPath).split('/').filter(Boolean);
  const chain: TreeNode[] = [{
    path: ROOT_PATH,
    name: $t('files.ui.root'),
    depth: 0,
    current: segments.length === 0,
    ancestor: segments.length > 0,
    child: false,
  }];

  segments.forEach((segment, index) => {
    const isCurrent = index === segments.length - 1;
    chain.push({
      path: `${ROOT_PATH}/${segments.slice(0, index + 1).join('/')}`,
      name: segment,
      depth: index + 1,
      current: isCurrent,
      ancestor: !isCurrent,
      child: false,
    });
  });

  const childDepth = segments.length + 1;

  return [
    ...chain,
    ...props.directories.map(directory => ({
      path: directory.path,
      name: directory.name,
      depth: childDepth,
      items: directory.items,
      current: false,
      ancestor: false,
      child: true,
    })),
  ];
});

const navItems = computed(() => [
  { id: 'browse' as const, label: $t('files.ui.view_browse'), icon: Files, badge: undefined },
  { id: 'starred' as const, label: $t('files.ui.view_starred'), icon: Star, badge: props.starredCount || undefined },
  { id: 'recent' as const, label: $t('files.ui.view_recent'), icon: Clock, badge: undefined },
]);
</script>

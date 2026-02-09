<template>
  <CommandItem
    :value="action.id"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full">
      <!-- Icon container with background based on category -->
      <div
        :class="[
          'flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors',
          iconContainerClasses
        ]"
      >
        <component :is="action.icon" class="size-4" />
      </div>

      <!-- Label -->
      <span class="flex-1 truncate text-sm font-medium group-hover:text-foreground transition-colors">
        {{ action.label }}
      </span>

      <!-- Category badge for create actions -->
      <span
        v-if="action.category === 'create'"
        class="hidden sm:inline-flex items-center rounded-full bg-primary/10 text-primary px-2 py-0.5 text-[10px] font-medium"
      >
        {{ $t('Naujas') }}
      </span>

      <!-- Shortcut or arrow -->
      <template v-if="action.shortcut">
        <kbd class="hidden sm:inline-flex h-5 items-center justify-center rounded border bg-muted/50 px-1.5 font-mono text-[10px] text-muted-foreground">
          {{ action.shortcut }}
        </kbd>
      </template>
      <ChevronRight v-else class="size-4 text-muted-foreground/40 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
    </div>
  </CommandItem>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight } from 'lucide-vue-next';

import type { CommandAction } from '../useCommandActions';

import { CommandItem } from '@/Components/ui/command';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';

const props = defineProps<{
  action: CommandAction;
}>();

const { close, addRecentItem } = useCommandPalette();

const iconContainerClasses = computed(() => {
  switch (props.action.category) {
    case 'create':
      return 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 group-hover:bg-emerald-500/15 dark:group-hover:bg-emerald-500/25';
    case 'navigation':
      return 'bg-zinc-500/10 text-zinc-600 dark:bg-zinc-500/20 dark:text-zinc-400 group-hover:bg-zinc-500/15 dark:group-hover:bg-zinc-500/25';
    default:
      return 'bg-muted/50 text-muted-foreground group-hover:bg-muted';
  }
});

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.action.id,
    type: 'action',
    title: props.action.label,
  } as Omit<RecentItem, 'timestamp'>);

  // Execute action and close
  close();
  props.action.action();
};
</script>

<template>
  <CommandItem
    :value="itemValue"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full min-w-0">
      <!-- Icon container with background -->
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400 group-hover:bg-sky-500/15 dark:group-hover:bg-sky-500/25 transition-colors">
        <FileText class="size-4" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate text-sm group-hover:text-foreground transition-colors">
            {{ page.title || $t('Be pavadinimo') }}
          </span>
          <span
            v-if="page.lang"
            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset bg-zinc-500/10 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-500/20 dark:text-zinc-400 uppercase"
          >
            {{ page.lang }}
          </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
          <span v-if="page.tenant_name" class="shrink-0 font-medium">{{ page.tenant_name }}</span>
          <span v-if="page.tenant_name && page.category_name" class="text-muted-foreground/40">•</span>
          <span v-if="page.category_name" class="truncate">{{ page.category_name }}</span>
        </div>
      </div>

      <!-- Arrow indicator -->
      <ChevronRight class="size-4 text-muted-foreground/40 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
    </div>
  </CommandItem>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, FileText } from 'lucide-vue-next';

import { CommandItem } from '@/Components/ui/command';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import type { PageSearchResult } from '@/Composables/useAdminSearch';

const props = defineProps<{
  page: PageSearchResult;
}>();

const { close, addRecentItem } = useCommandPalette();

const itemValue = computed(() => `page-${props.page.id}`);

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.page.id,
    type: 'page',
    title: props.page.title || $t('Be pavadinimo'),
    href: route('pages.edit', props.page.id),
  } as Omit<RecentItem, 'timestamp'>);

  // Navigate to edit page
  close();
  router.visit(route('pages.edit', props.page.id));
};
</script>

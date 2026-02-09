<template>
  <CommandItem
    :value="itemValue"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full min-w-0">
      <!-- Icon container with background -->
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-teal-500/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400 group-hover:bg-teal-500/15 dark:group-hover:bg-teal-500/25 transition-colors">
        <FileArchive class="size-4" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate text-sm group-hover:text-foreground transition-colors">
            {{ document.title || $t('Be pavadinimo') }}
          </span>
          <span
            v-if="document.content_type"
            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset bg-zinc-500/10 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-500/20 dark:text-zinc-400 truncate max-w-[120px] shrink-0"
            :title="document.content_type"
          >
            {{ document.content_type }}
          </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
          <span v-if="document.institution_name_lt" class="shrink-0 font-medium">{{ document.institution_name_lt }}</span>
          <span v-if="document.institution_name_lt && document.document_year" class="text-muted-foreground/40">•</span>
          <span v-if="document.document_year" class="shrink-0 tabular-nums">{{ document.document_year }}</span>
        </div>
      </div>

      <!-- External link indicator -->
      <ExternalLink class="size-4 text-muted-foreground/40 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
    </div>
  </CommandItem>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, FileArchive } from 'lucide-vue-next';

import { CommandItem } from '@/Components/ui/command';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import type { DocumentSearchResult } from '@/Composables/useAdminSearch';

const props = defineProps<{
  document: DocumentSearchResult;
}>();

const { close, addRecentItem } = useCommandPalette();

const itemValue = computed(() => `document-${props.document.id}`);

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.document.id,
    type: 'document',
    title: props.document.title || $t('Be pavadinimo'),
    href: route('documents.show', props.document.id),
  } as Omit<RecentItem, 'timestamp'>);

  // Navigate to document show page (which will have option to open in SharePoint)
  close();
  router.visit(route('documents.show', props.document.id));
};
</script>

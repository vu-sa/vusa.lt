<template>
  <li class="flex items-center gap-3 py-2" data-slot="fileable-file-row">
    <FileText class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
    <div class="min-w-0 flex-1">
      <!-- A plain link to a redirect: opening mints the reader's anonymous link on the way. -->
      <a
        :href="route('fileableFiles.open', file.id)"
        target="_blank"
        rel="noopener noreferrer"
        class="block truncate text-sm font-medium text-foreground hover:underline"
      >{{ file.name }}</a>
      <span class="block truncate text-xs text-muted-foreground">
        {{ meta }}
      </span>
    </div>
    <Button
      type="button"
      variant="ghost"
      size="icon-sm"
      class="shrink-0 text-muted-foreground"
      :title="$t('Kopijuoti nuorodą')"
      :aria-label="$t('Kopijuoti nuorodą')"
      @click="emit('copy', file)"
    >
      <Link2 class="size-4" />
    </Button>
    <Button
      v-if="canDelete"
      type="button"
      variant="ghost"
      size="icon-sm"
      class="shrink-0 text-muted-foreground hover:text-destructive"
      :title="$t('Ištrinti')"
      :aria-label="$t('Ištrinti')"
      @click="emit('delete', file)"
    >
      <Trash2 class="size-4" />
    </Button>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { FileText, Link2, Trash2 } from 'lucide-vue-next';

import type { FileableFileItem } from './types';

import { Button } from '@/Components/ui/button';
import { fileTypeLabel } from '@/Constants/fileTypes';

const props = defineProps<{
  file: FileableFileItem;
  canDelete?: boolean;
}>();

const emit = defineEmits<{
  copy: [file: FileableFileItem];
  delete: [file: FileableFileItem];
}>();

const meta = computed(() => [
  props.file.fileable?.title ?? $t(fileTypeLabel(props.file.file_type)),
  props.file.file_date?.slice(0, 10),
  props.file.formatted_size,
].filter(Boolean).join(' · '));
</script>

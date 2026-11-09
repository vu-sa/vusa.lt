<template>
  <li :class="ROW_ITEM_CLASS" data-slot="fileable-file-row">
    <CollectionRow :actions :class="['min-w-0 flex-1', ROW_PADDING_CLASS]" @select="select">
      <template #lead>
        <FileText class="size-4 text-muted-foreground" aria-hidden="true" />
      </template>
      <!-- A plain link to a redirect: opening mints the reader's anonymous link on the way. -->
      <a
        :href="openHref"
        target="_blank"
        rel="noopener noreferrer"
        data-collection-open
        class="block truncate font-bold text-foreground hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
      >{{ file.name }}</a>
      <p class="mt-0.5 truncate text-xs text-muted-foreground">
        {{ meta }}
      </p>
    </CollectionRow>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, FileText, Link2, Link2Off, Trash2 } from '@lucide/vue';

import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import { ROW_ITEM_CLASS, ROW_PADDING_CLASS } from '@/Components/Collection/rowList';
import { fileTypeLabel } from '@/Constants/fileTypes';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';

import type { FileableFileItem } from './types';

const props = defineProps<{
  file: FileableFileItem;
  canDelete?: boolean;
}>();

const emit = defineEmits<{
  copy: [file: FileableFileItem];
  delete: [file: FileableFileItem];
  revoke: [file: FileableFileItem];
}>();

const openHref = computed(() => route('fileableFiles.open', props.file.id));

const actions = computed<AdminRecordAction[]>(() => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, href: openHref.value, external: true, role: 'open', primary: true },
  { key: 'copy', label: $t('Kopijuoti nuorodą'), icon: Link2 },
  ...(props.canDelete && props.file.public_link ? [{ key: 'revoke', label: $t('Atšaukti nuorodą'), icon: Link2Off }] : []),
  ...(props.canDelete ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true }] : []),
]);

const select = (key: string) => {
  if (key === 'copy') emit('copy', props.file);
  else if (key === 'revoke') emit('revoke', props.file);
  else if (key === 'delete') emit('delete', props.file);
};

const meta = computed(() => [
  props.file.fileable?.title ?? $t(fileTypeLabel(props.file.file_type)),
  props.file.file_date?.slice(0, 10),
  props.file.formatted_size,
].filter(Boolean).join(' · '));
</script>

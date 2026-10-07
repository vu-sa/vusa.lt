<template>
  <CollectionRowActions :actions @select="select" />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Folder, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

import type { DocumentFolderRow } from './types';

import CollectionRowActions, { type CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';

const props = defineProps<{
  file: DocumentFolderRow;
}>();

const emit = defineEmits<{
  delete: [file: DocumentFolderRow];
}>();

const removed = computed(() => Boolean(props.file.removed_from_sharepoint_at));

const actions = computed<CollectionRowAction[]>(() => [
  ...(props.file.public_url ? [{ key: 'public', label: $t('Atidaryti vusa.lt nuorodą'), icon: ExternalLink, href: props.file.public_url, external: true }] : []),
  ...(props.file.sharepoint_web_url && !removed.value ? [{ key: 'sharepoint', label: $t('Atidaryti SharePoint'), icon: Folder, href: props.file.sharepoint_web_url, external: true }] : []),
  ...(removed.value && props.file.can.update ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true, labelled: true }] : []),
]);

function select(key: string): void {
  if (key === 'delete') emit('delete', props.file);
}
</script>

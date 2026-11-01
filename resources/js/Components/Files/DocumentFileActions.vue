<template>
  <CollectionRowActions :actions @select="select" />
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { documentFileActions } from './documentFileActions';
import type { DocumentFolderRow } from './types';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';

const props = defineProps<{
  file: DocumentFolderRow;
}>();

const emit = defineEmits<{
  delete: [file: DocumentFolderRow];
}>();

const actions = computed(() => documentFileActions(props.file));

function select(key: string): void {
  if (key === 'delete') emit('delete', props.file);
}
</script>

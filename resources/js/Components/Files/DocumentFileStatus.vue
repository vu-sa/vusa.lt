<template>
  <CollectionStatusMenu
    :status="removed ? documentStatuses.removed : documentStatuses[file.status]"
    :model-value="file.status"
    :options="statusOptions"
    :editable="file.can.update && !removed"
    :pending="busy"
    @update:model-value="value => emit('status', file, value as DocumentStatus)"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { DocumentFolderRow } from './types';

import CollectionStatusMenu, { type CollectionStatusOption } from '@/Components/Collection/CollectionStatusMenu.vue';
import { documentStatuses } from '@/Constants/statuses';
import { DocumentStatus } from '@/Types/enums';

const props = defineProps<{
  file: DocumentFolderRow;
  /** A request for this file is in flight. */
  busy?: boolean;
}>();

const emit = defineEmits<{
  status: [file: DocumentFolderRow, status: DocumentStatus];
}>();

// "Laukia" is where discovery puts a file, not a choice; a pending file shows it with neither option ticked.
const statusOptions: CollectionStatusOption[] = [
  { value: DocumentStatus.Published, status: documentStatuses[DocumentStatus.Published] },
  { value: DocumentStatus.Hidden, status: documentStatuses[DocumentStatus.Hidden] },
];

const removed = computed(() => Boolean(props.file.removed_from_sharepoint_at));
</script>

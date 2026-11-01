<template>
  <StatusBadge :status="removed ? documentStatuses.removed : documentStatuses[file.status]" />
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { DocumentFolderRow } from './types';

import { StatusBadge } from '@/Components/Patterns';
import { documentStatuses } from '@/Constants/statuses';

const props = defineProps<{
  file: DocumentFolderRow;
}>();

const removed = computed(() => Boolean(props.file.removed_from_sharepoint_at));
</script>

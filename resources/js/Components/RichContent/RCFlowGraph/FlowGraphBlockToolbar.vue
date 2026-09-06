<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    :show-vertical-spacing="false"
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <FlowGraphEditor v-model="graph" />
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import FlowGraphEditor from '../Types/FlowGraphEditor.vue';
import type { ContentPart } from '../Types';

import type { FlowGraph } from '@/Types/contentParts';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const graph = computed<FlowGraph['json_content']>({
  get: () => props.content.json_content as FlowGraph['json_content'],
  set: json_content => emit('update:content', { ...props.content, json_content }),
});
</script>

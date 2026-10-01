<template>
  <div :class="['relative', blockLayoutClasses(group.element)]" :data-rc-block-key="keyFor(group.element)">
    <RCInsertAffordance v-if="!preview" :quick-add-types="getQuickAddTypes()" @insert="$emit('insert', $event, index)" @more="$emit('more', index)" />
    <RCFullscreenBlock :content="group.element" :resolved="resolved[keyFor(group.element)]" :band="bands.get(group.element)" :block-key="keyFor(group.element)" :preview :can-move-up="index > 0" :can-move-down="index < contents.length - 1" :can-delete="contents.length > 1" @update:content="contents[index] = $event" @move-up="$emit('move', index, index - 1)" @move-down="$emit('move', index, index + 1)" @delete="$emit('remove', index)" @open-form="$emit('form', group.element)">
      <RCFullscreenCanvasGroup v-for="child in group.kind === 'section' ? group.children : []" :key="keyFor(child)" v-model:contents="contents" :group="{ kind: 'block', element: child }" :resolved :bands :preview @insert="(type, position) => $emit('insert', type, position)" @more="$emit('more', $event)" @move="(from, to) => $emit('move', from, to)" @remove="$emit('remove', $event)" @form="$emit('form', $event)" />
    </RCFullscreenBlock>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { ContentPart } from '../../Types';
import type { ContentGroup } from '../../groupContent';
import type { BandResolution } from '../../bandLayout';
import { blockLayoutClasses } from '../../blockLayout';
import { getQuickAddTypes } from '../quickAddTypes';
import RCInsertAffordance from '../RCInsertAffordance.vue';
import RCFullscreenBlock from './RCFullscreenBlock.vue';
const props = defineProps<{ group: ContentGroup<ContentPart>; resolved: Record<string, unknown>; bands: Map<ContentPart, BandResolution>; preview: boolean }>();
const contents = defineModel<ContentPart[]>('contents', { required: true });
const keyFor = (part: ContentPart) => String(part.key ?? part.id ?? '');
const index = computed(() => contents.value.findIndex(part => keyFor(part) === keyFor(props.group.element)));
defineEmits<{ insert: [type: string, index: number]; more: [index: number]; move: [from: number, to: number]; remove: [index: number]; form: [part: ContentPart] }>();
</script>

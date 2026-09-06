<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')" @move-down="$emit('move-down')"
    @delete="$emit('delete')" @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>
      <CardOptionsFields v-model="options" />
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import CardOptionsFields from '../Types/CardOptionsFields.vue';

import type { ShadcnCard } from '@/Types/contentParts';
import { FieldLabel } from '@/Components/ui/field';

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

const contentType = computed(() => getContentType('shadcn-card'));
const cardOptions = computed(() => (props.content.options ?? {}) as ShadcnCard['options']);
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => cardOptions.value.width ?? contentType.value.defaultWidth);
const options = computed<ShadcnCard['options']>({
  get: () => cardOptions.value,
  set: value => emit('update:content', { ...props.content, options: value }),
});

onMounted(() => {
  if (!props.content.options) {
    emit('update:content', { ...props.content, options: (contentType.value.defaultOptions?.() ?? {}) as ShadcnCard['options'] });
  }
});

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}
</script>

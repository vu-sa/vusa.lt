<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <CalendarOptionsFields v-model="options" />

      <RCPresentationPicker
        :model-value="presentation"
        :plain-padding
        :disabled="presentationDisabled"
        @update:model-value="setPresentation"
        @update:plain-padding="setPlainPadding"
      />
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
/**
 * `calendar`'s whole-block toolbar — same shape as `LinkListBlockToolbar.vue`:
 * `RCBlockToolbarShell`'s generic chrome, the width/presentation pickers, and
 * `CalendarOptionsFields` (limit/category/tenantScope — everything the block fetches
 * with). Title/eyebrow are edited inline on the display itself, not here.
 */
import { computed, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import type { BlockPresentation } from '../bandLayout';
import type { PlainPadding } from '../sectionClasses';
import CalendarOptionsFields from '../Types/CalendarOptionsFields.vue';

import type { Calendar } from '@/Types/contentParts';
import { FieldLabel } from '@/Components/ui/field';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  presentationDisabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const calendarOptions = computed(() => (props.content.options ?? {}) as NonNullable<Calendar['options']>);
const presentation = computed<BlockPresentation | undefined>(() => calendarOptions.value.presentation);
const plainPadding = computed<PlainPadding | undefined>(() => calendarOptions.value.plainPadding);

const contentType = computed(() => getContentType('calendar'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => calendarOptions.value.width ?? contentType.value.defaultWidth);

const options = computed<Calendar['options']>({
  get: () => calendarOptions.value,
  set: value => emit('update:content', { ...props.content, options: value }),
});

// See LinkListBlockToolbar.vue's identical guard: CalendarOptionsFields mutates
// `options` in place, so it needs a real, persisted object rather than the `?? {}`
// read-only fallback above.
onMounted(() => {
  if (!props.content.options) {
    emit('update:content', { ...props.content, options: (contentType.value.defaultOptions?.() ?? {}) as Calendar['options'] });
  }
});

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function setPresentation(value: BlockPresentation): void {
  emit('update:content', { ...props.content, options: { ...calendarOptions.value, presentation: value } });
}

function setPlainPadding(value: PlainPadding): void {
  emit('update:content', { ...props.content, options: { ...calendarOptions.value, plainPadding: value } });
}
</script>

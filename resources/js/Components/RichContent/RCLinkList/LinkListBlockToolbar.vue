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

      <LinkListOptionsFields v-model="content" v-model:options="options" />

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
 * `link-list`'s whole-block toolbar: `RCBlockToolbarShell`'s generic chrome plus the
 * width/presentation pickers and — the field this type actually needs full-screen —
 * `LinkListOptionsFields` (source/mode/limit/pinned/category/tenant scope). The header
 * (title/subtitle/eyebrow) is already inline-editable via `RCSection`'s `editable` prop;
 * this popover is for the fetch configuration that has no on-canvas representation to
 * click into, same rationale as Hero's variant picker.
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
import LinkListOptionsFields from '../Types/LinkListOptionsFields.vue';

import type { LinkList } from '@/Types/contentParts';
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

const linkListOptions = computed(() => (props.content.options ?? {}) as LinkList['options']);
const presentation = computed<BlockPresentation | undefined>(() => linkListOptions.value.presentation);
const plainPadding = computed<PlainPadding | undefined>(() => linkListOptions.value.plainPadding);

const contentType = computed(() => getContentType('link-list'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (linkListOptions.value.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

// `LinkListOptionsFields` mutates the `options` object it's handed in place (same
// contract the regular side-form editor relies on) — it needs a real, persisted object
// to mutate, not the `?? {}` read-only fallback above. Legacy rows saved before
// `link-list` existed can have null options; self-heal once, on mount, the same way
// `CalendarEditor.vue` already does for its own options.
onMounted(() => {
  if (!props.content.options) {
    emit('update:content', { ...props.content, options: (contentType.value.defaultOptions?.() ?? {}) as LinkList['options'] });
  }
});

const content = computed<LinkList['json_content']>({
  get: () => props.content.json_content as LinkList['json_content'],
  set: value => emit('update:content', { ...props.content, json_content: value }),
});

const options = computed<LinkList['options']>({
  get: () => linkListOptions.value,
  set: value => emit('update:content', { ...props.content, options: value }),
});

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function setPresentation(value: BlockPresentation): void {
  emit('update:content', { ...props.content, options: { ...linkListOptions.value, presentation: value } });
}

function setPlainPadding(value: PlainPadding): void {
  emit('update:content', { ...props.content, options: { ...linkListOptions.value, plainPadding: value } });
}
</script>

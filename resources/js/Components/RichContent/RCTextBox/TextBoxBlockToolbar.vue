<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <!-- Open / Closed toggle -->
      <Field>
        <div class="flex items-center justify-between">
          <FieldLabel class="mb-0 text-xs">
            {{ $t('rich-content.text_box_closed_label') }}
          </FieldLabel>
          <Switch
            :model-value="options.isClosed === true"
            @update:model-value="updateOption('isClosed', $event)"
          />
        </div>
        <FieldDescription class="text-xs">
          {{ $t('rich-content.text_box_closed_description') }}
        </FieldDescription>
      </Field>

      <!-- Closed message when closed -->
      <Field v-if="options.isClosed">
        <FieldLabel class="text-xs">
          {{ $t('rich-content.text_box_closed_message_label') }}
        </FieldLabel>
        <Input
          :model-value="closedMessageText"
          type="text"
          :placeholder="$t('rich-content.text_box_closed_default')"
          @update:model-value="updateClosedMessage(String($event))"
        />
      </Field>

      <!-- Placeholder text -->
      <Field>
        <FieldLabel class="text-xs">
          {{ $t('rich-content.text_box_placeholder_label') }}
        </FieldLabel>
        <Input
          :model-value="placeholderText"
          type="text"
          :placeholder="$t('rich-content.enter_placeholder')"
          @update:model-value="updatePlaceholder(String($event))"
        />
      </Field>

      <!-- Width Picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <!-- Submissions dialog -->
      <div v-if="content?.id" class="border-t border-border pt-2">
        <TextBoxSubmissionsDialog :content-part-id="content.id" />
      </div>
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import TextBoxSubmissionsDialog from '../Types/TextBoxSubmissionsDialog.vue';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';

import { Field, FieldDescription, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import type { TextBox } from '@/Types/contentParts';

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

const page = usePage();
const locale = computed(() => page.props.app?.locale ?? 'lt');

const options = computed<NonNullable<TextBox['options']>>(
  () => (props.content.options ?? {}) as NonNullable<TextBox['options']>,
);

const placeholderText = computed(() => {
  const p = options.value.placeholder;
  if (!p) return '';
  if (typeof p === 'string') return p;
  return p[locale.value as 'lt' | 'en'] || p.lt || p.en || '';
});

const closedMessageText = computed(() => {
  const m = options.value.closedMessage;
  if (!m) return '';
  if (typeof m === 'string') return m;
  return m[locale.value as 'lt' | 'en'] || m.lt || m.en || '';
});

const contentType = computed(() => getContentType('text-box'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (options.value.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function updateOption<K extends keyof NonNullable<TextBox['options']>>(key: K, value: NonNullable<TextBox['options']>[K]): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...options.value,
      [key]: value,
    },
  });
}

function updatePlaceholder(val: string): void {
  const current = options.value.placeholder;
  const updated = typeof current === 'object' && current !== null
    ? { ...current, [locale.value]: val }
    : val;
  updateOption('placeholder', updated);
}

function updateClosedMessage(val: string): void {
  const current = options.value.closedMessage;
  const updated = typeof current === 'object' && current !== null
    ? { ...current, [locale.value]: val }
    : val;
  updateOption('closedMessage', updated);
}

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}
</script>

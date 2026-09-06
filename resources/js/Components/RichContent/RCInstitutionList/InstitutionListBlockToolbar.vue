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
    <div class="flex flex-col gap-3">
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <InstitutionListOptionsFields v-model="options" />

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
import { computed, onMounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import type { BlockPresentation } from '../bandLayout';
import type { PlainPadding } from '../sectionClasses';
import InstitutionListOptionsFields from '../Types/InstitutionListOptionsFields.vue';

import type { InstitutionList } from '@/Types/contentParts';
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

const institutionOptions = computed(() => (props.content.options ?? {}) as NonNullable<InstitutionList['options']>);
const presentation = computed<BlockPresentation | undefined>(() => institutionOptions.value.presentation);
const plainPadding = computed<PlainPadding | undefined>(() => institutionOptions.value.plainPadding);

const contentType = computed(() => getContentType('institution-list'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => institutionOptions.value.width ?? contentType.value.defaultWidth);

const options = computed<InstitutionList['options']>({
  get: () => institutionOptions.value,
  set: value => emit('update:content', { ...props.content, options: value }),
});

onMounted(() => {
  if (!props.content.options) {
    emit('update:content', { ...props.content, options: { tenantScope: 'all', typeSlug: 'pkp', limit: null } });
  }
});

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function setPresentation(value: BlockPresentation): void {
  emit('update:content', { ...props.content, options: { ...institutionOptions.value, presentation: value } });
}

function setPlainPadding(value: PlainPadding): void {
  emit('update:content', { ...props.content, options: { ...institutionOptions.value, plainPadding: value } });
}
</script>

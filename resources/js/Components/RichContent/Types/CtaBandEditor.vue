<template>
  <div class="flex flex-col gap-5">
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input :model-value="content.heading" type="text" :placeholder="$t('rich-content.enter_title')"
        @update:model-value="patch({ heading: String($event) })" />
    </Field>

    <Field>
      <FieldLabel>{{ $t('rich-content.cta_band_text') }}</FieldLabel>
      <Textarea :model-value="content.text" :rows="3" :placeholder="$t('rich-content.enter_cta_band_text')"
        @update:model-value="patch({ text: String($event) })" />
    </Field>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.button_text') }}</FieldLabel>
        <Input :model-value="content.button?.label" type="text" :placeholder="$t('rich-content.enter_button_text')"
          @update:model-value="patchButton({ label: String($event) })" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.button_link') }}</FieldLabel>
        <Input :model-value="content.button?.href" type="text" placeholder="https://..."
          @update:model-value="patchButton({ href: String($event) })" />
      </Field>
    </div>

    <Field>
      <div class="flex items-center justify-between">
        <FieldLabel class="mb-0">{{ $t('rich-content.section_bleed') }}</FieldLabel>
        <Switch :model-value="options?.bleed !== false"
          @update:model-value="options = { ...options, bleed: $event }" />
      </div>
      <FieldDescription>{{ $t('rich-content.section_bleed_help') }}</FieldDescription>
    </Field>

    <DynamicListInput
      :model-value="content.items ?? []"
      :create-item
      :empty-text="$t('rich-content.no_cta_band_items')"
      :add-first-text="$t('rich-content.add_first_cta_band_item')"
      :add-text="$t('rich-content.add_cta_band_item')"
      allow-empty
      @update:model-value="patch({ items: $event })">
      <template #item="{ item, update }">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-[12rem_1fr]">
          <Field>
            <FieldLabel>{{ $t('rich-content.cta_band_item_icon') }}</FieldLabel>
            <RCIconSelect :model-value="item.icon" @update:model-value="update({ ...item, icon: $event })" />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.cta_band_item_label') }}</FieldLabel>
            <Input :model-value="item.label" type="text"
              :placeholder="$t('rich-content.enter_cta_band_item_label')"
              @update:model-value="update({ ...item, label: String($event) })" />
          </Field>
        </div>
      </template>
    </DynamicListInput>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCIconSelect from '../RCIconSelect.vue';

import type { CtaBand } from '@/Types/contentParts';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldDescription, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';

const model = defineModel<CtaBand['json_content']>();
const options = defineModel<CtaBand['options']>('options');

const content = computed(() => model.value ?? {});

// The whole block is one object rather than a list, so every field writes a fresh object —
// mutating `model.value` in place would not trip the editor's dirty tracking.
function patch(fields: Partial<CtaBand['json_content']>): void {
  model.value = { ...content.value, ...fields };
}

function patchButton(fields: Partial<NonNullable<CtaBand['json_content']['button']>>): void {
  patch({ button: { label: '', href: '', ...content.value.button, ...fields } });
}

function createItem(): NonNullable<CtaBand['json_content']['items']>[number] {
  return { icon: '', label: '' };
}
</script>

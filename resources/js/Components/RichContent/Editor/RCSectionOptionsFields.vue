<template>
  <!-- The shared grid of section-chrome fields, rendered by both modes of RCSectionOptions
       (flat for the standalone `section` block, collapsible for every other block) so the
       authoring UI cannot drift between types. See RCSectionOptions.vue. -->
  <div class="flex flex-col gap-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
        <Input :model-value="options.title" type="text" :placeholder="$t('rich-content.enter_section_title')" @update:model-value="patchOptions({ title: $event })" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.subtitle') }}</FieldLabel>
        <Input :model-value="options.subtitle" type="text" :placeholder="$t('rich-content.enter_section_subtitle')" @update:model-value="patchOptions({ subtitle: $event })" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.section_eyebrow') }}</FieldLabel>
        <Input :model-value="options.eyebrow" type="text" :placeholder="$t('rich-content.enter_section_eyebrow')" @update:model-value="patchOptions({ eyebrow: $event })" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.section_heading_level') }}</FieldLabel>
        <Select :model-value="options.headingLevel" @update:model-value="patchOptions({ headingLevel: $event as SectionOptions['headingLevel'] })">
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="2">
              {{ $t('rich-content.heading_level_2') }}
            </SelectItem>
            <SelectItem :value="3">
              {{ $t('rich-content.heading_level_3') }}
            </SelectItem>
            <SelectItem :value="4">
              {{ $t('rich-content.heading_level_4') }}
            </SelectItem>
          </SelectContent>
        </Select>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.section_align') }}</FieldLabel>
        <Select :model-value="options.align" @update:model-value="patchOptions({ align: $event as SectionOptions['align'] })">
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="center">
              {{ $t('rich-content.section_align_center') }}
            </SelectItem>
            <SelectItem value="start">
              {{ $t('rich-content.section_align_start') }}
            </SelectItem>
          </SelectContent>
        </Select>
      </Field>
      <Field>
        <div class="flex items-center justify-between">
          <FieldLabel class="mb-0">
            {{ $t('rich-content.section_show_separator') }}
          </FieldLabel>
          <Switch :model-value="options.showSeparator" @update:model-value="patchOptions({ showSeparator: $event })" />
        </div>
      </Field>
    </div>

    <RCPresentationPicker
      :model-value="options.presentation" :plain-padding="options.plainPadding" :disabled="presentationDisabled"
      @update:model-value="patchOptions({ presentation: $event })"
      @update:plain-padding="patchOptions({ plainPadding: $event })"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import RCPresentationPicker from './RCPresentationPicker.vue';

import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import type { SectionOptions } from '@/Types/contentParts';

defineProps<{ presentationDisabled?: boolean }>();

const options = defineModel<SectionOptions>({ required: true });

function patchOptions(patch: Partial<SectionOptions>): void {
  options.value = { ...options.value, ...patch };
}
</script>

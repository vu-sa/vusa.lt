<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" :collapsible="false" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.section_inner_width') }}</FieldLabel>
        <Select :model-value="options.inner ?? 'full'" @update:model-value="options.inner = $event">
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="prose">{{ $t('rich-content.width_prose_short') }}</SelectItem>
            <SelectItem value="content">{{ $t('rich-content.width_content_short') }}</SelectItem>
            <SelectItem value="wide">{{ $t('rich-content.width_wide_short') }}</SelectItem>
            <SelectItem value="full">{{ $t('rich-content.width_full_short') }}</SelectItem>
          </SelectContent>
        </Select>
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.section_wraps') }}</FieldLabel>
        <Select :model-value="options.wraps ?? 'following'" @update:model-value="options.wraps = $event">
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="following">{{ $t('rich-content.section_wraps_following') }}</SelectItem>
            <SelectItem value="none">{{ $t('rich-content.section_wraps_none') }}</SelectItem>
          </SelectContent>
        </Select>
      </Field>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * `section` editor. This block has no `json_content` of its own — it's a marker that
 * `RichContentParser`'s `groupedContent` uses to wrap every following block up to the
 * next section marker (see RCSection/SectionDisplay.vue). Nothing here edits those
 * wrapped blocks directly; they stay ordinary, independently-editable entries in the
 * flat block list — only their *rendering* nests once the parser groups them.
 */
import { trans as $t } from 'laravel-vue-i18n';

import type { Section } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

defineModel<Section['json_content']>({ required: true });
const options = defineModel<Section['options']>('options', { required: true });
</script>

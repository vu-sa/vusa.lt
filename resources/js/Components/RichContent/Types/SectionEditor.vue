<template>
  <div class="w-full">
    <Accordion type="multiple" :default-value="['general', 'text']" class="w-full">
      <AccordionItem value="general">
        <AccordionTrigger>{{ $t('rich-content.section_general') }}</AccordionTrigger>
        <AccordionContent class="pb-5">
          <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <Field>
                <FieldLabel>{{ $t('rich-content.section_inner_width') }}</FieldLabel>
                <Select :model-value="options.inner ?? 'full'" @update:model-value="options.inner = $event">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="prose">
                      {{ $t('rich-content.width_prose_short') }}
                    </SelectItem>
                    <SelectItem value="content">
                      {{ $t('rich-content.width_content_short') }}
                    </SelectItem>
                    <SelectItem value="wide">
                      {{ $t('rich-content.width_wide_short') }}
                    </SelectItem>
                    <SelectItem value="full">
                      {{ $t('rich-content.width_full_short') }}
                    </SelectItem>
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
                    <SelectItem value="following">
                      {{ $t('rich-content.section_wraps_following') }}
                    </SelectItem>
                    <SelectItem value="none">
                      {{ $t('rich-content.section_wraps_none') }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </Field>
              <Field>
                <!-- One alignment for the whole header (eyebrow + title + subtitle together,
                     see SectionHeader.vue) — not a per-field control. -->
                <FieldLabel>{{ $t('rich-content.section_align') }}</FieldLabel>
                <Select v-model="options.align">
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
                  <Switch v-model="options.showSeparator" />
                </div>
              </Field>
            </div>

            <RCPresentationPicker v-model="options.presentation" v-model:plain-padding="options.plainPadding" />
          </div>
        </AccordionContent>
      </AccordionItem>

      <AccordionItem value="text">
        <AccordionTrigger>{{ $t('rich-content.section_text') }}</AccordionTrigger>
        <AccordionContent class="pb-5">
          <div class="flex flex-col gap-5">
            <Field>
              <FieldLabel>{{ $t('rich-content.section_eyebrow') }}</FieldLabel>
              <Input v-model="options.eyebrow" type="text" :placeholder="$t('rich-content.enter_section_eyebrow')" />
            </Field>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_auto]">
              <Field>
                <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
                <Input v-model="options.title" type="text" :placeholder="$t('rich-content.enter_section_title')" />
              </Field>
              <Field>
                <FieldLabel>{{ $t('rich-content.section_heading_level') }}</FieldLabel>
                <Select v-model="options.headingLevel">
                  <SelectTrigger class="sm:w-32">
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
            </div>
            <Field>
              <FieldLabel>{{ $t('rich-content.subtitle') }}</FieldLabel>
              <Input v-model="options.subtitle" type="text" :placeholder="$t('rich-content.enter_section_subtitle')" />
            </Field>
          </div>
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  </div>
</template>

<script setup lang="ts">
/**
 * `section` editor. This block has no `json_content` of its own — it's a marker that
 * `RichContentParser`'s `groupedContent` uses to wrap every following block up to the
 * next section marker (see RCSection/SectionDisplay.vue). Nothing here edits those
 * wrapped blocks directly; they stay ordinary, independently-editable entries in the
 * flat block list — only their *rendering* nests once the parser groups them.
 *
 * Unlike every other type using `RCSection.vue`'s chrome, this one skips the shared
 * `RCSectionOptions`/`RCSectionOptionsFields` fieldset entirely: for those types the
 * header is secondary chrome (collapsed by default), but here the header *is* the
 * block's whole content, so it gets the full "General/Text" accordion treatment other
 * primary-content editors use (see HeroForm.vue) instead of being buried.
 */
import { trans as $t } from 'laravel-vue-i18n';

import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';

import type { Section } from '@/Types/contentParts';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

defineModel<Section['json_content']>({ required: true });
const options = defineModel<Section['options']>('options', { required: true });
</script>

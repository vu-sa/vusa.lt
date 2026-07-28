<template>
  <!-- The shared grid of section-chrome fields, rendered by both modes of RCSectionOptions
       (flat for the standalone `section` block, collapsible for every other block) so the
       authoring UI cannot drift between types. See RCSectionOptions.vue. -->
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="options.title" type="text" :placeholder="$t('rich-content.enter_section_title')" />
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.subtitle') }}</FieldLabel>
      <Input v-model="options.subtitle" type="text" :placeholder="$t('rich-content.enter_section_subtitle')" />
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.section_heading_level') }}</FieldLabel>
      <Select v-model="options.headingLevel">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem :value="2">{{ $t('rich-content.heading_level_2') }}</SelectItem>
          <SelectItem :value="3">{{ $t('rich-content.heading_level_3') }}</SelectItem>
          <SelectItem :value="4">{{ $t('rich-content.heading_level_4') }}</SelectItem>
        </SelectContent>
      </Select>
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.section_align') }}</FieldLabel>
      <Select v-model="options.align">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="center">{{ $t('rich-content.section_align_center') }}</SelectItem>
          <SelectItem value="start">{{ $t('rich-content.section_align_start') }}</SelectItem>
        </SelectContent>
      </Select>
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.section_background') }}</FieldLabel>
      <Select v-model="options.background">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">{{ $t('rich-content.section_background_none') }}</SelectItem>
          <SelectItem value="muted">{{ $t('rich-content.section_background_muted') }}</SelectItem>
          <SelectItem value="contrast">{{ $t('rich-content.section_background_contrast') }}</SelectItem>
          <SelectItem value="gradient">{{ $t('rich-content.section_background_gradient') }}</SelectItem>
        </SelectContent>
      </Select>
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.section_padding') }}</FieldLabel>
      <Select v-model="options.padding">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">{{ $t('rich-content.section_padding_none') }}</SelectItem>
          <SelectItem value="sm">{{ $t('rich-content.small') }}</SelectItem>
          <SelectItem value="md">{{ $t('rich-content.medium') }}</SelectItem>
          <SelectItem value="lg">{{ $t('rich-content.large') }}</SelectItem>
        </SelectContent>
      </Select>
    </Field>
    <Field>
      <FieldLabel>{{ $t('rich-content.section_rounded') }}</FieldLabel>
      <Select v-model="options.rounded">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="none">{{ $t('rich-content.section_rounded_none') }}</SelectItem>
          <SelectItem value="sm">{{ $t('rich-content.small') }}</SelectItem>
          <SelectItem value="md">{{ $t('rich-content.medium') }}</SelectItem>
          <SelectItem value="lg">{{ $t('rich-content.large') }}</SelectItem>
        </SelectContent>
      </Select>
    </Field>
    <Field>
      <div class="flex items-center justify-between">
        <FieldLabel class="mb-0">{{ $t('rich-content.section_show_separator') }}</FieldLabel>
        <Switch v-model="options.showSeparator" />
      </div>
    </Field>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

import type { SectionOptions } from '@/Types/contentParts';

const options = defineModel<SectionOptions>({ required: true });
</script>

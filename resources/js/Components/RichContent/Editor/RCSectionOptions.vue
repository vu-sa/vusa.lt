<template>
  <Field>
    <FieldLabel>{{ $t('rich-content.section_options') }}</FieldLabel>
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
    </div>
  </Field>
</template>

<script setup lang="ts">
/**
 * Shared title/subtitle/background/padding fields for every content type that renders
 * through RCSection.vue — one implementation instead of six copy-pasted field blocks,
 * so the authoring UI can't drift between e.g. card-stack and photo-gallery.
 */
import { trans as $t } from 'laravel-vue-i18n';

import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

export interface SectionOptions {
  title?: string;
  subtitle?: string;
  background?: 'none' | 'muted' | 'contrast' | 'gradient';
  padding?: 'none' | 'sm' | 'md' | 'lg';
}

const options = defineModel<SectionOptions>({ required: true });
</script>

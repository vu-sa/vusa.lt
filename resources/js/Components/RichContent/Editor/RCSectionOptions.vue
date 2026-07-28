<template>
  <!-- Standalone `section` block (collapsible=false): title/subtitle IS the content,
       so keep every field flat and always visible rather than tucked behind a toggle. -->
  <Field v-if="!collapsible">
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
    </div>
  </Field>

  <!-- Every other section block: title/subtitle/background/padding/rounded are
       secondary chrome around the block's own content, so collapse them behind a
       trigger (closed by default) to keep the editor focused on the primary content.
       Wrapped in a bordered panel so the collapsed state is clearly discoverable — a
       bare text label here is easy to miss among the block's own editing controls. -->
  <Collapsible v-else v-model:open="open"
    class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50/60 dark:border-zinc-700/60 dark:bg-zinc-800/30">
    <CollapsibleTrigger as-child>
      <button type="button"
        class="flex w-full items-center gap-2 px-3 py-2.5 text-left transition-colors hover:bg-zinc-100/80 dark:hover:bg-zinc-800/50">
        <SlidersHorizontal class="size-4 shrink-0 text-muted-foreground" />
        <span class="flex-1 text-sm font-medium leading-none select-none">
          {{ $t('rich-content.section_options') }}
        </span>
        <ChevronDown
          class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
          :class="{ 'rotate-180': open }" />
      </button>
    </CollapsibleTrigger>
    <CollapsibleContent>
      <div
        class="grid grid-cols-1 gap-4 border-t border-zinc-200 p-3 sm:grid-cols-2 dark:border-zinc-700/60">
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
      </div>
    </CollapsibleContent>
  </Collapsible>
</template>

<script setup lang="ts">
/**
 * Shared title/subtitle/background/padding/rounded fields for every content type that
 * renders through RCSection.vue — one implementation instead of six copy-pasted field
 * blocks, so the authoring UI can't drift between e.g. card-stack and photo-gallery.
 *
 * Collapsed behind a trigger by default (`collapsible` prop): for most blocks these
 * are secondary looks, not content. The standalone `section` block opts out by passing
 * `:collapsible="false"` — there the title/subtitle *is* the block's content, so
 * hiding it behind a toggle would bury the one thing the author came to edit.
 */
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, SlidersHorizontal } from 'lucide-vue-next';

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import type { SectionBackground, SectionPadding, SectionRounded } from '../sectionClasses';

export interface SectionOptions {
  title?: string;
  subtitle?: string;
  background?: SectionBackground;
  padding?: SectionPadding;
  rounded?: SectionRounded;
}

// Default-true is intentional: every editor using this wants collapse-by-default,
// and only SectionEditor opts out via `:collapsible="false"`. Vue coerces an absent
// boolean prop to false, so the default must be explicit — hence the lint exception.
// eslint-disable-next-line vue/no-boolean-default
withDefaults(defineProps<{ collapsible?: boolean }>(), { collapsible: true });

const options = defineModel<SectionOptions>({ required: true });

// Closed by default — matches the "advanced settings" collapsibles in NewsForm/PageForm.
const open = ref(false);
</script>

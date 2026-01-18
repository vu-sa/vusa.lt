<template>
  <div class="flex flex-col gap-5">
    <!-- Variant & Color row -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <!-- Variant toggle -->
      <Field>
        <FieldLabel>{{ $t('rich-content.variant') }}</FieldLabel>
        <ToggleGroup v-model="options.variant" type="single" class="justify-start">
          <ToggleGroupItem value="outline" class="gap-1.5">
            <IFluentSquare24Regular class="h-4 w-4" />
            {{ $t('rich-content.variants.outline') }}
          </ToggleGroupItem>
          <ToggleGroupItem value="soft" class="gap-1.5">
            <IFluentSquare24Filled class="h-4 w-4" />
            {{ $t('rich-content.variants.soft') }}
          </ToggleGroupItem>
        </ToggleGroup>
      </Field>

      <!-- Color select -->
      <Field>
        <FieldLabel>{{ $t('rich-content.color') }}</FieldLabel>
        <Select v-model="options.color">
          <SelectTrigger>
            <SelectValue :placeholder="$t('rich-content.select_color')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="color in colorOptions" :key="color.value" :value="color.value">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full" :class="color.swatch" />
                {{ color.label }}
              </div>
            </SelectItem>
          </SelectContent>
        </Select>
      </Field>

      <!-- Checkboxes -->
      <div class="flex flex-col gap-2 pt-6">
        <label class="flex items-center gap-2 text-sm">
          <Checkbox v-model="options.isTitleColored" />
          <span class="text-zinc-700 dark:text-zinc-300">{{ $t('rich-content.color_title') }}</span>
        </label>
        <label class="flex items-center gap-2 text-sm">
          <Checkbox v-model="options.showIcon" />
          <span class="text-zinc-700 dark:text-zinc-300">{{ $t('rich-content.show_icon') }}</span>
          <InfoPopover>
            <p>{{ $t('rich-content.icon_description') }}</p>
            <ul class="mt-1 list-inside list-disc text-xs">
              <li>{{ $t('rich-content.colors.red') }}: {{ $t('rich-content.icon_exclamation') }}</li>
              <li>{{ $t('rich-content.colors.yellow') }}: {{ $t('rich-content.icon_question') }}</li>
              <li>{{ $t('rich-content.colors.gray') }}: {{ $t('rich-content.icon_info') }}</li>
            </ul>
          </InfoPopover>
        </label>
      </div>
    </div>

    <!-- Title -->
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <Input v-model="options.title" type="text" :placeholder="$t('rich-content.enter_title')" />
    </Field>

    <!-- Content -->
    <Field>
      <FieldLabel>{{ $t('rich-content.content') }}</FieldLabel>
      <ModernTipTap v-model="content" />
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { ShadcnCard } from '@/Types/contentParts';
import ModernTipTap from '@/Components/TipTap/ModernTipTap.vue';
import InfoPopover from '@/Components/Buttons/InfoPopover.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Checkbox } from '@/Components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { useColorOptions } from '../composables/useColorOptions';
import IFluentSquare24Regular from '~icons/fluent/square24-regular';
import IFluentSquare24Filled from '~icons/fluent/square24-filled';

const content = defineModel<ShadcnCard['json_content']>();
const options = defineModel<ShadcnCard['options']>('options');

const { colorOptions } = useColorOptions();
</script>

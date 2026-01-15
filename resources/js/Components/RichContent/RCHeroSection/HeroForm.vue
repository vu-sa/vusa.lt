<template>
  <div class="flex flex-col gap-5">
    <!-- Title -->
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <OriginalTipTap v-model="json_content.title" html type="text" />
    </Field>

    <!-- Subtitle -->
    <Field>
      <FieldLabel>{{ $t('rich-content.subtitle') }}</FieldLabel>
      <OriginalTipTap v-model="json_content.subtitle" html type="text" />
    </Field>

    <!-- Background Image -->
    <Field>
      <FieldLabel>{{ $t('rich-content.background_image') }}</FieldLabel>
      <TiptapImageButton 
        v-if="!json_content.backgroundMedia" 
        size="medium"
        @submit="json_content.backgroundMedia = $event">
        {{ $t('rich-content.select_image') }}
      </TiptapImageButton>
      <div v-else class="flex items-center gap-3">
        <img :src="json_content.backgroundMedia" class="aspect-video h-20 rounded-lg object-cover">
        <Button variant="destructive" size="sm" @click="json_content.backgroundMedia = null">
          {{ $t('rich-content.delete_image') }}
        </Button>
      </div>
    </Field>

    <!-- Background blur toggle -->
    <div class="flex items-center gap-3">
      <Switch 
        v-model="options.backgroundBlur"
      />
      <span class="text-sm text-zinc-700 dark:text-zinc-300">
        {{ $t('rich-content.blur_background') }}
      </span>
    </div>

    <!-- Right side image -->
    <Field>
      <FieldLabel>{{ $t('rich-content.right_image') }}</FieldLabel>
      <TiptapImageButton 
        v-if="!json_content.rightMedia" 
        size="medium" 
        @submit="json_content.rightMedia = $event">
        {{ $t('rich-content.select_image') }}
      </TiptapImageButton>
      <div v-else class="flex items-center gap-3">
        <img :src="json_content.rightMedia" class="aspect-video h-20 rounded-lg object-cover">
        <Button variant="destructive" size="sm" @click="json_content.rightMedia = null">
          {{ $t('rich-content.delete_image') }}
        </Button>
      </div>
    </Field>

    <!-- Button text & link -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.button_text') }}</FieldLabel>
        <Input v-model="json_content.buttonText" type="text" :placeholder="$t('rich-content.enter_button_text')" />
      </Field>
      <Field>
        <FieldLabel>{{ $t('rich-content.button_link') }}</FieldLabel>
        <Input v-model="json_content.buttonLink" type="text" placeholder="https://..." />
      </Field>
    </div>

    <!-- Button color -->
    <Field>
      <FieldLabel>{{ $t('rich-content.button_color') }}</FieldLabel>
      <ToggleGroup v-model="options.buttonColor" type="single" class="justify-start">
        <ToggleGroupItem v-for="color in buttonColorOptions" :key="color.value" :value="color.value" class="gap-1.5">
          <div class="h-3 w-3 rounded-full" :class="color.swatch" />
          {{ color.label }}
        </ToggleGroupItem>
      </ToggleGroup>
    </Field>
  </div>
</template>

<script setup lang="ts">
import OriginalTipTap from '@/Components/TipTap/OriginalTipTap.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import type { Hero } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { useColorOptions } from '../composables/useColorOptions';

const options = defineModel<Hero['options']>('options', { required: true });
const json_content = defineModel<Hero['json_content']>({ required: true });

const { buttonColorOptions } = useColorOptions();
</script>

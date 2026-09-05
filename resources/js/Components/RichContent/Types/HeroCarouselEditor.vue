<template>
  <div class="flex flex-col gap-5">
    <!-- Carousel Options -->
    <Field>
      <FieldLabel>{{ $t('rich-content.hero_carousel_options') }}</FieldLabel>
      <div class="space-y-3">
        <!-- `asBoolean` on every Switch: legacy rows saved through FormData
             (EditHomePage) carry "1"/"0" strings, which reka-ui's strict boolean
             model renders as always-unchecked. -->
        <div class="flex items-center gap-3">
          <Switch :model-value="asBoolean(options.autoplay)" @update:model-value="patchOption('autoplay', $event)" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.enable_autoplay') }}
          </span>
        </div>
        <div v-if="asBoolean(options.autoplay)" class="flex items-center gap-3">
          <FieldLabel class="min-w-fit">
            {{ $t('rich-content.autoplay_delay') }}
          </FieldLabel>
          <Input
            :model-value="options.autoplayDelay ?? 8000"
            type="number"
            min="2000"
            max="30000"
            step="1000"
            class="w-24"
            @update:model-value="patchOption('autoplayDelay', Number($event))"
          />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $t('rich-content.milliseconds') }}</span>
        </div>
        <div class="flex items-center gap-3">
          <Switch :model-value="asBoolean(options.showArrows ?? true)" @update:model-value="patchOption('showArrows', $event)" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.show_arrows') }}
          </span>
        </div>
        <div class="flex items-center gap-3">
          <Switch :model-value="asBoolean(options.showIndicators ?? true)" @update:model-value="patchOption('showIndicators', $event)" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.show_indicators') }}
          </span>
        </div>
        <Field>
          <FieldLabel>{{ $t('rich-content.scrim_strength') }}</FieldLabel>
          <Select :model-value="options.scrim ?? 'medium'" @update:model-value="patchOption('scrim', $event as NonNullable<HeroCarousel['options']>['scrim'])">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="light">
                {{ $t('rich-content.scrim_light') }}
              </SelectItem>
              <SelectItem value="medium">
                {{ $t('rich-content.scrim_medium') }}
              </SelectItem>
              <SelectItem value="dark">
                {{ $t('rich-content.scrim_dark') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.carousel_height') }}</FieldLabel>
          <Select :model-value="options.height ?? 'md'" @update:model-value="patchOption('height', $event as NonNullable<HeroCarousel['options']>['height'])">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="sm">
                {{ $t('rich-content.small') }}
              </SelectItem>
              <SelectItem value="md">
                {{ $t('rich-content.medium') }}
              </SelectItem>
              <SelectItem value="lg">
                {{ $t('rich-content.large') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
      </div>
    </Field>

    <!-- Slides -->
    <Field>
      <FieldLabel>{{ $t('rich-content.slides') }}</FieldLabel>
      <DynamicListInput
        v-model="json_content"
        :create-item="createSlide"
        :empty-text="$t('rich-content.no_slides')"
        :add-first-text="$t('rich-content.add_first_slide')"
        :add-text="$t('rich-content.add_slide')"
        compact>
        <template #item="{ item, update }">
          <div class="flex flex-col gap-3">
            <Field>
              <FieldLabel>{{ $t('rich-content.eyebrow') }}</FieldLabel>
              <Input
                :model-value="item.eyebrow"
                type="text"
                :placeholder="$t('rich-content.enter_eyebrow')"
                @update:model-value="update({ ...item, eyebrow: $event })"
              />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
              <Input
                :model-value="item.title"
                type="text"
                :placeholder="$t('rich-content.enter_title')"
                @update:model-value="update({ ...item, title: $event })"
              />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.subtitle') }}</FieldLabel>
              <Input
                :model-value="item.subtitle"
                type="text"
                :placeholder="$t('rich-content.enter_subtitle')"
                @update:model-value="update({ ...item, subtitle: $event })"
              />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.description') }}</FieldLabel>
              <TiptapEditor
                :model-value="item.description"
                preset="full"
                prose-style
                @update:model-value="update({ ...item, description: $event })"
              />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.slide_image') }}</FieldLabel>
              <TiptapImageButton
                v-if="!item.imageSrc"
                @submit:object="(img) => update({ ...item, imageSrc: img.src, imageAlt: img.alt })">
                {{ $t('rich-content.select_image') }}
              </TiptapImageButton>
              <div v-else class="flex items-center gap-3">
                <img :src="item.imageSrc" :alt="item.imageAlt" class="aspect-video h-20 rounded-lg object-cover">
                <TiptapImageButton
                  size="sm"
                  @submit:object="(img) => update({ ...item, imageSrc: img.src, imageAlt: img.alt })"
                >
                  {{ $t('rich-content.select_image') }}
                </TiptapImageButton>
                <Button variant="outline" size="sm" @click="activeFocalSlide = item">
                  {{ $t('rich-content.set_focal_point') }}
                </Button>
                <Button variant="destructive" size="sm" @click="update({ ...item, imageSrc: '' })">
                  {{ $t('rich-content.delete_image') }}
                </Button>
              </div>
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.image_alt_text') }}</FieldLabel>
              <Input
                :model-value="item.imageAlt"
                type="text"
                :placeholder="$t('rich-content.enter_image_alt_text')"
                @update:model-value="update({ ...item, imageAlt: $event })"
              />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.hero_carousel_text_position') }}</FieldLabel>
              <Select :model-value="item.align ?? 'start'" @update:model-value="update({ ...item, align: $event })">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="start">
                    {{ $t('rich-content.hero_carousel_position_start') }}
                  </SelectItem>
                  <SelectItem value="center">
                    {{ $t('rich-content.hero_carousel_position_center') }}
                  </SelectItem>
                  <SelectItem value="end">
                    {{ $t('rich-content.hero_carousel_position_end') }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </Field>

            <!-- Buttons — the same per-button customization as the hero block. -->
            <Field>
              <FieldLabel>{{ $t('rich-content.buttons') }}</FieldLabel>
              <DynamicListInput
                :model-value="item.buttons ?? []"
                :create-item="createButton"
                :empty-text="$t('rich-content.no_buttons')"
                :add-first-text="$t('rich-content.add_first_button')"
                :add-text="$t('rich-content.add_button')"
                @update:model-value="update({ ...item, buttons: $event })">
                <template #item="{ item: buttonItem, update: updateButton }">
                  <div class="flex flex-col gap-3">
                    <Field>
                      <FieldLabel>{{ $t('rich-content.button_text') }}</FieldLabel>
                      <Input
                        :model-value="buttonItem.text"
                        type="text"
                        :placeholder="$t('rich-content.enter_button_text')"
                        @update:model-value="updateButton({ ...buttonItem, text: $event })"
                      />
                    </Field>
                    <Field>
                      <FieldLabel>{{ $t('rich-content.button_link') }}</FieldLabel>
                      <Input
                        :model-value="buttonItem.link"
                        type="text"
                        placeholder="https://..."
                        @update:model-value="updateButton({ ...buttonItem, link: $event })"
                      />
                    </Field>
                    <div class="grid grid-cols-2 gap-4">
                      <Field>
                        <FieldLabel>{{ $t('rich-content.button_variant') }}</FieldLabel>
                        <Select :model-value="buttonItem.variant || 'default'" @update:model-value="updateButton({ ...buttonItem, variant: $event })">
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="default">
                              {{ $t('rich-content.default') }}
                            </SelectItem>
                            <SelectItem value="outline">
                              {{ $t('rich-content.outline') }}
                            </SelectItem>
                          </SelectContent>
                        </Select>
                      </Field>
                      <Field>
                        <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
                        <RCIconSelect :model-value="buttonItem.icon" allow-none @update:model-value="updateButton({ ...buttonItem, icon: $event })" />
                      </Field>
                    </div>
                  </div>
                </template>
              </DynamicListInput>
            </Field>
          </div>
        </template>
      </DynamicListInput>
    </Field>

    <!-- Focal point picker — one shared dialog, opened for whichever slide's image
         requested it (HeroForm uses a per-field dialog; slides are a dynamic list,
         so the dialog needs to know which item to write back to). -->
    <Dialog v-model:open="focalDialogOpen">
      <DialogContent class="max-w-xl">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.image_focus_point') }}</DialogTitle>
        </DialogHeader>
        <FocalPointPicker
          v-if="activeFocalSlide?.imageSrc"
          :image-url="activeFocalSlide.imageSrc"
          :model-value="activeFocalSlide.objectPosition ?? null"
          @update:model-value="(val: string) => updateFocalPoint(val)"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

import RCIconSelect from '../RCIconSelect.vue';
import { asBoolean } from '../booleanish';

import type { HeroCarousel } from '@/Types/contentParts';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';

const options = defineModel<HeroCarousel['options']>('options', {
  default: () => ({ autoplay: true, autoplayDelay: 8000, showArrows: true, showIndicators: true, scrim: 'medium', height: 'md' }),
});
const json_content = defineModel<HeroCarousel['json_content']>({ default: () => [] });

function patchOption<K extends keyof NonNullable<HeroCarousel['options']>>(
  key: K,
  value: NonNullable<HeroCarousel['options']>[K],
): void {
  const current = options.value ?? ({} as NonNullable<HeroCarousel['options']>);
  (current as Record<string, unknown>)[key as string] = value;
  options.value = {
    ...current,
    [key]: value,
  };
}

// The slide whose image the focal-point dialog is editing, identified by object
// identity — the dialog must write back through the same `update()` contract the
// list template uses, never by mutating the model array in place.
const activeFocalSlide = ref<HeroCarousel['json_content'][number] | null>(null);
const focalDialogOpen = computed({
  get: () => activeFocalSlide.value !== null,
  set: (open: boolean) => {
    if (!open) activeFocalSlide.value = null;
  },
});

function updateFocalPoint(val: string) {
  if (!activeFocalSlide.value) return;
  const index = json_content.value.indexOf(activeFocalSlide.value);
  if (index === -1) return;
  const updatedSlide = { ...activeFocalSlide.value, objectPosition: val };
  const newContent = [...json_content.value];
  newContent[index] = updatedSlide;
  json_content.value = newContent;
  activeFocalSlide.value = updatedSlide;
}

function createSlide(): HeroCarousel['json_content'][number] {
  return {
    eyebrow: '',
    title: '',
    subtitle: '',
    description: { type: 'doc', content: [] },
    imageSrc: '',
    imageAlt: '',
    align: 'start',
    buttons: [],
  };
}

function createButton(): NonNullable<HeroCarousel['json_content'][number]['buttons']>[number] {
  return {
    text: '',
    link: '',
    variant: 'default' as const,
  };
}
</script>

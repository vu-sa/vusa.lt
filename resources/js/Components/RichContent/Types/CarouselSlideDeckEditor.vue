<template>
  <div class="flex flex-col gap-5">
    <!-- Carousel Options -->
    <Field>
      <FieldLabel>{{ $t('rich-content.carousel_options') }}</FieldLabel>
      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <Switch v-model="options.autoplay" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.enable_autoplay') }}
          </span>
        </div>
        <div v-if="options.autoplay" class="flex items-center gap-3">
          <FieldLabel class="min-w-fit">{{ $t('rich-content.autoplay_delay') }}</FieldLabel>
          <Input
            v-model.number="options.autoplayDelay"
            type="number"
            min="2000"
            max="30000"
            step="1000"
            class="w-24"
          />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $t('rich-content.milliseconds') }}</span>
        </div>
        <div class="flex items-center gap-3">
          <Switch v-model="options.showNavigation" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.show_navigation') }}
          </span>
        </div>
        <div class="flex items-center gap-3">
          <Switch v-model="options.showThumbnails" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.show_thumbnails') }}
          </span>
        </div>
      </div>
    </Field>

    <RCSectionOptions v-model="options" />

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
              <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
              <RCIconSelect :model-value="item.icon" @update:model-value="update({ ...item, icon: $event })" />
            </Field>

            <Field>
              <FieldLabel>{{ $t('rich-content.badge') }}</FieldLabel>
              <Input
                :model-value="item.badge"
                type="text"
                :placeholder="$t('rich-content.enter_badge')"
                @update:model-value="update({ ...item, badge: $event })"
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
              <FieldLabel>{{ $t('rich-content.description') }}</FieldLabel>
              <TiptapEditor
                :model-value="item.description"
                preset="full"
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
                <img :src="item.imageSrc" class="aspect-video h-20 rounded-lg object-cover">
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

            <div class="flex items-center gap-3">
              <Switch
                :model-value="item.imageLeft"
                @update:model-value="update({ ...item, imageLeft: $event })"
              />
              <span class="text-sm text-zinc-700 dark:text-zinc-300">
                {{ $t('rich-content.image_on_left') }}
              </span>
            </div>

            <!-- Image Decorations -->
            <Field>
              <FieldLabel>{{ $t('rich-content.image_decorations') }}</FieldLabel>
              <DynamicListInput
                :model-value="item.decorations ?? []"
                :create-item="createDecoration"
                :empty-text="$t('rich-content.no_decorations')"
                :add-first-text="$t('rich-content.add_first_decoration')"
                :add-text="$t('rich-content.add_decoration')"
                @update:model-value="update({ ...item, decorations: $event })">
                <template #item="{ item: decorationItem, update: updateDecoration }">
                  <div class="flex flex-col gap-3">
                    <div class="grid grid-cols-2 gap-4">
                      <Field>
                        <FieldLabel>{{ $t('rich-content.decoration_type') }}</FieldLabel>
                        <Select :model-value="decorationItem.type" @update:model-value="updateDecoration({ ...decorationItem, type: $event })">
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="line">{{ $t('rich-content.line') }}</SelectItem>
                            <SelectItem value="circle">{{ $t('rich-content.circle') }}</SelectItem>
                            <SelectItem value="square">{{ $t('rich-content.square') }}</SelectItem>
                          </SelectContent>
                        </Select>
                      </Field>
                      <Field>
                        <FieldLabel>{{ $t('rich-content.decoration_position') }}</FieldLabel>
                        <Select :model-value="decorationItem.position" @update:model-value="updateDecoration({ ...decorationItem, position: $event })">
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="top-left">{{ $t('rich-content.top_left') }}</SelectItem>
                            <SelectItem value="top-right">{{ $t('rich-content.top_right') }}</SelectItem>
                            <SelectItem value="bottom-left">{{ $t('rich-content.bottom_left') }}</SelectItem>
                            <SelectItem value="bottom-right">{{ $t('rich-content.bottom_right') }}</SelectItem>
                          </SelectContent>
                        </Select>
                      </Field>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                      <Field>
                        <FieldLabel>{{ $t('rich-content.decoration_size') }}</FieldLabel>
                        <Select :model-value="decorationItem.size" @update:model-value="updateDecoration({ ...decorationItem, size: $event })">
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="sm">{{ $t('rich-content.small') }}</SelectItem>
                            <SelectItem value="md">{{ $t('rich-content.medium') }}</SelectItem>
                            <SelectItem value="lg">{{ $t('rich-content.large') }}</SelectItem>
                          </SelectContent>
                        </Select>
                      </Field>
                      <Field>
                        <FieldLabel>{{ $t('rich-content.decoration_color') }}</FieldLabel>
                        <Select :model-value="decorationItem.color || 'vusa-red'" @update:model-value="updateDecoration({ ...decorationItem, color: $event })">
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="vusa-red">{{ $t('rich-content.vusa_red') }}</SelectItem>
                            <SelectItem value="vusa-yellow">{{ $t('rich-content.vusa_yellow') }}</SelectItem>
                            <SelectItem value="zinc">{{ $t('rich-content.zinc') }}</SelectItem>
                          </SelectContent>
                        </Select>
                      </Field>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                      <Field>
                        <FieldLabel>{{ $t('rich-content.decoration_opacity') }}</FieldLabel>
                        <Input
                          :model-value="decorationItem.opacity"
                          type="number"
                          min="0"
                          max="100"
                          @update:model-value="updateDecoration({ ...decorationItem, opacity: Number($event) })"
                        />
                      </Field>
                      <div class="flex items-center gap-3 pt-6">
                        <Switch
                          :model-value="decorationItem.rotation"
                          @update:model-value="updateDecoration({ ...decorationItem, rotation: $event })"
                        />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                          {{ $t('rich-content.enable_rotation') }}
                        </span>
                      </div>
                    </div>
                  </div>
                </template>
              </DynamicListInput>
            </Field>
          </div>
        </template>
      </DynamicListInput>
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { CarouselSlideDeck } from '@/Types/contentParts';
import RCIconSelect from '../RCIconSelect.vue';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';

const options = defineModel<CarouselSlideDeck['options']>('options', {
  default: () => ({ autoplay: true, autoplayDelay: 8000, showNavigation: true, showThumbnails: true }),
});
const json_content = defineModel<CarouselSlideDeck['json_content']>({ default: () => [] });

function createSlide(): CarouselSlideDeck['json_content'][number] {
  return {
    icon: 'info',
    badge: '',
    title: '',
    description: '',
    imageSrc: '',
    imageAlt: '',
    imageLeft: false,
    decorations: [],
  };
}

function createDecoration() {
  return {
    type: 'line' as const,
    position: 'top-right' as const,
    size: 'md' as const,
    color: 'vusa-red' as const,
    opacity: 60,
    rotation: false,
  };
}
</script>
<template>
  <div class="flex flex-col gap-5">
    <!-- Variant — a visual picker (like PageForm's layout picker) so the shape of
         each option is obvious without having to try it first. -->
    <Field>
      <FieldLabel>{{ $t('rich-content.hero_variant') }}</FieldLabel>
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <button v-for="variantOption in variantOptions" :key="variantOption.value" type="button"
          class="group relative overflow-visible rounded-lg border-2 p-3 text-left transition-all duration-200"
          :class="[
            (options.variant ?? 'split') === variantOption.value
              ? 'border-vusa-red bg-red-50/50 ring-2 ring-vusa-red/20 dark:bg-red-950/20'
              : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'
          ]" @click="options.variant = variantOption.value">
          <div class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-vusa-red text-white shadow-md transition-all"
            :class="(options.variant ?? 'split') === variantOption.value ? 'scale-100 opacity-100' : 'scale-75 opacity-0'">
            <IFluentCheckmark12Regular class="h-2.5 w-2.5" />
          </div>
          <div class="mb-2 flex justify-center transition-opacity"
            :class="(options.variant ?? 'split') === variantOption.value ? 'opacity-100' : 'opacity-50 group-hover:opacity-75'">
            <component :is="variantOption.icon" class="h-12 w-20" />
          </div>
          <div class="text-center">
            <span class="text-xs font-medium">{{ variantOption.label }}</span>
          </div>
        </button>
      </div>
    </Field>

    <!-- Eyebrow / Description — banner has no room for either (it renders only the
         title + one button), so they're hidden rather than left as dead options. -->
    <template v-if="options.variant !== 'banner'">
      <Field>
        <FieldLabel>{{ $t('rich-content.eyebrow') }}</FieldLabel>
        <Input v-model="json_content.eyebrow" type="text" :placeholder="$t('rich-content.enter_eyebrow')" />
      </Field>
    </template>

    <!-- Title -->
    <Field>
      <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
      <TiptapEditor v-model="json_content.title" preset="compact" :html="true" />
    </Field>

    <!-- Description -->
    <Field v-if="options.variant !== 'banner'">
      <FieldLabel>{{ $t('rich-content.description') }}</FieldLabel>
      <Input v-model="json_content.description" type="text" :placeholder="$t('rich-content.enter_description')" />
    </Field>

    <!-- Main Image — hidden for centered/banner, which render no image. -->
    <template v-if="showImageFields">
      <Field>
        <FieldLabel>{{ $t('rich-content.main_image') }}</FieldLabel>
        <TiptapImageButton
          v-if="!json_content.imageSrc"
          @submit:object="(img) => { json_content.imageSrc = img.src; json_content.imageAlt = img.alt; }">
          {{ $t('rich-content.select_image') }}
        </TiptapImageButton>
        <div v-else class="flex items-center gap-3">
          <img :src="json_content.imageSrc" class="aspect-video h-20 rounded-lg object-cover">
          <Button variant="outline" size="sm" @click="showFocalPoint = true">
            {{ $t('rich-content.set_focal_point') }}
          </Button>
          <Button variant="destructive" size="sm" @click="json_content.imageSrc = ''">
            {{ $t('rich-content.delete_image') }}
          </Button>
        </div>
      </Field>

      <!-- Image Alt Text -->
      <Field>
        <FieldLabel>{{ $t('rich-content.image_alt_text') }}</FieldLabel>
        <Input v-model="json_content.imageAlt" type="text" :placeholder="$t('rich-content.enter_image_alt_text')" />
      </Field>

      <!-- Text Position — only meaningful for the two-column split layout. -->
      <div v-if="options.variant === 'split' || !options.variant" class="flex items-center gap-3">
        <Switch v-model="options.textLeft" />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">
          {{ $t('rich-content.text_on_left') }}
        </span>
      </div>

      <!-- Object Position — set via the same click/drag picker used for user photos,
           instead of hand-typing a "40% 65%" string. -->
      <Dialog v-model:open="showFocalPoint">
        <DialogContent class="max-w-xl">
          <DialogHeader>
            <DialogTitle>{{ $t('rich-content.image_focus_point') }}</DialogTitle>
          </DialogHeader>
          <FocalPointPicker
            v-if="json_content.imageSrc"
            :image-url="json_content.imageSrc"
            :model-value="json_content.objectPosition ?? null"
            @update:model-value="(val: string) => (json_content.objectPosition = val)"
          />
        </DialogContent>
      </Dialog>
    </template>

    <!-- Buttons Section -->
    <Field>
      <FieldLabel>{{ $t('rich-content.buttons') }}</FieldLabel>
      <p v-if="options.variant === 'banner'" class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ $t('rich-content.hero_banner_buttons_hint') }}
      </p>
      <DynamicListInput
        v-model="json_content.buttons"
        :create-item="createButton"
        :empty-text="$t('rich-content.no_buttons')"
        :add-first-text="$t('rich-content.add_first_button')"
        :add-text="$t('rich-content.add_button')">
        <template #item="{ item, update }">
          <div class="flex flex-col gap-3">
            <Field>
              <FieldLabel>{{ $t('rich-content.button_text') }}</FieldLabel>
              <Input
                :model-value="item.text"
                type="text"
                :placeholder="$t('rich-content.enter_button_text')"
                @update:model-value="update({ ...item, text: $event })"
              />
            </Field>
            <Field>
              <FieldLabel>{{ $t('rich-content.button_link') }}</FieldLabel>
              <Input
                :model-value="item.link"
                type="text"
                placeholder="https://..."
                @update:model-value="update({ ...item, link: $event })"
              />
            </Field>
            <div class="grid grid-cols-3 gap-4">
              <Field>
                <FieldLabel>{{ $t('rich-content.button_variant') }}</FieldLabel>
                <Select :model-value="item.variant || 'default'" @update:model-value="update({ ...item, variant: $event })">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="default">{{ $t('rich-content.default') }}</SelectItem>
                    <SelectItem value="outline">{{ $t('rich-content.outline') }}</SelectItem>
                  </SelectContent>
                </Select>
              </Field>
              <Field>
                <FieldLabel>{{ $t('rich-content.button_color') }}</FieldLabel>
                <Select :model-value="item.color || 'red'" @update:model-value="update({ ...item, color: $event })">
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="red">{{ $t('rich-content.red') }}</SelectItem>
                    <SelectItem value="yellow">{{ $t('rich-content.yellow') }}</SelectItem>
                    <SelectItem value="zinc">{{ $t('rich-content.zinc') }}</SelectItem>
                    <SelectItem value="white">{{ $t('rich-content.white') }}</SelectItem>
                  </SelectContent>
                </Select>
              </Field>
              <Field>
                <FieldLabel>{{ $t('rich-content.icon') }}</FieldLabel>
                <RCIconSelect :model-value="item.icon" allow-none @update:model-value="update({ ...item, icon: $event })" />
              </Field>
            </div>
          </div>
        </template>
      </DynamicListInput>
    </Field>

    <!-- Overlay Content — only the split layout's ImageWithDecorations renders this. -->
    <Field v-if="options.variant === 'split' || !options.variant">
      <FieldLabel>{{ $t('rich-content.overlay_content') }}</FieldLabel>
      <div class="space-y-3">
        <Field>
          <FieldLabel>{{ $t('rich-content.overlay_title') }}</FieldLabel>
          <Input v-model="json_content.overlayContent.title" type="text" :placeholder="$t('rich-content.enter_overlay_title')" />
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.overlay_subtitle') }}</FieldLabel>
          <Input v-model="json_content.overlayContent.subtitle" type="text" :placeholder="$t('rich-content.enter_overlay_subtitle')" />
        </Field>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <Field>
            <FieldLabel>{{ $t('rich-content.overlay_corner') }}</FieldLabel>
            <Select :model-value="json_content.overlayCorner ?? 'bottom-left'" @update:model-value="json_content.overlayCorner = $event">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="top-left">{{ $t('rich-content.overlay_corner_top_left') }}</SelectItem>
                <SelectItem value="top-right">{{ $t('rich-content.overlay_corner_top_right') }}</SelectItem>
                <SelectItem value="bottom-left">{{ $t('rich-content.overlay_corner_bottom_left') }}</SelectItem>
                <SelectItem value="bottom-right">{{ $t('rich-content.overlay_corner_bottom_right') }}</SelectItem>
              </SelectContent>
            </Select>
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.overlay_padding') }}</FieldLabel>
            <Select :model-value="json_content.overlayPadding ?? 'md'" @update:model-value="json_content.overlayPadding = $event">
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
          <div class="flex items-center gap-3 pt-6">
            <Switch v-model="json_content.overlayOverhang" />
            <span class="text-sm text-zinc-700 dark:text-zinc-300">
              {{ $t('rich-content.overlay_overhang') }}
            </span>
          </div>
        </div>
      </div>
    </Field>

    <!-- Image Decorations — split-only; panel's thumbnail is a plain image. -->
    <RCDecorationListEditor v-if="options.variant === 'split' || !options.variant" v-model="options.imageDecorations" />

    <!-- Background appearance — split/centered/banner only; panel keeps its own fixed
         gradient-panel chrome (no room for an alternate background there). -->
    <Field v-if="options.variant !== 'panel'">
      <FieldLabel>{{ $t('rich-content.section_options') }}</FieldLabel>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Field>
          <FieldLabel>{{ $t('rich-content.section_background') }}</FieldLabel>
          <Select :model-value="options.background ?? 'muted'" @update:model-value="options.background = $event">
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
          <Select :model-value="options.padding ?? ''" @update:model-value="options.padding = $event">
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
          <Select :model-value="options.rounded ?? 'none'" @update:model-value="options.rounded = $event">
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
  </div>
</template>

<script setup lang="ts">
import { computed, h, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { Hero } from '@/Types/contentParts';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import RCDecorationListEditor from '../Editor/RCDecorationListEditor.vue';
import RCIconSelect from '../RCIconSelect.vue';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';

const options = defineModel<Hero['options']>('options', { required: true });
const json_content = defineModel<Hero['json_content']>({ required: true });

const showFocalPoint = ref(false);

// The split variant reveals the Overlay Content inputs, whose v-models reach into
// `overlayContent.title`/`.subtitle`. A hero created or seeded without that key
// (e.g. the demo seeder's panel heroes, later switched to split) would crash on the
// first render of those inputs, so ensure the object exists whenever split is active.
watch(
  () => options.value?.variant ?? 'split',
  (variant) => {
    if ((variant === 'split' || !variant) && !json_content.value.overlayContent) {
      json_content.value.overlayContent = { title: '', subtitle: '' };
    }
  },
  { immediate: true },
);

// centered/banner render no image at all; split and panel both need one (split's
// two-column layout, panel's square thumbnail) even though panel skips the
// decorations/overlay/text-position fields below (its image is a plain <img>).
const showImageFields = computed(() => {
  const variant = options.value?.variant ?? 'split';
  return variant === 'split' || variant === 'panel';
});

// Variant skeleton icons — simple SVG schematics, same pattern as PageForm's layout
// picker, so the shape of each option is obvious before trying it.
const SplitVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 6, y: 12, width: 34, height: 8, rx: 2 }),
  h('rect', { x: 6, y: 26, width: 34, height: 14, rx: 2 }),
  h('rect', { x: 6, y: 46, width: 16, height: 8, rx: 2 }),
  h('rect', { x: 50, y: 8, width: 40, height: 48, rx: 3 }),
]);

const CenteredVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 28, y: 12, width: 40, height: 8, rx: 2 }),
  h('rect', { x: 20, y: 26, width: 56, height: 10, rx: 2 }),
  h('rect', { x: 38, y: 42, width: 20, height: 8, rx: 2 }),
]);

const BannerVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 26, width: 60, height: 12, rx: 2 }),
  h('rect', { x: 72, y: 26, width: 20, height: 12, rx: 2 }),
]);

const PanelVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 2, y: 2, width: 92, height: 60, rx: 8 }),
  h('rect', { x: 10, y: 16, width: 32, height: 32, rx: 6 }),
  h('rect', { x: 50, y: 18, width: 36, height: 8, rx: 2 }),
  h('rect', { x: 50, y: 30, width: 36, height: 14, rx: 2 }),
]);

const variantOptions: { value: NonNullable<Hero['options']['variant']>; label: string; icon: unknown }[] = [
  { value: 'split', label: $t('rich-content.hero_variant_split'), icon: SplitVariantIcon },
  { value: 'centered', label: $t('rich-content.hero_variant_centered'), icon: CenteredVariantIcon },
  { value: 'banner', label: $t('rich-content.hero_variant_banner'), icon: BannerVariantIcon },
  { value: 'panel', label: $t('rich-content.hero_variant_panel'), icon: PanelVariantIcon },
];

function createButton() {
  return {
    text: '',
    link: '',
    variant: 'default' as const,
    color: 'red' as const,
  };
}

</script>

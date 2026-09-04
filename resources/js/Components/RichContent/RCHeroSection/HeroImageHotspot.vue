<template>
  <div
    ref="rootRef"
    :class="[
      'group relative',
      hasImage && 'grid grid-cols-[minmax(0,1fr)_1.5rem] items-start gap-2',
      !hasImage && 'aspect-[16/10] min-h-[240px]',
    ]"
  >
    <RCAddPlaceholder
      v-if="!hasImage"
      :label="$t('rich-content.add_image')"
      class="left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"
      @click="hotspots.openPopover(imageHotspotId)"
    />
    <img v-else-if="variant === 'panel'"
      :src="jsonContent.imageSrc"
      :alt="jsonContent.imageAlt"
      :style="jsonContent.objectPosition ? { objectPosition: jsonContent.objectPosition } : undefined"
      class="hidden aspect-square w-32 shrink-0 border border-border object-cover sm:block lg:w-40"
      loading="lazy"
    >
    <ImageWithDecorations v-else
      :src="jsonContent.imageSrc"
      :alt="jsonContent.imageAlt"
      height-class="aspect-[16/10] h-auto"
      :decorations="content.options?.imageDecorations"
      :overlay-content="jsonContent.overlayContent"
      :overlay-corner="jsonContent.overlayCorner"
      :overlay-overhang="jsonContent.overlayOverhang"
      :overlay-padding="jsonContent.overlayPadding"
      :object-position="jsonContent.objectPosition"
      force-overlay-content
      loading="eager"
    >
      <template #overlay-content>
        <div class="mb-1 flex items-center space-x-1 sm:mb-2 sm:space-x-2 md:space-x-3">
          <div class="size-2 rounded-full bg-vusa-yellow sm:size-2.5 md:size-3" />
          <RCInlineText
            :model-value="jsonContent.overlayContent?.title ?? ''"
            :editable="true"
            :placeholder="$t('rich-content.overlay_title')"
            class="text-xs font-medium text-zinc-600 dark:text-zinc-400 sm:text-sm"
            @update:model-value="patchOverlayContent({ title: $event })"
          />
        </div>
        <RCInlineText
          as="p"
          :model-value="jsonContent.overlayContent?.subtitle ?? ''"
          :editable="true"
          :placeholder="$t('rich-content.overlay_subtitle')"
          class="text-xs text-zinc-500 sm:text-sm"
          @update:model-value="patchOverlayContent({ subtitle: $event })"
        />
      </template>
    </ImageWithDecorations>
    <div v-if="hasImage" data-testid="hero-image-spotlight-rail" class="flex flex-col items-center gap-3 pt-3">
      <button
        ref="imageSpotlightRef"
        type="button"
        class="size-3 shrink-0 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)] animate-pulse transition-transform hover:scale-125 focus-visible:scale-125 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2"
        data-rc-interactive
        :aria-label="$t('rich-content.edit_image')"
        :title="$t('rich-content.edit_image')"
        @click="hotspots.openPopover(imageHotspotId)"
      >
        <span class="sr-only">{{ $t('rich-content.edit_image') }}</span>
      </button>

      <Popover v-if="variant === 'split'" :open="hotspots.isPopoverOpen(overlayHotspotId)" @update:open="onOverlayOpenChange">
        <PopoverAnchor :reference="overlaySpotlightRef ?? undefined" />
        <button
          ref="overlaySpotlightRef"
          type="button"
          class="size-3 shrink-0 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)] animate-pulse transition-transform hover:scale-125 focus-visible:scale-125 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2"
          data-rc-interactive
          :aria-label="$t('rich-content.overlay_content')"
          :title="$t('rich-content.overlay_content')"
          @click="hotspots.openPopover(overlayHotspotId)"
        >
          <span class="sr-only">{{ $t('rich-content.overlay_content') }}</span>
        </button>
        <PopoverContent
          v-if="hotspots.isPopoverOpen(overlayHotspotId)"
          data-surface="public"
          class="max-h-[calc(100dvh-2rem)] w-[min(32rem,calc(100vw-2rem))] overflow-y-auto"
          @close-auto-focus.prevent
        >
          <Field>
            <FieldLabel>{{ $t('rich-content.overlay_content') }}</FieldLabel>
            <div class="space-y-3">
              <Field>
                <FieldLabel>{{ $t('rich-content.overlay_title') }}</FieldLabel>
                <Input
                  :model-value="jsonContent.overlayContent?.title ?? ''"
                  type="text"
                  :placeholder="$t('rich-content.enter_overlay_title')"
                  @update:model-value="patchOverlayContent({ title: $event as string })"
                />
              </Field>
              <Field>
                <FieldLabel>{{ $t('rich-content.overlay_subtitle') }}</FieldLabel>
                <Input
                  :model-value="jsonContent.overlayContent?.subtitle ?? ''"
                  type="text"
                  :placeholder="$t('rich-content.enter_overlay_subtitle')"
                  @update:model-value="patchOverlayContent({ subtitle: $event as string })"
                />
              </Field>
              <div class="grid grid-cols-1 gap-3">
                <Field>
                  <FieldLabel>{{ $t('rich-content.overlay_corner') }}</FieldLabel>
                  <Select :model-value="jsonContent.overlayCorner ?? 'bottom-left'" @update:model-value="patchJson({ overlayCorner: $event as Hero['json_content']['overlayCorner'] })">
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="top-left">
                        {{ $t('rich-content.overlay_corner_top_left') }}
                      </SelectItem>
                      <SelectItem value="top-right">
                        {{ $t('rich-content.overlay_corner_top_right') }}
                      </SelectItem>
                      <SelectItem value="bottom-left">
                        {{ $t('rich-content.overlay_corner_bottom_left') }}
                      </SelectItem>
                      <SelectItem value="bottom-right">
                        {{ $t('rich-content.overlay_corner_bottom_right') }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <Field>
                  <FieldLabel>{{ $t('rich-content.overlay_padding') }}</FieldLabel>
                  <Select :model-value="jsonContent.overlayPadding ?? 'md'" @update:model-value="patchJson({ overlayPadding: $event as Hero['json_content']['overlayPadding'] })">
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
                <div class="flex items-center gap-3">
                  <Switch :model-value="!!jsonContent.overlayOverhang" @update:model-value="patchJson({ overlayOverhang: $event as boolean })" />
                  <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ $t('rich-content.overlay_overhang') }}
                  </span>
                </div>
              </div>
            </div>
          </Field>
        </PopoverContent>
      </Popover>

      <Popover v-if="variant === 'split'" :open="hotspots.isPopoverOpen(decorationsHotspotId)" @update:open="onDecorationsOpenChange">
        <PopoverAnchor :reference="decorationsSpotlightRef ?? undefined" />
        <button
          ref="decorationsSpotlightRef"
          type="button"
          class="size-3 shrink-0 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)] animate-pulse transition-transform hover:scale-125 focus-visible:scale-125 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2"
          data-rc-interactive
          :aria-label="$t('rich-content.image_decorations')"
          :title="$t('rich-content.image_decorations')"
          @click="hotspots.openPopover(decorationsHotspotId)"
        >
          <span class="sr-only">{{ $t('rich-content.image_decorations') }}</span>
        </button>
        <PopoverContent
          v-if="hotspots.isPopoverOpen(decorationsHotspotId)"
          data-surface="public"
          class="max-h-[calc(100dvh-2rem)] w-[min(32rem,calc(100vw-2rem))] overflow-y-auto"
          @close-auto-focus.prevent
        >
          <RCDecorationListEditor v-model="decorations" />
        </PopoverContent>
      </Popover>
    </div>
  </div>

  <Popover :open="hotspots.isPopoverOpen(imageHotspotId)" @update:open="onImageOpenChange">
    <PopoverAnchor :reference="imageSpotlightRef ?? rootRef ?? undefined" />
    <PopoverContent
      v-if="hotspots.isPopoverOpen(imageHotspotId)"
      data-surface="public"
      class="max-h-[calc(100dvh-2rem)] w-[min(32rem,calc(100vw-2rem))] overflow-y-auto"
      @close-auto-focus.prevent
    >
      <div class="flex flex-col gap-5">
        <Field>
          <FieldLabel>{{ $t('rich-content.main_image') }}</FieldLabel>
          <TiptapImageButton v-if="!hasImage" @submit:object="onImageSubmit">
            {{ $t('rich-content.select_image') }}
          </TiptapImageButton>
          <div v-else class="flex items-center gap-3">
            <img :src="jsonContent.imageSrc" alt="" class="aspect-video h-16 rounded-lg object-cover">
            <TiptapImageButton size="sm" @submit:object="onImageSubmit">
              {{ $t('rich-content.select_image') }}
            </TiptapImageButton>
            <Button variant="destructive" size="sm" @click="patchJson({ imageSrc: '' })">
              {{ $t('rich-content.delete_image') }}
            </Button>
          </div>
        </Field>

        <template v-if="hasImage">
          <Field class="border-t border-border pt-4">
            <FieldLabel>{{ $t('rich-content.image_alt_text') }}</FieldLabel>
            <Input
              :model-value="jsonContent.imageAlt"
              type="text"
              :placeholder="$t('rich-content.enter_image_alt_text')"
              @update:model-value="patchJson({ imageAlt: $event as string })"
            />
          </Field>

          <div class="border-t border-border pt-4">
            <FocalPointPicker
              :image-url="jsonContent.imageSrc"
              :model-value="jsonContent.objectPosition ?? null"
              @update:model-value="(val: string) => patchJson({ objectPosition: val })"
            />
          </div>
        </template>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
/**
 * The hero's main image: a `RCAddPlaceholder` when `imageSrc` is empty, or the real
 * rendered image (`ImageWithDecorations` for `split`, a plain thumbnail `<img>` for
 * `panel`) — one image hotspot and, for split heroes, overlay-content and decoration
 * hotspots. Existing images reserve a narrow side rail for all three triggers so none
 * of the controls obscures the image or decorations rendered over it.
 */
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCAddPlaceholder from '../Editor/Fullscreen/RCAddPlaceholder.vue';
import RCDecorationListEditor from '../Editor/RCDecorationListEditor.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';

import type { DecorationConfig, Hero } from '@/Types/contentParts';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

const props = defineProps<{
  content: Hero;
  blockKey: string;
}>();

const emit = defineEmits<(e: 'update:content', value: Hero) => void>();

const hotspots = injectActiveHotspot();
const imageHotspotId = computed(() => `${props.blockKey}:image`);
const overlayHotspotId = computed(() => `${props.blockKey}:overlay`);
const decorationsHotspotId = computed(() => `${props.blockKey}:decorations`);
const rootRef = ref<HTMLElement | null>(null);
const imageSpotlightRef = ref<HTMLElement | null>(null);
const overlaySpotlightRef = ref<HTMLElement | null>(null);
const decorationsSpotlightRef = ref<HTMLElement | null>(null);

const jsonContent = computed(() => props.content.json_content);
const variant = computed(() => props.content.options?.variant ?? 'split');
const hasImage = computed(() => !!jsonContent.value.imageSrc);

const decorations = computed<DecorationConfig[]>({
  get: () => props.content.options?.imageDecorations ?? [],
  set: value => emit('update:content', { ...props.content, options: { ...(props.content.options ?? {}), imageDecorations: value } }),
});

function patchJson(patch: Partial<Hero['json_content']>): void {
  emit('update:content', { ...props.content, json_content: { ...jsonContent.value, ...patch } });
}

function patchOverlayContent(patch: Partial<{ title: string; subtitle: string }>): void {
  patchJson({ overlayContent: { ...(jsonContent.value.overlayContent ?? { title: '', subtitle: '' }), ...patch } });
}

function onImageSubmit(img: { src: string; alt: string }): void {
  patchJson({ imageSrc: img.src, imageAlt: img.alt });
}

function onImageOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(imageHotspotId.value);
  else hotspots.close(imageHotspotId.value);
}

function onOverlayOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(overlayHotspotId.value);
  else hotspots.close(overlayHotspotId.value);
}

function onDecorationsOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(decorationsHotspotId.value);
  else hotspots.close(decorationsHotspotId.value);
}

// The split variant reveals the Overlay Content inputs, whose v-models reach into
// `overlayContent.title`/`.subtitle`. A hero created or seeded without that key (e.g.
// the demo seeder's panel heroes, later switched to split) would crash on the first
// render of those inputs, so ensure the object exists whenever split is active
// (ported verbatim from HeroForm.vue's matching watch).
watch(
  variant,
  (v) => {
    if ((v === 'split' || !v) && !jsonContent.value.overlayContent) {
      patchJson({ overlayContent: { title: '', subtitle: '' } });
    }
  },
  { immediate: true },
);
</script>

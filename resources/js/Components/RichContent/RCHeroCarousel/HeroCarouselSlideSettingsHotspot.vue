<template>
  <div class="relative">
    <Popover :open="hotspots.isPopoverOpen(hotspotId)" @update:open="onOpenChange">
      <PopoverAnchor :reference="spotlightRef ?? undefined" />
      <button
        ref="spotlightRef"
        type="button"
        :class="[
          'size-3 shrink-0 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)]',
          'animate-pulse transition-transform hover:scale-125 focus-visible:scale-125',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2',
        ]"
        data-rc-interactive
        :aria-label="$t('rich-content.slide_settings')"
        :title="$t('rich-content.slide_settings')"
        @click="hotspots.openPopover(hotspotId)"
      >
        <span class="sr-only">{{ $t('rich-content.slide_settings') }}</span>
      </button>

      <PopoverContent
        v-if="hotspots.isPopoverOpen(hotspotId)"
        data-surface="public"
        class="max-h-[calc(100dvh-2rem)] w-[min(24rem,calc(100vw-2rem))] overflow-y-auto"
        @close-auto-focus.prevent
      >
        <div class="flex flex-col gap-5">
          <Field>
            <FieldLabel>{{ $t('rich-content.hero_carousel_text_position') }}</FieldLabel>
            <Select :model-value="slide.align ?? 'start'" @update:model-value="patchSlide({ align: $event as 'start' | 'center' })">
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
              </SelectContent>
            </Select>
          </Field>

          <Field v-if="slide.imageSrc">
            <FieldLabel>{{ $t('rich-content.slide_scrim_strength') }}</FieldLabel>
            <Select :model-value="slide.scrim ?? 'default'" @update:model-value="onScrimChange">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="default">
                  {{ $t('rich-content.scrim_default') }}
                </SelectItem>
                <SelectItem value="light">
                  {{ $t('rich-content.scrim_light') }}
                </SelectItem>
                <SelectItem value="medium">
                  {{ $t('rich-content.scrim_medium') }}
                </SelectItem>
                <SelectItem value="strong">
                  {{ $t('rich-content.scrim_strong') }}
                </SelectItem>
                <SelectItem value="dark">
                  {{ $t('rich-content.scrim_dark') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </Field>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
/**
 * The second of two per-slide hotspots in `HeroCarouselSlideView.vue`'s slide-actions
 * row (the other, `HeroCarouselImageHotspot.vue`, owns the image/alt/focal point).
 * Split out rather than folded into the image hotspot's popover so each stays a single
 * short scroll — text position and darkness have nothing to do with picking a photo.
 */
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';

import { Field, FieldLabel } from '@/Components/ui/field';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import type { HeroCarousel } from '@/Types/contentParts';

type Slide = HeroCarousel['json_content'][number];

const props = defineProps<{
  slide: Slide;
  slideIndex: number;
  blockKey: string;
}>();

const emit = defineEmits<(e: 'update:slide', value: Slide) => void>();

const hotspots = injectActiveHotspot();
const hotspotId = `${props.blockKey}:slide-${props.slideIndex}:settings`;
const spotlightRef = ref<HTMLElement | null>(null);

function patchSlide(patch: Partial<Slide>): void {
  emit('update:slide', { ...props.slide, ...patch });
}

function onScrimChange(value: unknown): void {
  patchSlide({ scrim: value === 'default' ? undefined : (value as Slide['scrim']) });
}

function onOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(hotspotId);
  else hotspots.close(hotspotId);
}
</script>

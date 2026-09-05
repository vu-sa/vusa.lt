<template>
  <div class="flex flex-col gap-5">
    <!-- Gallery Options — segmented buttons instead of Selects so the editor grid
         visibly reflows as columns/gap change, matching what the public page will do. -->
    <Field>
      <FieldLabel>{{ $t('rich-content.gallery_options') }}</FieldLabel>
      <div class="space-y-3">
        <div class="flex items-center gap-3">
          <span class="w-24 shrink-0 text-sm text-zinc-600 dark:text-zinc-400">{{ $t('rich-content.columns') }}</span>
          <div class="inline-flex rounded-md border border-zinc-200 p-0.5 dark:border-zinc-700">
            <button v-for="col in (['2', '3', '4'] as const)" :key="col" type="button"
              class="rounded px-3 py-1 text-sm transition-colors"
              :class="options.columns === col ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
              @click="options.columns = col">
              {{ col }}
            </button>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="w-24 shrink-0 text-sm text-zinc-600 dark:text-zinc-400">{{ $t('rich-content.gap_size') }}</span>
          <div class="inline-flex rounded-md border border-zinc-200 p-0.5 dark:border-zinc-700">
            <button v-for="gap in (['small', 'medium', 'large'] as const)" :key="gap" type="button"
              class="rounded px-3 py-1 text-sm capitalize transition-colors"
              :class="options.gap === gap ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
              @click="options.gap = gap">
              {{ $t(`rich-content.${gap}`) }}
            </button>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Switch v-model="options.showLightbox" />
          <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $t('rich-content.enable_lightbox') }}
          </span>
        </div>
      </div>
    </Field>

    <RCSectionOptions v-model="options" />

    <!-- Images -->
    <Field>
      <FieldLabel>{{ $t('rich-content.images') }}</FieldLabel>
      <RCImageTileGrid
        v-model="json_content"
        src-key="src"
        :columns="Number(options.columns ?? '4')"
        :create-item="createImage"
      >
        <template #tile-menu="{ index }">
          <DropdownMenuItem @click="openTileSettings(index)">
            <IFluentSettings24Regular class="mr-2 h-4 w-4" />
            {{ $t('rich-content.height_class') }} / {{ $t('rich-content.image_decorations') }}
          </DropdownMenuItem>
        </template>
      </RCImageTileGrid>
    </Field>

    <!-- Per-image height + decorations — kept in a dialog rather than the hover menu
         (decorations are a small form, not a one-click toggle). -->
    <Dialog v-model:open="showTileSettings">
      <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.height_class') }} / {{ $t('rich-content.image_decorations') }}</DialogTitle>
        </DialogHeader>
        <div v-if="activeImage" class="flex flex-col gap-4">
          <Field>
            <FieldLabel>{{ $t('rich-content.height_class') }}</FieldLabel>
            <Select :model-value="activeImage.heightClass || 'h-52'" @update:model-value="updateActive({ heightClass: $event as string })">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="h-32">{{ $t('rich-content.small') }} (h-32)</SelectItem>
                <SelectItem value="h-40">{{ $t('rich-content.medium_small') }} (h-40)</SelectItem>
                <SelectItem value="h-52">{{ $t('rich-content.medium') }} (h-52)</SelectItem>
                <SelectItem value="h-64">{{ $t('rich-content.large') }} (h-64)</SelectItem>
              </SelectContent>
            </Select>
          </Field>

          <Field>
            <FieldLabel>{{ $t('rich-content.image_decorations') }}</FieldLabel>
            <DynamicListInput
              :model-value="activeImage.decorations"
              :create-item="createDecoration"
              :empty-text="$t('rich-content.no_decorations')"
              :add-first-text="$t('rich-content.add_first_decoration')"
              :add-text="$t('rich-content.add_decoration')"
              compact
              @update:model-value="updateActive({ decorations: $event })">
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
                </div>
              </template>
            </DynamicListInput>
          </Field>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCImageTileGrid from '../Editor/RCImageTileGrid.vue';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';

import type { PhotoGalleryGrid } from '@/Types/contentParts';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import IFluentSettings24Regular from '~icons/fluent/settings24-regular';

const options = defineModel<PhotoGalleryGrid['options']>('options', {
  default: () => ({ columns: '4', gap: 'medium', showLightbox: true }),
});
const json_content = defineModel<PhotoGalleryGrid['json_content']>({ default: () => [] });

function createImage(): PhotoGalleryGrid['json_content'][number] {
  return {
    src: '',
    alt: '',
    heightClass: 'h-52',
    decorations: [],
  };
}

function createDecoration() {
  return {
    type: 'line' as const,
    position: 'top-right' as const,
    size: 'md' as const,
  };
}

// Per-image settings dialog, opened from RCImageTileGrid's hover menu.
const showTileSettings = ref(false);
const tileSettingsIndex = ref<number | null>(null);
const activeImage = computed(() => (tileSettingsIndex.value !== null ? json_content.value?.[tileSettingsIndex.value] : null));

function openTileSettings(index: number) {
  tileSettingsIndex.value = index;
  showTileSettings.value = true;
}

function updateActive(patch: Partial<PhotoGalleryGrid['json_content'][number]>) {
  if (tileSettingsIndex.value === null || !json_content.value) return;
  const next = [...json_content.value];
  const current = next[tileSettingsIndex.value];
  if (!current) return;
  next[tileSettingsIndex.value] = { ...current, ...patch };
  json_content.value = next;
}
</script>

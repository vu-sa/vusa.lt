<template>
  <div class="flex flex-col gap-5">
    <div v-if="showTileSettings && isMobile" class="flex flex-col gap-4">
      <Button variant="ghost" size="sm" class="self-start" @click="showTileSettings = false">
        <ArrowLeft class="size-4" />
        {{ $t('rich-content.back_to_images') }}
      </Button>
      <h3 class="text-sm font-semibold text-foreground">
        {{ $t('rich-content.image_settings') }}
      </h3>
      <PhotoGalleryTileSettingsFields v-if="activeImage" :image="activeImage" @update:patch="updateActive" />
    </div>
    <template v-else>
      <!-- Gallery Options — segmented buttons instead of Selects so the editor grid
         visibly reflows as columns/gap change, matching what the public page will do. -->
      <Field>
        <FieldLabel>{{ $t('rich-content.gallery_options') }}</FieldLabel>
        <div class="space-y-3">
          <div class="space-y-2">
            <span class="text-sm text-foreground">{{ $t('rich-content.columns') }}</span>
            <FormSegmentedControl v-model="columnsChoice" :options="columnOptions" :aria-label="$t('rich-content.columns')" />
          </div>
          <div class="space-y-2">
            <span class="text-sm text-foreground">{{ $t('rich-content.gap_size') }}</span>
            <FormSegmentedControl v-model="gapChoice" :options="gapOptions" :aria-label="$t('rich-content.gap_size')" />
          </div>
          <div class="flex min-h-11 items-center gap-3">
            <Switch :id="lightboxId" v-model="options.showLightbox" />
            <label :for="lightboxId" class="text-sm text-foreground">
              {{ $t('rich-content.enable_lightbox') }}
            </label>
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
              <Settings2 class="mr-2 size-4" />
              {{ $t('rich-content.height_class') }} / {{ $t('rich-content.image_decorations') }}
            </DropdownMenuItem>
          </template>
        </RCImageTileGrid>
      </Field>
    </template>
    <Dialog v-if="!isMobile" v-model:open="showTileSettings">
      <DialogContent class="max-h-[85vh] max-w-lg overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.height_class') }} / {{ $t('rich-content.image_decorations') }}</DialogTitle>
        </DialogHeader>
        <PhotoGalleryTileSettingsFields v-if="activeImage" :image="activeImage" @update:patch="updateActive" />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, Settings2 } from 'lucide-vue-next';

import RCImageTileGrid from '../Editor/RCImageTileGrid.vue';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';

import PhotoGalleryTileSettingsFields from './PhotoGalleryTileSettingsFields.vue';

import type { PhotoGalleryGrid } from '@/Types/contentParts';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import { Field, FieldLabel } from '@/Components/ui/field';
import FormSegmentedControl from '@/Components/Patterns/FormSegmentedControl.vue';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { useIsMobile } from '@/Composables/useIsMobile';

const options = defineModel<PhotoGalleryGrid['options']>('options', {
  default: () => ({ columns: '4', gap: 'medium', showLightbox: true }),
});
const json_content = defineModel<PhotoGalleryGrid['json_content']>({ default: () => [] });
const isMobile = useIsMobile();
const lightboxId = useId();

const columnOptions = computed(() => (['2', '3', '4'] as const).map(value => ({ value, label: value })));
const gapOptions = computed(() => (['small', 'medium', 'large'] as const).map(value => ({ value, label: $t(`rich-content.${value}`) })));
const columnsChoice = computed({
  get: () => options.value?.columns ?? '4',
  set: (value: '2' | '3' | '4') => { options.value!.columns = value; },
});
const gapChoice = computed({
  get: () => options.value?.gap ?? 'medium',
  set: (value: 'small' | 'medium' | 'large') => { options.value!.gap = value; },
});

function createImage(): PhotoGalleryGrid['json_content'][number] {
  return {
    src: '',
    alt: '',
    heightClass: 'h-52',
    decorations: [],
  };
}

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

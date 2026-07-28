<template>
  <RCImageTileGrid
    v-model="modelValue"
    src-key="image"
    :columns="6"
    :tile-class="tileHeightClass"
    :span-class="tileSpanClass"
    :create-item="createItem"
  >
    <template #tile-menu="{ item, update }">
      <DropdownMenuLabel class="text-xs font-normal text-zinc-400">
        {{ $t('rich-content.image_width') }}
      </DropdownMenuLabel>
      <DropdownMenuItem v-for="opt in imageGridOptions" :key="opt.value" @click="update({ colspan: opt.value })">
        <IFluentCheckmark12Regular class="mr-2 h-3.5 w-3.5" :class="item.colspan === opt.value ? 'opacity-100' : 'opacity-0'" />
        {{ opt.label }}
      </DropdownMenuItem>
      <DropdownMenuSeparator />
    </template>
  </RCImageTileGrid>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import RCImageTileGrid from '../Editor/RCImageTileGrid.vue';

import type { ImageGrid } from '@/Types/contentParts';
import { DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/Components/ui/dropdown-menu';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';

type GridImage = ImageGrid['json_content'][number];

const modelValue = defineModel<ImageGrid['json_content']>({ default: () => [] });

const imageGridOptions: { label: string; value: GridImage['colspan'] }[] = [
  { label: '1/1', value: 'col-span-full' },
  { label: '1/3', value: 'col-span-2' },
  { label: '1/2', value: 'col-span-3' },
  { label: '2/3', value: 'col-span-4' },
];

function tileHeightClass(image: GridImage): string {
  return image.colspan === 'col-span-full' ? 'h-48 md:h-60' : 'h-32 md:h-40';
}

function tileSpanClass(image: GridImage): string {
  return image.colspan;
}

function createItem(): GridImage {
  return { colspan: 'col-span-2', image: '', alt: '', title: '' };
}
</script>

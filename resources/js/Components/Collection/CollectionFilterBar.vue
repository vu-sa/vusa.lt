<template>
  <!-- From md the facets sit in a row of popovers, toggled by Filtrai; below md the same
       facets stack in a bottom sheet (.ai/rules/js-pages-admin.md). -->
  <div
    v-if="isAtLeastMd && open && facets.length > 0"
    class="flex flex-wrap items-center gap-2 border-t border-border/60 pt-3"
    data-slot="collection-filter-bar"
  >
    <Popover v-for="facet in facets" :key="facet.field">
      <PopoverTrigger as-child>
        <button
          type="button"
          :class="controlVariants({ size: 'sm', active: selectedCount(facet) > 0 })"
        >
          <span>{{ facet.label }}</span>
          <span v-if="selectedCount(facet) > 0" :class="controlCountClass">
            {{ selectedCount(facet) }}
          </span>
          <ChevronDown class="size-3.5" aria-hidden="true" />
        </button>
      </PopoverTrigger>
      <PopoverContent align="start" class="max-h-80 w-64 overflow-y-auto p-1">
        <CollectionFacetOptions :facet @toggle="(field, value) => emit('toggle', field, value)" />
      </PopoverContent>
    </Popover>
  </div>

  <Sheet v-if="!isAtLeastMd" :open="sheetOpen" @update:open="value => emit('update:sheetOpen', value)">
    <SheetContent side="bottom" class="max-h-[85dvh] overflow-y-auto">
      <SheetHeader>
        <SheetTitle>{{ $t('Filtrai') }}</SheetTitle>
        <SheetDescription class="sr-only">
          {{ $t('Siaurink sąrašą pagal savybes.') }}
        </SheetDescription>
      </SheetHeader>

      <div class="flex flex-col gap-5 px-4 pb-4">
        <section v-for="facet in facets" :key="facet.field">
          <h3 class="mb-1 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
            {{ facet.label }}
          </h3>
          <CollectionFacetOptions :facet @toggle="(field, value) => emit('toggle', field, value)" />
        </section>
      </div>

      <SheetFooter class="flex-row justify-between gap-2 border-t border-border p-4">
        <Button variant="ghost" :disabled="activeCount === 0" @click="emit('clear')">
          {{ $t('Išvalyti visus') }}
        </Button>
        <Button variant="brand" @click="emit('update:sheetOpen', false)">
          {{ $t('Rodyti rezultatus') }}
        </Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';

import CollectionFacetOptions from './CollectionFacetOptions.vue';

import { Button } from '@/Components/ui/button';
import { controlCountClass, controlVariants } from '@/Components/ui/control';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/Components/ui/sheet';
import type { CollectionFacet } from '@/Composables/useCollectionSource';

defineProps<{
  facets: CollectionFacet[];
  /** The inline row is shown (md and up). */
  open: boolean;
  /** The bottom sheet is shown (below md). */
  sheetOpen: boolean;
  isAtLeastMd: boolean;
  activeCount: number;
}>();

const emit = defineEmits<{
  'toggle': [field: string, value: string];
  'clear': [];
  'update:sheetOpen': [open: boolean];
}>();

const selectedCount = (facet: CollectionFacet) => facet.values.filter(value => value.isSelected).length;
</script>

<template>
  <div class="mt-4 flex flex-col gap-4">
    <!-- Ensure the json_content is initialized properly -->
    <div v-if="!isModelValueInitialized" class="flex justify-center">
      <Button @click="initializeModelValue">
        <IFluentAdd24Filled />
        Sukurti tinklelį
      </Button>
    </div>

    <template v-else>
      <RCSectionOptions v-model="options" />

      <!-- Grid Options -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <FormFieldWrapper id="gap" label="Tarpai">
          <Select v-model="options.gap">
            <SelectTrigger>
              <SelectValue placeholder="Pasirinkite tarpą" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in gapOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
        <FormFieldWrapper id="mobileStacking" label="Mobilusis vaizdas">
          <div class="flex items-center gap-2">
            <Switch :model-value="options.mobileStacking" @update:model-value="val => options.mobileStacking = val" />
            <span class="text-sm">Dėti stulpelius vertikaliai</span>
          </div>
        </FormFieldWrapper>
        <FormFieldWrapper id="equalHeight" label="Vienodas aukštis">
          <div class="flex items-center gap-2">
            <Switch :model-value="options.equalHeight" @update:model-value="val => options.equalHeight = val" />
            <span class="text-sm">Vienodo aukščio stulpeliai</span>
          </div>
        </FormFieldWrapper>
        <FormFieldWrapper id="dividers" :label="$t('rich-content.grid_dividers')"
          :hint="$t('rich-content.grid_dividers_help')">
          <div class="flex items-center gap-2">
            <Switch :model-value="options.dividers" @update:model-value="val => options.dividers = val" />
            <span class="text-sm">{{ $t('rich-content.grid_dividers_on') }}</span>
          </div>
        </FormFieldWrapper>
        <FormFieldWrapper id="verticalAlign" :label="$t('rich-content.grid_vertical_align')">
          <Select :model-value="options.verticalAlign ?? 'stretch'" @update:model-value="options.verticalAlign = $event">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="stretch">{{ $t('rich-content.grid_vertical_align_stretch') }}</SelectItem>
              <SelectItem value="start">{{ $t('rich-content.grid_vertical_align_start') }}</SelectItem>
              <SelectItem value="center">{{ $t('rich-content.grid_vertical_align_center') }}</SelectItem>
              <SelectItem value="end">{{ $t('rich-content.grid_vertical_align_end') }}</SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </div>

      <!-- Row management -->
      <div v-for="(row, rowIndex) in json_content" :key="rowIndex" class="flex flex-col gap-4">
        <div
          class="flex w-full items-center gap-4 rounded-md border bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/30">
          <h4 class="font-medium">
            Eilutė {{ rowIndex + 1 }}
          </h4>
          <div class="flex-grow" />
          <ButtonGroup>
            <Button v-if="rowIndex > 0" variant="ghost" size="icon-sm" class="rounded-full" @click="moveRow(rowIndex, rowIndex - 1)">
              <IFluentArrowUp24Filled />
            </Button>
            <Button v-if="rowIndex < json_content.length - 1" variant="ghost" size="icon-sm" class="rounded-full"
              @click="moveRow(rowIndex, rowIndex + 1)">
              <IFluentArrowDown24Filled />
            </Button>
            <Button variant="ghost" size="icon-sm" class="rounded-full" @click="removeRow(rowIndex)">
              <IFluentDelete24Filled />
            </Button>
          </ButtonGroup>
        </div>

        <!-- Column layout -->
        <div class="w-full">
          <!-- Grid content -->
          <div :class="['grid', options.gap || 'gap-4', 'grid-cols-12']">
            <div v-for="(column, colIndex) in row.columns" :key="colIndex"
              :class="[column.width, 'flex flex-col gap-2 rounded-md border bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/20']">
              <div class="flex items-center gap-4">
                <FormFieldWrapper id="`width-${rowIndex}-${colIndex}`" label="Plotis" class="max-w-[140px]">
                  <Select v-model="column.width">
                    <SelectTrigger>
                      <SelectValue placeholder="Plotis" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="opt in columnWidthOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </FormFieldWrapper>
                <div class="flex-grow" />
                <ButtonGroup>
                  <Button v-if="colIndex > 0" variant="ghost" size="icon-sm" class="rounded-full" @click="moveColumn(rowIndex, colIndex, colIndex - 1)">
                    <IFluentArrowLeft24Filled />
                  </Button>
                  <Button v-if="colIndex < row.columns.length - 1" variant="ghost" size="icon-sm" class="rounded-full"
                    @click="moveColumn(rowIndex, colIndex, colIndex + 1)">
                    <IFluentArrowRight24Filled />
                  </Button>
                  <Button variant="ghost" size="icon-sm" class="rounded-full" @click="removeColumn(rowIndex, colIndex)">
                    <IFluentDelete24Filled />
                  </Button>
                </ButtonGroup>
              </div>

              <!-- Column content type selector -->
              <FormFieldWrapper :id="`content-type-${rowIndex}-${colIndex}`" label="Turinio tipas">
                <Select v-model="column.content.type">
                  <SelectTrigger>
                    <SelectValue placeholder="Turinio tipas" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="opt in columnContentOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </FormFieldWrapper>

              <!-- Content editor based on content type -->
              <div class="mt-2 w-full">
                <div v-if="column.content.type === 'tiptap'" class="w-full">
                  <TiptapEditor v-model="column.content.value" preset="compact" :show-toolbar-toggle="true" />
                </div>
                <div v-else-if="column.content.type === 'image'" class="flex flex-col gap-3">
                  <FormFieldWrapper :id="`image-${rowIndex}-${colIndex}`" :label="$t('rich-content.image')">
                    <div>
                      <TiptapImageButton v-if="!column.content.value"
                        @submit:object="(img) => { column.content.value = img.src; column.content.alt = img.alt; }">
                        {{ $t('rich-content.select_image') }}
                      </TiptapImageButton>
                      <div v-else class="relative">
                        <img :src="column.content.value" class="aspect-video w-full rounded-lg object-cover"
                          :style="column.content.objectPosition ? { objectPosition: column.content.objectPosition } : undefined">
                        <div class="absolute top-1 right-1 flex gap-1">
                          <Button size="icon-sm" variant="ghost" class="rounded-full bg-white/80 dark:bg-zinc-900/80"
                            @click="openFocalPoint(column)">
                            <IFluentTarget24Regular />
                          </Button>
                          <Button size="icon-sm" variant="ghost" class="rounded-full bg-white/80 dark:bg-zinc-900/80"
                            @click="column.content.value = null">
                            <IFluentDismiss20Regular />
                          </Button>
                        </div>
                      </div>
                    </div>
                  </FormFieldWrapper>
                  <FormFieldWrapper :id="`image-alt-${rowIndex}-${colIndex}`" :label="$t('rich-content.image_alt_text')">
                    <Input v-model="column.content.alt" type="text" :placeholder="$t('rich-content.image_alt_placeholder')" />
                  </FormFieldWrapper>
                  <FormFieldWrapper :id="`image-overlay-${rowIndex}-${colIndex}`" :label="$t('rich-content.overlay_content')">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                      <Input :model-value="column.content.overlayContent?.title"
                        type="text" :placeholder="$t('rich-content.enter_overlay_title')"
                        @update:model-value="setOverlayField(column, 'title', $event as string)" />
                      <Input :model-value="column.content.overlayContent?.subtitle"
                        type="text" :placeholder="$t('rich-content.enter_overlay_subtitle')"
                        @update:model-value="setOverlayField(column, 'subtitle', $event as string)" />
                    </div>
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                      <Select :model-value="column.content.overlayCorner ?? 'bottom-left'" @update:model-value="column.content.overlayCorner = $event">
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
                      <Select :model-value="column.content.overlayPadding ?? 'md'" @update:model-value="column.content.overlayPadding = $event">
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="sm">{{ $t('rich-content.small') }}</SelectItem>
                          <SelectItem value="md">{{ $t('rich-content.medium') }}</SelectItem>
                          <SelectItem value="lg">{{ $t('rich-content.large') }}</SelectItem>
                        </SelectContent>
                      </Select>
                      <div class="flex items-center gap-2">
                        <Switch :model-value="column.content.overlayOverhang" @update:model-value="val => column.content.overlayOverhang = val" />
                        <span class="text-sm">{{ $t('rich-content.overlay_overhang') }}</span>
                      </div>
                    </div>
                  </FormFieldWrapper>
                  <RCDecorationListEditor
                    :model-value="imageDecorations(column)"
                    @update:model-value="column.content.decorations = $event" />
                </div>
                <div v-else-if="column.content.type === 'card'" class="flex flex-col gap-3">
                  <FormFieldWrapper :id="`card-image-${rowIndex}-${colIndex}`" :label="$t('rich-content.image')">
                    <div>
                      <TiptapImageButton v-if="!cardValue(column).image"
                        @submit:object="updateCardImage(column, $event)">
                        {{ $t('rich-content.select_image') }}
                      </TiptapImageButton>
                      <div v-else class="relative">
                        <img :src="cardValue(column).image" class="aspect-video w-full rounded-lg object-cover">
                        <Button class="absolute top-1 right-1 rounded-full" size="icon-sm" variant="ghost"
                          @click="cardValue(column).image = ''">
                          <IFluentDismiss20Regular />
                        </Button>
                      </div>
                    </div>
                  </FormFieldWrapper>
                  <FormFieldWrapper :id="`card-title-${rowIndex}-${colIndex}`" :label="$t('rich-content.title')">
                    <Input v-model="cardValue(column).title" type="text" />
                  </FormFieldWrapper>
                  <FormFieldWrapper :id="`card-description-${rowIndex}-${colIndex}`" :label="$t('rich-content.description')">
                    <Input v-model="cardValue(column).description" type="text" />
                  </FormFieldWrapper>
                  <FormFieldWrapper :id="`card-href-${rowIndex}-${colIndex}`" :label="$t('rich-content.link_url')">
                    <Input v-model="cardValue(column).href" type="url" placeholder="https://…" />
                  </FormFieldWrapper>
                </div>
              </div>
            </div>
          </div>

          <!-- Add column button -->
          <div class="mt-2 flex justify-center">
            <Button variant="ghost" :disabled="isMaxColumnsReached(row)" @click="addColumn(rowIndex)">
              <IFluentAdd24Filled />
              Pridėti stulpelį
            </Button>
            <TooltipProvider v-if="isMaxColumnsReached(row)">
              <Tooltip>
                <TooltipTrigger as-child>
                  <span class="ml-2 text-zinc-400 flex items-center">
                    <IFluentInfo16Filled class="mr-1" />
                  </span>
                </TooltipTrigger>
                <TooltipContent>
                  Maksimalus stulpelių skaičius: {{ MAX_COLUMNS }}
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>
        </div>
      </div>

      <!-- Add row button -->
      <div class="mt-2 flex justify-center">
        <Button variant="ghost" @click="addRow">
          <IFluentAdd24Filled />
          Pridėti eilutę
        </Button>
      </div>
    </template>

    <!-- Shared focal-point dialog for image cells — only one can be open at a time. -->
    <Dialog v-model:open="showFocalPoint">
      <DialogContent class="max-w-xl">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.image_focus_point') }}</DialogTitle>
        </DialogHeader>
        <FocalPointPicker
          v-if="focalPointColumn?.content?.value"
          :image-url="focalPointColumn.content.value"
          :model-value="focalPointColumn.content.objectPosition ?? null"
          @update:model-value="(val: string) => { if (focalPointColumn) focalPointColumn.content.objectPosition = val; }"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { defineModel, computed, onMounted, ref, watch } from 'vue';

import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import type { ContentGrid } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import RCDecorationListEditor from '../Editor/RCDecorationListEditor.vue';

const json_content = defineModel<ContentGrid['json_content']>();
const options = defineModel<ContentGrid['options']>('options');

const MAX_COLUMNS = 4; // Maximum number of columns per row

const gapOptions = [
  { label: 'Mažas (0.5rem)', value: 'gap-2' },
  { label: 'Vidutinis (1rem)', value: 'gap-4' },
  { label: 'Didelis (1.5rem)', value: 'gap-6' },
  { label: 'Labai didelis (2rem)', value: 'gap-8' },
];

const columnWidthOptions = [
  { label: '25%', value: 'col-span-3' },
  { label: '33%', value: 'col-span-4' },
  { label: '40%', value: 'col-span-5' },
  { label: '50%', value: 'col-span-6' },
  { label: '60%', value: 'col-span-7' },
  { label: '66%', value: 'col-span-8' },
  { label: '75%', value: 'col-span-9' },
  { label: '100%', value: 'col-span-12' },
];

const columnContentOptions = [
  { label: 'Tekstas', value: 'tiptap' },
  { label: 'Nuotrauka', value: 'image' },
  { label: 'Kortelė', value: 'card' },
];

// Check if the maximum number of columns is reached
function isMaxColumnsReached(row) {
  return row.columns && row.columns.length >= MAX_COLUMNS;
}

// A 'card' cell's value is `{ image, imageAlt, title, description, href }` — lazily
// initialize it the first time a column's type is switched to 'card' (switching
// away and back would otherwise leave whatever the previous type left behind, e.g.
// tiptap's `{}`).
function cardValue(column) {
  const value = column.content.value;
  if (!value || typeof value !== 'object' || Array.isArray(value)) {
    column.content.value = { image: '', imageAlt: '', title: '', description: '', href: '' };
  }
  return column.content.value;
}

const showFocalPoint = ref(false);
const focalPointColumn = ref<{ content: { value: string; objectPosition?: string } } | null>(null);

function openFocalPoint(column) {
  focalPointColumn.value = column;
  showFocalPoint.value = true;
}

function setOverlayField(column, field: 'title' | 'subtitle', value: string) {
  column.content.overlayContent = { title: '', subtitle: '', ...column.content.overlayContent, [field]: value };
}

function imageDecorations(column) {
  if (!Array.isArray(column.content.decorations)) {
    column.content.decorations = [];
  }
  return column.content.decorations;
}

function updateCardImage(column, imageData) {
  const card = cardValue(column);
  card.image = imageData.src;
  card.imageAlt = imageData.alt;
}

/**
 * `json_content` IS the rows array (see the `ContentGrid` type) — the same object
 * `ContentEditorFactory` reads/writes as `content.json_content`. An earlier version
 * of this component treated it as a wrapper (`{ json_content: rows, options }`) and
 * re-derived that wrapper on every mount via a broken parser; since a real rows array
 * has no `.json_content`/`.options` keys of its own, that check was always false,
 * so `initializeModelValue()` ran on *every* mount and silently replaced whatever
 * rows the author had already built with a fresh default row. `ContentGridDisplay`
 * still tolerates the old wrapped shape for rows saved under that bug, so no
 * migration is needed — this only changes what gets written going forward.
 */
const isModelValueInitialized = computed(() => Array.isArray(json_content.value) && json_content.value.length > 0);

function createDefaultRow() {
  return {
    columns: [
      { width: 'col-span-6', content: { type: 'tiptap', value: {} } },
      { width: 'col-span-6', content: { type: 'tiptap', value: {} } },
    ],
  };
}

function initializeModelValue() {
  json_content.value = [createDefaultRow()];
  if (!options.value) {
    options.value = { gap: 'gap-4', mobileStacking: true, equalHeight: false };
  }
}

onMounted(() => {
  if (!isModelValueInitialized.value) {
    initializeModelValue();
  }
});

// Row management functions
function addRow() {
  json_content.value.push(createDefaultRow());
}

function removeRow(rowIndex) {
  if (json_content.value.length > 1) {
    json_content.value.splice(rowIndex, 1);
  }
}

function moveRow(currentIndex, targetIndex) {
  const row = json_content.value[currentIndex];
  json_content.value.splice(currentIndex, 1);
  json_content.value.splice(targetIndex, 0, row);
}

// Column management functions
function addColumn(rowIndex) {
  const row = json_content.value[rowIndex];
  if (!row || isMaxColumnsReached(row)) return;

  row.columns.push({ width: 'col-span-6', content: { type: 'tiptap', value: {} } });

  // Adjust column widths based on count
  redistributeColumnWidths(row);
}

function removeColumn(rowIndex, colIndex) {
  const row = json_content.value[rowIndex];
  if (!row?.columns) return;

  if (row.columns.length > 1) {
    row.columns.splice(colIndex, 1);
    redistributeColumnWidths(row);
  }
}

function moveColumn(rowIndex, currentIndex, targetIndex) {
  const row = json_content.value[rowIndex];
  if (!row?.columns) return;

  const column = row.columns[currentIndex];
  row.columns.splice(currentIndex, 1);
  row.columns.splice(targetIndex, 0, column);
}

// Helper function to redistribute column widths based on number of columns
function redistributeColumnWidths(row) {
  if (!row || !row.columns) return;

  const colCount = row.columns.length;

  // Calculate approximate equal widths
  let spanValue;
  if (colCount === 1) {
    spanValue = 'col-span-12';
  }
  else if (colCount === 2) {
    spanValue = 'col-span-6';
  }
  else if (colCount === 3) {
    spanValue = 'col-span-4';
  }
  else if (colCount === 4) {
    spanValue = 'col-span-3';
  }

  // Apply new width to all columns
  if (spanValue) {
    row.columns.forEach((col) => {
      col.width = spanValue;
    });
  }
}
</script>

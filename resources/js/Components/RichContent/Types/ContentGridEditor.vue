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
            <Switch :checked="options.mobileStacking" @update:checked="val => options.mobileStacking = val" />
            <span class="text-sm">Dėti stulpelius vertikaliai</span>
          </div>
        </FormFieldWrapper>
        <FormFieldWrapper id="equalHeight" label="Vienodas aukštis">
          <div class="flex items-center gap-2">
            <Switch :checked="options.equalHeight" @update:checked="val => options.equalHeight = val" />
            <span class="text-sm">Vienodo aukščio stulpeliai</span>
          </div>
        </FormFieldWrapper>
      </div>

      <!-- Row management -->
      <div v-for="(row, rowIndex) in json_content.json_content" :key="rowIndex" class="flex flex-col gap-4">
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
            <Button v-if="rowIndex < json_content.json_content.length - 1" variant="ghost" size="icon-sm" class="rounded-full"
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
                <div v-else-if="column.content.type === 'image'">
                  <FormFieldWrapper :id="`image-${rowIndex}-${colIndex}`" label="Nuotrauka">
                    <div>
                      <TiptapImageButton v-if="!column.content.value" size="medium"
                        @submit="column.content.value = $event">
                        Pasirinkti paveikslėlį
                      </TiptapImageButton>
                      <div v-else class="relative">
                        <img :src="column.content.value" class="aspect-video w-full rounded-lg object-cover">
                        <Button class="absolute top-1 right-1 rounded-full" size="icon-sm" variant="ghost"
                          @click="column.content.value = null">
                          <IFluentDismiss20Regular />
                        </Button>
                      </div>
                    </div>
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
  </div>
</template>

<script setup lang="ts">
import { defineModel, computed, onMounted, ref, watch } from 'vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import type { ContentGrid } from '@/Types/contentParts';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';

const json_content = defineModel<ContentGrid['json_content']>();
const options = defineModel<ContentGrid[]>('options')

const MAX_COLUMNS = 4; // Maximum number of columns per row

const gapOptions = [
  { label: 'Mažas (0.5rem)', value: 'gap-2' },
  { label: 'Vidutinis (1rem)', value: 'gap-4' },
  { label: 'Didelis (1.5rem)', value: 'gap-6' },
  { label: 'Labai didelis (2rem)', value: 'gap-8' }
];

const columnWidthOptions = [
  { label: '25%', value: 'col-span-3' },
  { label: '33%', value: 'col-span-4' },
  { label: '50%', value: 'col-span-6' },
  { label: '66%', value: 'col-span-8' },
  { label: '75%', value: 'col-span-9' },
  { label: '100%', value: 'col-span-12' },
];

const columnContentOptions = [
  { label: 'Tekstas', value: 'tiptap' },
  { label: 'Nuotrauka', value: 'image' },
];

// Check if the maximum number of columns is reached
function isMaxColumnsReached(row) {
  return row.columns && row.columns.length >= MAX_COLUMNS;
}

// Parse incoming data if needed (supports both string and object formats)
function parseModelValue() {
  if (!json_content.value) return null;

  // Create a working copy
  const result = { ...json_content.value };

  // Handle json_content as string (from backend)
  if (typeof result.value === 'string') {
    try {
      result.value = JSON.parse(result);
    } catch (e) {
      console.error('Failed to parse grid JSON content', e);
      result.value = [];
    }
  }

  // Handle options as string
  if (typeof options === 'string') {
    try {
      options.value = JSON.parse(options.value);
    } catch (e) {
      console.error('Failed to parse grid options', e);
      options.value = {
        gap: 'gap-4',
        mobileStacking: true,
        equalHeight: false
      };
    }
  }

  return result;
}

// Check if the json_content is properly initialized
const isModelValueInitialized = computed(() => {
  const parsed = parseModelValue();
  return !!(
    parsed &&
    parsed.json_content &&
    Array.isArray(parsed.json_content) &&
    parsed.options
  );
});

// Initialize the model with proper structure if it's not already
function initializeModelValue() {
  // Instead of nesting rows inside json_content, make rows the main json_content
  const rowsData = [
    {
      columns: [
        {
          width: 'col-span-6',
          content: {
            type: 'tiptap',
            value: {}
          }
        },
        {
          width: 'col-span-6',
          content: {
            type: 'tiptap',
            value: {}
          }
        }
      ]
    }
  ];

  const defaultOptions = {
    gap: 'gap-4',
    mobileStacking: true,
    equalHeight: false
  };

  // Set the structure without extra nesting
  json_content.value = {
    json_content: rowsData,
    options: defaultOptions
  };
}

// Process incoming data when component mounts
onMounted(() => {
  const parsed = parseModelValue();

  if (!isModelValueInitialized.value) {
    initializeModelValue();
  } else if (parsed && parsed !== json_content.value) {
    // Use the parsed data to update the model value
    json_content.value = parsed;
  }
});

// Row management functions
function addRow() {
  if (!json_content.value.json_content) {
    json_content.value.json_content = [];
  }

  json_content.value.json_content.push({
    columns: [
      {
        width: 'col-span-6',
        content: {
          type: 'tiptap',
          value: {}
        }
      },
      {
        width: 'col-span-6',
        content: {
          type: 'tiptap',
          value: {}
        }
      }
    ]
  });
}

function removeRow(rowIndex) {
  if (json_content.value.json_content && json_content.value.json_content.length > 1) {
    json_content.value.json_content.splice(rowIndex, 1);
  }
}

function moveRow(currentIndex, targetIndex) {
  if (!json_content.value.json_content) return;

  const rows = json_content.value.json_content;
  const row = rows[currentIndex];
  rows.splice(currentIndex, 1);
  rows.splice(targetIndex, 0, row);
}

// Column management functions
function addColumn(rowIndex) {
  if (!json_content.value.json_content || !json_content.value.json_content[rowIndex]) return;

  const row = json_content.value.json_content[rowIndex];

  // Check if maximum columns reached
  if (isMaxColumnsReached(row)) {
    return;
  }

  row.columns.push({
    width: 'col-span-6',
    content: {
      type: 'tiptap',
      value: {}
    }
  });

  // Adjust column widths based on count
  redistributeColumnWidths(row);
}

function removeColumn(rowIndex, colIndex) {
  if (!json_content.value.json_content ||
    !json_content.value.json_content[rowIndex] ||
    !json_content.value.json_content[rowIndex].columns) return;

  const columns = json_content.value.json_content[rowIndex].columns;
  if (columns.length > 1) {
    columns.splice(colIndex, 1);
    // Redistribute column widths after removal
    redistributeColumnWidths(json_content.value.json_content[rowIndex]);
  }
}

function moveColumn(rowIndex, currentIndex, targetIndex) {
  if (!json_content.value.json_content ||
    !json_content.value.json_content[rowIndex] ||
    !json_content.value.json_content[rowIndex].columns) return;

  const columns = json_content.value.json_content[rowIndex].columns;
  const column = columns[currentIndex];
  columns.splice(currentIndex, 1);
  columns.splice(targetIndex, 0, column);
}

// Helper function to redistribute column widths based on number of columns
function redistributeColumnWidths(row) {
  if (!row || !row.columns) return;

  const colCount = row.columns.length;

  // Calculate approximate equal widths
  let spanValue;
  if (colCount === 1) {
    spanValue = 'col-span-12';
  } else if (colCount === 2) {
    spanValue = 'col-span-6';
  } else if (colCount === 3) {
    spanValue = 'col-span-4';
  } else if (colCount === 4) {
    spanValue = 'col-span-3';
  }

  // Apply new width to all columns
  if (spanValue) {
    row.columns.forEach(col => {
      col.width = spanValue;
    });
  }
}
</script>

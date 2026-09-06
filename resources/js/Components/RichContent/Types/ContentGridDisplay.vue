<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="processedOptions.title"
    :subtitle="processedOptions.subtitle"
    :eyebrow="processedOptions.eyebrow" :band
    :align="processedOptions.align ?? 'center'" :heading-level="processedOptions.headingLevel"
    :show-separator="processedOptions.showSeparator" inner="full"
    :editable @update:header="updateOptions"
  >
    <div class="relative">
      <!-- `dividers` turns the gaps into 1px rules: the grid's own background becomes the rule
           colour and each cell paints over it, which is the design's panelled grid (no cards, no
           shadows — just hairlines). `gap-px` overrides the authored gap while it is on. -->
      <div :class="['content-grid', dividers && 'border border-border']">
        <div class="flex flex-col" :class="dividers ? 'gap-px bg-border' : (processedOptions.gap || 'gap-4')">
          <div v-for="(row, rowIndex) in rows" :key="rowIndex" class="relative w-full">
            <div v-if="editable" class="mb-1 flex justify-start">
              <RCGridRowOptions
                :block-key="blockKey ?? ''" :row-index
                :can-move-up="rowIndex > 0" :can-move-down="rowIndex < rows.length - 1" :can-remove="rows.length > 1"
                @move-up="moveRow(rowIndex, rowIndex - 1)"
                @move-down="moveRow(rowIndex, rowIndex + 1)"
                @remove="removeRow(rowIndex)"
              />
            </div>
            <!-- Responsive grid - stack columns on mobile if mobileStacking is true -->
            <div :class="[
              'grid',
              dividers ? 'gap-px bg-border' : (processedOptions.gap || 'gap-4'),
              'grid-cols-12',
              processedOptions.mobileStacking ? 'max-md:grid-cols-1' : '',
              VERTICAL_ALIGN_CLASS[processedOptions.verticalAlign ?? 'stretch'],
            ]">
              <div v-for="(column, colIndex) in row.columns" :key="colIndex" :class="[
                column.width,
                'relative',
                processedOptions.equalHeight ? 'h-full' : '',
                dividers && 'bg-background p-6 sm:p-8',
              ]">
                <!-- Render content based on type -->
                <div v-if="column.content.type === 'tiptap'" class="max-w-none tracking-normal">
                  <template v-if="editable">
                    <TiptapEditor
                      v-if="isCellLive(rowIndex, colIndex)"
                      :model-value="tiptapValue(column)" preset="compact" prose-style
                      :placeholder="$t('rich-content.content')"
                      @update:model-value="updateColumnContent(rowIndex, colIndex, { value: $event })"
                    />
                    <div
                      v-else class="min-h-12 cursor-text" data-rc-interactive data-rc-grid-cell-content
                      role="button" tabindex="0"
                      @click="$emit('claim-inline-field', cellFieldId(rowIndex, colIndex))"
                      @keydown.enter.prevent="$emit('claim-inline-field', cellFieldId(rowIndex, colIndex))"
                      @keydown.space.prevent="$emit('claim-inline-field', cellFieldId(rowIndex, colIndex))"
                    >
                      <RichContentTiptapHTML v-if="hasContent(column.content.value)" :json_content="column.content.value" />
                      <p v-else class="italic text-muted-foreground/60">
                        {{ $t('rich-content.content') }}
                      </p>
                    </div>
                  </template>
                  <RichContentTiptapHTML v-else :json_content="column.content.value" />
                </div>
                <div v-else-if="column.content.type === 'image'" :class="editable ? 'relative overflow-hidden' : 'h-full'">
                  <template v-if="editable">
                    <div class="relative overflow-hidden" :class="[processedOptions.equalHeight ? 'h-full' : 'aspect-video', !imageSrc(column) && 'min-h-32']">
                      <img
                        :src="imageSrc(column)" class="!size-full rounded-md object-cover" :alt="column.content.alt || ''"
                        :style="column.content.objectPosition ? { objectPosition: column.content.objectPosition } : undefined"
                      >
                      <RCImageHotspot
                        :image-url="imageSrc(column)" :alt="column.content.alt" :object-position="column.content.objectPosition"
                        :block-key="blockKey ?? ''" :image-index="cellFlatIndex(rowIndex, colIndex)" full-tile-trigger
                        @update:image="replaceColumnImage(rowIndex, colIndex, $event)"
                        @update:alt="updateColumnContent(rowIndex, colIndex, { alt: $event })"
                        @update:object-position="updateColumnContent(rowIndex, colIndex, { objectPosition: $event })"
                        @delete="clearColumnImage(rowIndex, colIndex)"
                      />
                    </div>
                  </template>
                  <ImageWithDecorations
                    v-else
                    :src="imageSrc(column)"
                    :alt="column.content.alt || ''"
                    :height-class="processedOptions.equalHeight ? 'h-full' : 'aspect-video'"
                    :object-position="column.content.objectPosition"
                    :overlay-content="column.content.overlayContent"
                    :overlay-corner="column.content.overlayCorner"
                    :overlay-overhang="column.content.overlayOverhang"
                    :overlay-padding="column.content.overlayPadding"
                    :decorations="column.content.decorations"
                  />
                </div>
                <template v-else-if="column.content.type === 'card'">
                  <div v-if="editable" class="group relative flex h-full flex-col overflow-hidden border border-border bg-card">
                    <div class="relative aspect-[16/9] overflow-hidden bg-secondary" :class="{ 'min-h-32': !cardValue(column).image }">
                      <img :src="cardValue(column).image" class="size-full object-cover" :alt="cardValue(column).imageAlt || ''">
                      <RCImageHotspot
                        :image-url="cardValue(column).image ?? ''" :alt="cardValue(column).imageAlt"
                        :block-key="blockKey ?? ''" :image-index="cellFlatIndex(rowIndex, colIndex)" full-tile-trigger
                        @update:image="replaceCardImage(rowIndex, colIndex, $event)"
                        @update:alt="updateCardValue(rowIndex, colIndex, { imageAlt: $event })"
                        @delete="updateCardValue(rowIndex, colIndex, { image: '' })"
                      >
                        <template #options>
                          <Field class="border-t border-border pt-4">
                            <FieldLabel>{{ $t('rich-content.link_url') }}</FieldLabel>
                            <Input
                              :model-value="cardValue(column).href" type="url" placeholder="https://…"
                              @update:model-value="updateCardValue(rowIndex, colIndex, { href: $event as string })"
                            />
                          </Field>
                        </template>
                      </RCImageHotspot>
                    </div>
                    <div class="flex flex-1 flex-col gap-1.5 p-5">
                      <RCInlineText
                        as="h3" class="text-base font-bold leading-tight text-foreground"
                        :model-value="cardValue(column).title ?? ''" editable :placeholder="$t('rich-content.title')"
                        @update:model-value="updateCardValue(rowIndex, colIndex, { title: $event })"
                      />
                      <RCInlineText
                        as="p" class="text-sm text-muted-foreground"
                        :model-value="cardValue(column).description ?? ''" editable :placeholder="$t('rich-content.description')"
                        @update:model-value="updateCardValue(rowIndex, colIndex, { description: $event })"
                      />
                    </div>
                  </div>
                  <RCFeatureCard
                    v-else
                    :title="cardValue(column).title || ''"
                    :cover-image="cardValue(column).image || null"
                    :cover-alt="cardValue(column).imageAlt || cardValue(column).title"
                    :href="cardValue(column).href || null"
                    :show-cover-fallback="false"
                    :class="processedOptions.equalHeight ? 'h-full' : ''"
                  >
                    <p v-if="cardValue(column).description" class="text-sm text-muted-foreground">
                      {{ cardValue(column).description }}
                    </p>
                  </RCFeatureCard>
                </template>

                <RCGridColumnOptions
                  v-if="editable"
                  :block-key="blockKey ?? ''" :row-index :col-index
                  :type="column.content.type" :width="column.width"
                  :can-move-left="colIndex > 0" :can-move-right="colIndex < row.columns.length - 1"
                  :can-remove="row.columns.length > 1"
                  @update:type="setColumnType(rowIndex, colIndex, $event)"
                  @update:width="setColumnWidth(rowIndex, colIndex, $event)"
                  @move-left="moveColumn(rowIndex, colIndex, colIndex - 1)"
                  @move-right="moveColumn(rowIndex, colIndex, colIndex + 1)"
                  @remove="removeColumn(rowIndex, colIndex)"
                />
              </div>
            </div>

            <RCAddPlaceholder
              v-if="editable && !isMaxColumnsReached(row)"
              :label="$t('rich-content.grid_add_column')"
              class="right-0 top-1/2 -translate-y-1/2 translate-x-full"
              data-rc-grid-add-column
              @click="addColumn(rowIndex)"
            />
          </div>

          <RCAddPlaceholder v-if="editable && !rows.length" :label="$t('rich-content.grid_add_row')" @click="addRow" />
        </div>
      </div>

      <RCAddPlaceholder
        v-if="editable && rows.length"
        :label="$t('rich-content.grid_add_row')"
        class="left-1/2 -bottom-2 -translate-x-1/2 translate-y-full"
        data-rc-grid-add-row
        @click="addRow"
      />
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import RCFeatureCard from '../RCFeatureCard.vue';
import RCSection from '../RCSection.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import RCAddPlaceholder from '../Editor/Fullscreen/RCAddPlaceholder.vue';
import RCImageHotspot from '../Editor/Fullscreen/RCImageHotspot.vue';
import RCGridColumnOptions from '../RCContentGrid/RCGridColumnOptions.vue';
import RCGridRowOptions from '../RCContentGrid/RCGridRowOptions.vue';
import type { BandResolution } from '../bandLayout';

import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import type { DecorationConfig } from '@/Types/contentParts';

// Lazy-loaded: only needed while a tiptap cell is actually being edited in the
// full-screen editor. A static import would bundle it into every public page
// that renders a content grid, which never mounts this branch at all.
const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

// The user's specific complaint was a short text column stretching to the row's
// height with its content pinned to the top — `grid` items default to `stretch` with
// nothing overriding it. `center` reproduces MembershipPage's `items-center` mascot
// layout (a text column beside a taller decorated image).
const VERTICAL_ALIGN_CLASS: Record<string, string> = {
  stretch: '',
  start: 'items-start',
  center: 'items-center',
  end: 'items-end',
};

interface GridColumnContent {
  type: string;
  /** Doc/HTML for `tiptap`, an image URL for `image`, a `GridCardValue` for `card`. */
  value: unknown;
  alt?: string;
  title?: string;
  objectPosition?: string;
  overlayContent?: { title: string; subtitle: string };
  overlayCorner?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  overlayOverhang?: boolean;
  overlayPadding?: 'sm' | 'md' | 'lg';
  decorations?: DecorationConfig[];
}

interface GridColumn {
  width: string;
  content: GridColumnContent;
}

interface GridRow {
  columns: GridColumn[];
}

interface GridCardValue {
  image?: string;
  imageAlt?: string;
  title?: string;
  description?: string;
  href?: string;
}

const props = defineProps<{
  element: {
    json_content: unknown;
    options?: {
      title?: string;
      subtitle?: string;
      eyebrow?: string;
      /** 1px rules instead of gaps, each cell painted over them. See the template. */
      dividers?: boolean;
      /** Header alignment, forwarded to RCSection — grids default to centered like every other section block. */
      align?: 'center' | 'start';
      /** Semantic heading level for the title, forwarded to RCSection. */
      headingLevel?: 2 | 3 | 4;
      /** Whether to render the separator bar beneath the title. */
      showSeparator?: boolean;
      /** Vertical alignment of column content within each row. */
      verticalAlign?: 'stretch' | 'start' | 'center' | 'end';
      gap?: string;
      mobileStacking?: boolean;
      equalHeight?: boolean;
    };
  };
  /** Content-part id, used as the ToC scroll anchor when this block has a title (see tocAnchors.ts). */
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: rows/columns become click-to-edit. Undefined/false in
   *  every other context (public rendering, forms-mode preview, the block picker). */
  editable?: boolean;
  /** This block's identity for inline-field ids (`${blockKey}:cell:R:C`). Only meaningful
   *  — and only ever set — when `editable` is true. */
  blockKey?: string;
  /** `${blockKey}:${fieldPath}` of whichever field across the whole document is the one live
   *  Tiptap-mounted field right now (see BlockPreviewRenderer/useActiveHotspot). */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: typeof props.element): void;
  (e: 'claim-inline-field', field: string | null): void;
}>();

// Parse the JSON content if it's a string (which often happens when coming from the backend)
const displayElement = computed(() => {
  // Create a copy of the element to avoid mutating props
  const result = { ...props.element };

  // Check if json_content is a string (from backend) and try to parse it
  if (typeof result.json_content === 'string') {
    try {
      result.json_content = JSON.parse(result.json_content);
    }
    catch (e) {
      console.error('Failed to parse grid JSON content', e);
      // Provide a fallback empty structure
      result.json_content = [];
    }
  }

  // Check if options is a string and try to parse it
  if (typeof result.options === 'string') {
    try {
      result.options = JSON.parse(result.options);
    }
    catch (e) {
      console.error('Failed to parse grid options', e);
      result.options = {
        gap: 'gap-4',
        mobileStacking: true,
        equalHeight: false,
      };
    }
  }

  return result;
});

// Process options to ensure they're properly accessible
const processedOptions = computed(() => {
  const { options } = displayElement.value;
  const content = displayElement.value.json_content as { options?: typeof options } | null | undefined;

  // If the json_content has a nested options structure, use that instead
  if (content && typeof content === 'object' && content.options && typeof content.options === 'object') {
    return content.options;
  }

  // Otherwise use the top-level options
  return options || {
    gap: 'gap-4',
    mobileStacking: true,
    equalHeight: false,
  };
});

const dividers = computed(() => processedOptions.value.dividers === true);

// Directly use the content as rows, converting from the old nested format if needed
const rows = computed<GridRow[]>(() => {
  const content = displayElement.value.json_content as
    | GridRow[]
    | { json_content?: GridRow[]; rows?: GridRow[] }
    | null
    | undefined;

  // If content is null or undefined, return empty array
  if (!content) {
    return [];
  }

  // If content is already an array (direct format), use it directly
  if (Array.isArray(content)) {
    return content;
  }

  // Handle nested json_content structure from ContentGridEditor
  if (Array.isArray(content.json_content)) {
    return content.json_content;
  }

  // If content has a rows property (old format), use that
  if (Array.isArray(content.rows)) {
    return content.rows;
  }

  // Fallback to empty array
  return [];
});

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}

/** Cheap structural emptiness check — avoids pulling in the HTML renderer just to decide
 *  whether to show a placeholder. Anything beyond a bare `doc`/`paragraph` shell, or any
 *  non-blank text anywhere in the tree, counts as content. */
function hasContent(node: unknown): boolean {
  if (!node || typeof node !== 'object') return false;
  const { type, text, content } = node as { type?: string; text?: string; content?: unknown[] };
  if (typeof text === 'string' && text.trim()) return true;
  if (type && type !== 'doc' && type !== 'paragraph') return true;
  return (content ?? []).some(hasContent);
}

function cellFieldId(rowIndex: number, colIndex: number): string {
  return `${props.blockKey ?? ''}:cell:${rowIndex}:${colIndex}`;
}

function isCellLive(rowIndex: number, colIndex: number): boolean {
  return props.activeInlineField === cellFieldId(rowIndex, colIndex);
}

// A synthetic flat index for RCImageHotspot's hotspot id — unique per grid position
// since a row never has more than MAX_COLUMNS columns, well under the *100 spacing.
function cellFlatIndex(rowIndex: number, colIndex: number): number {
  return rowIndex * 100 + colIndex;
}

const MAX_COLUMNS = 4;

function isMaxColumnsReached(row: GridRow): boolean {
  return row.columns.length >= MAX_COLUMNS;
}

function cardValue(column: GridColumn): GridCardValue {
  const { value } = column.content;
  return (value && typeof value === 'object') ? value as GridCardValue : {};
}

/** `tiptap` cell value is either a TipTap doc object or (legacy) an HTML string. */
function tiptapValue(column: GridColumn): string | Record<string, unknown> | null {
  const { value } = column.content;
  return (typeof value === 'string' || (value && typeof value === 'object')) ? value as string | Record<string, unknown> : null;
}

function imageSrc(column: GridColumn): string {
  return typeof column.content.value === 'string' ? column.content.value : '';
}

function commitRows(newRows: GridRow[]): void {
  emit('update:element', { ...props.element, json_content: newRows });
}

function updateColumnContent(rowIndex: number, colIndex: number, patch: Partial<GridColumnContent>): void {
  commitRows(rows.value.map((row, rIdx) => {
    if (rIdx !== rowIndex) return row;
    return {
      ...row,
      columns: row.columns.map((column, cIdx) => {
        if (cIdx !== colIndex) return column;
        return { ...column, content: { ...column.content, ...patch } };
      }),
    };
  }));
}

function updateCardValue(rowIndex: number, colIndex: number, patch: Partial<GridCardValue>): void {
  const column = rows.value[rowIndex]?.columns[colIndex];
  const currentValue = column ? cardValue(column) : {};
  updateColumnContent(rowIndex, colIndex, { value: { ...currentValue, ...patch } });
}

function replaceColumnImage(rowIndex: number, colIndex: number, image: { src: string; alt: string; title: string }): void {
  updateColumnContent(rowIndex, colIndex, { value: image.src, alt: image.alt, title: image.title });
}

function clearColumnImage(rowIndex: number, colIndex: number): void {
  updateColumnContent(rowIndex, colIndex, { value: '' });
}

function replaceCardImage(rowIndex: number, colIndex: number, image: { src: string; alt: string; title: string }): void {
  updateCardValue(rowIndex, colIndex, { image: image.src, imageAlt: image.alt });
}

function addRow(): void {
  const newRow: GridRow = { columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] };
  const newRows = [...rows.value, newRow];
  commitRows(newRows);
  emit('claim-inline-field', cellFieldId(newRows.length - 1, 0));
}

function removeRow(rowIndex: number): void {
  if (rows.value.length <= 1) return;
  commitRows(rows.value.filter((_, index) => index !== rowIndex));
}

function moveRow(from: number, to: number): void {
  if (to < 0 || to >= rows.value.length) return;
  const newRows = [...rows.value];
  const [row] = newRows.splice(from, 1);
  if (!row) return;
  newRows.splice(to, 0, row);
  commitRows(newRows);
}

/** Even-split column widths whenever the column count changes — matches the widths an
 *  author would pick manually for a balanced row, and avoids leaving a stale width
 *  (e.g. two `col-span-6` columns after a third was added, overflowing the 12-col row). */
function redistributeColumnWidths(columns: GridColumn[]): void {
  const span = { 1: 'col-span-12', 2: 'col-span-6', 3: 'col-span-4', 4: 'col-span-3' }[columns.length];
  if (!span) return;
  columns.forEach((column) => {
    column.width = span;
  });
}

function addColumn(rowIndex: number): void {
  const row = rows.value[rowIndex];
  if (!row || isMaxColumnsReached(row)) return;

  const columns = [...row.columns, { width: 'col-span-6', content: { type: 'tiptap', value: {} } }];
  redistributeColumnWidths(columns);
  const newRows = [...rows.value];
  newRows[rowIndex] = { ...row, columns };
  commitRows(newRows);
}

function removeColumn(rowIndex: number, colIndex: number): void {
  const row = rows.value[rowIndex];
  if (!row || row.columns.length <= 1) return;

  const columns = row.columns.filter((_, index) => index !== colIndex);
  redistributeColumnWidths(columns);
  const newRows = [...rows.value];
  newRows[rowIndex] = { ...row, columns };
  commitRows(newRows);
}

function moveColumn(rowIndex: number, from: number, to: number): void {
  const row = rows.value[rowIndex];
  if (!row || to < 0 || to >= row.columns.length) return;

  const columns = [...row.columns];
  const [column] = columns.splice(from, 1);
  if (!column) return;
  columns.splice(to, 0, column);
  const newRows = [...rows.value];
  newRows[rowIndex] = { ...row, columns };
  commitRows(newRows);
}

function setColumnWidth(rowIndex: number, colIndex: number, width: string): void {
  const row = rows.value[rowIndex];
  const column = row?.columns[colIndex];
  if (!row || !column) return;

  const columns = [...row.columns];
  columns[colIndex] = { ...column, width };
  const newRows = [...rows.value];
  newRows[rowIndex] = { ...row, columns };
  commitRows(newRows);
}

function defaultContentForType(type: string): GridColumnContent {
  if (type === 'image') return { type: 'image', value: '' };
  if (type === 'card') return { type: 'card', value: { image: '', imageAlt: '', title: '', description: '', href: '' } };
  return { type: 'tiptap', value: {} };
}

function setColumnType(rowIndex: number, colIndex: number, type: string): void {
  const row = rows.value[rowIndex];
  const column = row?.columns[colIndex];
  if (!row || !column || column.content.type === type) return;

  const columns = [...row.columns];
  columns[colIndex] = { ...column, content: defaultContentForType(type) };
  const newRows = [...rows.value];
  newRows[rowIndex] = { ...row, columns };
  commitRows(newRows);
}
</script>

<style scoped>
.content-grid :deep(img) {
  max-width: 100%;
  height: auto;
}

.content-grid :deep(.prose) {
  width: 100%;
}
</style>

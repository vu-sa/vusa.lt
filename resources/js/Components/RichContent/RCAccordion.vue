<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow" :band
    :align="element.options?.align ?? 'center'" :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator" inner="content"
    :editable @update:header="updateOptions"
  >
    <div class="relative">
      <Accordion
        :type="editable ? 'multiple' : 'single'" :collapsible="!editable"
        :model-value="editable ? openItemValues : undefined"
        class="space-y-4"
        @update:model-value="onOpenChange"
      >
        <div v-for="(item, index) in element.json_content" :key="index" class="group flex items-start gap-2">
          <!-- `last:border-b` cancels ui/accordion's own `last:border-b-0` (meant for a
               flush, divider-style list) — every item here is its own bordered card, so the
               last one needs the same bottom edge as the rest, not a missing one. -->
          <AccordionItem :value="`item-${index + 1}`"
            class="min-w-0 flex-1 overflow-hidden rounded-lg border border-border bg-card last:border-b">
            <!-- `as="div"` while editable: RCInlineText's contenteditable would otherwise
                 nest inside a native <button>, which steals click focus from the caret.
                 The row itself stays the toggle (same as public) — clicking the label
                 specifically stops propagation so it places the caret instead of also
                 collapsing the item; clicking anywhere else in the row still toggles,
                 using the accordion's own trigger/chevron, not a bespoke control. -->
            <!-- `data-slot="accordion-trigger"` (built into ui/accordion/AccordionTrigger.vue)
                 is the real interactive element — this component's own attrs fall through
                 to its <AccordionHeader> wrapper instead, one level up. -->
            <AccordionTrigger
              :as="editable ? 'div' : 'button'"
              class="px-4 py-3 text-left hover:bg-secondary/60 sm:px-6 sm:py-4 [&[data-state=open]]:bg-secondary/60"
              :class="editable ? 'cursor-pointer' : undefined"
            >
              <span v-if="!editable" class="text-sm font-medium text-foreground sm:text-base">{{ item.label }}</span>
              <RCInlineText v-else
                as="span" class="text-sm font-medium text-foreground sm:text-base"
                :model-value="item.label" :editable :placeholder="$t('rich-content.enter_accordion_title')"
                @click.stop
                @update:model-value="updateItem(index, { ...item, label: $event })"
              />
            </AccordionTrigger>
            <AccordionContent class="px-4 pb-4 pt-2 sm:px-6 sm:pb-6">
              <div
                class="text-muted-foreground mt-3 leading-relaxed text-sm sm:text-base
                  [&_a]:text-brand [&_a]:decoration-brand
                  dark:[&_a]:text-red-400 dark:[&_a]:decoration-red-400
                  [&_a:hover]:text-red-700 dark:[&_a:hover]:text-red-300
                "
              >
                <div v-if="editable && activeInlineField === contentFieldId(index)" data-rc-interactive data-rc-accordion-live-content>
                  <TiptapEditor
                    :model-value="item.content" preset="full" prose-style
                    :placeholder="$t('rich-content.content')"
                    @update:model-value="updateItem(index, { ...item, content: $event })"
                  />
                </div>
                <div v-else-if="editable" data-rc-interactive data-rc-accordion-content @click="$emit('claim-inline-field', contentFieldId(index))">
                  <p v-if="!hasContent(item.content)" class="italic text-muted-foreground/60">
                    {{ $t('rich-content.content') }}
                  </p>
                  <template v-else>
                    <RichContentTiptapHTML v-if="!html" :json_content="item.content" />
                    <div v-else class="rc-prose" v-html="item.html" />
                  </template>
                </div>
                <template v-else>
                  <RichContentTiptapHTML v-if="!html" :json_content="item.content" />
                  <div v-else class="rc-prose" v-html="item.html" />
                </template>
              </div>
            </AccordionContent>
          </AccordionItem>

          <button v-if="editable" type="button"
            :class="[
              'mt-1 flex size-7 shrink-0 items-center justify-center rounded-md text-muted-foreground',
              'opacity-0 transition-opacity group-hover:opacity-100',
              'hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400',
            ]"
            data-rc-interactive
            data-rc-accordion-remove-item
            :aria-label="$t('rich-content.remove_accordion_item')"
            :title="$t('rich-content.remove_accordion_item')"
            @click="removeItem(index)"
          >
            <IFluentDelete24Regular class="size-3.5" />
          </button>
        </div>
      </Accordion>

      <RCAddPlaceholder
        v-if="editable"
        :label="$t('rich-content.add_accordion_item')"
        data-rc-accordion-add-item
        class="left-1/2 -bottom-2 -translate-x-1/2 translate-y-full"
        @click="addItem"
      />
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCSection from './RCSection.vue';
import RCInlineText from './Editor/Fullscreen/RCInlineText.vue';
import RCAddPlaceholder from './Editor/Fullscreen/RCAddPlaceholder.vue';
import type { BandResolution } from './bandLayout';

import type { ShadcnAccordion } from '@/Types/contentParts';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';

const RichContentTiptapHTML = defineAsyncComponent(() => import('./RichContentTiptapHTML.vue'));
// Lazy-loaded: TiptapEditor (and its extension set) is only needed while a field is
// actually being edited in the full-screen editor. A static import would bundle it into
// every public page that renders an accordion, which never mounts this branch at all.
const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

type AccordionItemData = ShadcnAccordion['json_content'][number];

const props = defineProps<{
  element: ShadcnAccordion;
  html?: boolean;
  /** Content-part id, used as the ToC scroll anchor when this block has a title (see tocAnchors.ts). */
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: every item's label/content becomes click-to-edit. Undefined/false
   *  in every other context (public rendering, forms-mode preview, the block picker). */
  editable?: boolean;
  /** This block's identity for inline-field ids (`${blockKey}:content:N`). Only meaningful —
   *  and only ever set — when `editable` is true. */
  blockKey?: string;
  /** `${blockKey}:${fieldPath}` of whichever field across the whole document is the one live
   *  Tiptap-mounted field right now (see BlockPreviewRenderer/useActiveHotspot). */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: ShadcnAccordion): void;
  (e: 'claim-inline-field', field: string | null): void;
}>();

// Independent of `activeInlineField` — which item is *open* has nothing to do with which
// item's content field is *live*. Starts closed, same as the public accordion — nothing
// is forced open; `addItem()` below is the one deliberate exception (a freshly created
// item opens itself so there's something to click into).
const openIndices = ref<Set<number>>(new Set());
const openItemValues = computed(() => [...openIndices.value].sort((a, b) => a - b).map(index => `item-${index + 1}`));

function onOpenChange(value: string | string[] | undefined): void {
  if (!props.editable) return;

  const values = Array.isArray(value) ? value : (value ? [value] : []);
  openIndices.value = new Set(values.map(entry => Number(entry.replace('item-', '')) - 1));
}

function contentFieldId(index: number): string {
  return `${props.blockKey ?? ''}:content:${index}`;
}

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

function updateItem(index: number, value: AccordionItemData): void {
  const next = [...props.element.json_content];
  next[index] = value;
  emit('update:element', { ...props.element, json_content: next });
}

function removeItem(index: number): void {
  emit('update:element', { ...props.element, json_content: props.element.json_content.filter((_, itemIndex) => itemIndex !== index) });

  const nextOpen = new Set<number>();
  openIndices.value.forEach((openIndex) => {
    if (openIndex < index) nextOpen.add(openIndex);
    else if (openIndex > index) nextOpen.add(openIndex - 1);
  });
  openIndices.value = nextOpen;

  // The removed item may have owned the live field — never leave a stale claim pointing
  // at whatever now occupies its old index after the shift.
  emit('claim-inline-field', null);
}

function addItem(): void {
  const newIndex = props.element.json_content.length;
  const next: AccordionItemData = { label: '', content: { type: 'doc', content: [] } as AccordionItemData['content'] };
  emit('update:element', { ...props.element, json_content: [...props.element.json_content, next] });
  openIndices.value = new Set([...openIndices.value, newIndex]);
  // Claim the new item's content field immediately — the just-added item has nothing
  // rendered yet to click on, so without this there's no visible way to start typing.
  emit('claim-inline-field', contentFieldId(newIndex));
}
</script>

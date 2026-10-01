<template>
  <component :is="embedded ? 'div' : Dialog" :class="embedded ? 'flex min-h-0 flex-1 flex-col' : undefined" :open="true" @update:open="v => !v && $emit('close')">
    <component :is="embedded ? 'div' : DialogContent"
      :class="embedded ? 'relative min-h-0 flex-1 overflow-hidden border border-border' : 'fixed inset-0 top-0 left-0 block h-[100dvh] w-screen max-w-none overflow-hidden translate-x-0 translate-y-0 gap-0 rounded-none border-0 p-0 sm:max-w-none'"
      :show-close-button="false"
    >
      <component :is="embedded ? 'h2' : DialogTitle" class="sr-only">
        {{ $t('rich-content.fullscreen_editor') }}
      </component>
      <div data-surface="public" class="@container flex h-full min-h-0 flex-col overflow-x-clip bg-background text-foreground font-public">
        <div ref="toolbarPortalRef" data-rc-smart-toolbar-portal />

        <div class="z-40 flex shrink-0 flex-wrap items-center gap-2 border-b border-border bg-background/95 px-3 py-2 backdrop-blur">
          <Button v-if="!embedded" type="button" size="icon" class="size-11" variant="ghost" :title="$t('rich-content.close_fullscreen_editor')" @click="$emit('close')">
            <IFluentDismiss24Regular class="size-4" />
          </Button>
          <Button
            type="button"
            size="icon"
            class="size-11"
            variant="ghost"
            :aria-pressed="isPreviewing"
            :title="isPreviewing ? $t('rich-content.switch_to_edit') : $t('rich-content.switch_to_preview')"
            @click="isPreviewing = !isPreviewing"
          >
            <IFluentEdit24Regular v-if="isPreviewing" class="size-4" />
            <IFluentEye24Regular v-else class="size-4" />
          </Button>
          <ButtonGroup>
            <Button type="button" size="icon" class="size-11" variant="outline" :title="$t('Atšaukti')" :disabled="!history.canUndo" @click="history.undo()">
              <IFluentArrowUndo24Filled class="size-3.5" />
            </Button>
            <Button type="button" size="icon" class="size-11" variant="outline" :title="$t('editor.redo')" :disabled="!history.canRedo" @click="history.redo()">
              <IFluentArrowRedo24Filled class="size-3.5" />
            </Button>
          </ButtonGroup>
          <DarkModeButton size="icon" class="size-11" />
          <div class="ml-auto hidden items-center gap-2 @min-[900px]:flex">
            <span
              :class="[
                'px-2.5 py-1 text-xs font-semibold',
                isPreviewing
                  ? 'bg-muted text-muted-foreground'
                  : 'bg-vusa-red/10 text-vusa-red dark:bg-vusa-red/20',
              ]"
            >
              {{ isPreviewing ? $t('rich-content.preview_mode') : $t('rich-content.edit_mode') }}
            </span>
            <span class="text-xs text-muted-foreground">
              {{ isPreviewing ? $t('rich-content.preview_mode_hint') : $t('rich-content.edit_mode_hint') }}
            </span>
          </div>
          <SpotlightPopover :title="$t('editor.outline')" :description="$t('editor.outline_hint')" :is-dismissed="outlineSpotlight.isDismissed.value" @dismiss="outlineSpotlight.dismiss">
            <Button type="button" variant="outline" class="min-h-11 min-w-11" :title="$t('editor.outline')" :aria-label="$t('editor.outline')" :aria-expanded="outlineOpen" @click="outlineOpen = !outlineOpen; outlineSpotlight.dismiss()">
              <ListTree class="size-4" aria-hidden="true" /><span class="hidden @min-[600px]:inline">{{ $t('editor.outline') }}</span>
            </Button>
          </SpotlightPopover>
          <Button type="button" size="sm" class="ml-auto min-h-11" data-testid="fullscreen-save" :disabled="context?.form.processing || (context && !context.ready.value)" @click="context ? context.save() : $emit('save')">
            <IFluentSave24Regular class="size-4" />
            {{ $t(context?.form.processing ? 'editor.saving' : 'Išsaugoti') }}
          </Button>
        </div>

        <p v-if="context?.error.value" class="border-b border-border px-4 py-2 text-sm text-destructive" role="alert">
          {{ context.error.value }}
        </p>
        <div class="relative flex min-h-0 flex-1">
          <nav v-if="outlineOpen" :aria-label="$t('editor.outline')" class="absolute inset-y-0 left-0 z-40 w-72 max-w-[85vw] overflow-y-auto border-r border-border bg-background p-3 shadow-lg">
            <Button v-for="part in contents ?? []" :key="getBlockKey(part)" type="button" variant="ghost" class="min-h-11 w-full justify-start whitespace-normal text-left" @click="scrollToBlock(getBlockKey(part)); outlineOpen = false">
              {{ getContentType(part.type).label }} · {{ deriveBlockSummary(part) }}
            </Button>
            <Button v-for="anchor in outlineAnchors" :key="anchor.href" type="button" variant="ghost" class="min-h-11 w-full justify-start" @click="scrollToAnchor(anchor.href)">
              {{ anchor.title }}
            </Button>
          </nav>
          <div ref="scrollRoot" data-rc-fullscreen-scroll class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <main ref="canvasRef" class="rc-canvas mx-auto py-20 md:py-28" style="--rc-measure: 44rem">
              <RCFullscreenCanvasGroup v-for="group in groups" :key="getBlockKey(group.element)" v-model:contents="contents" :group :resolved="resolvedByBlockKey" :bands="bandMap" :preview="isPreviewing" @insert="insertAt" @more="openInsertMenuAt" @move="moveBlock" @remove="removeAt" @form="sideBySideContent = $event" />

              <p v-if="(contents?.length ?? 0) === 0 && !isPreviewing" class="py-16 text-center text-sm text-zinc-400">
                {{ $t('rich-content.fullscreen_empty') }}
              </p>

              <!-- Trailing insert affordance: doubles as "add the first block" when the
               document is empty (appendType/insertAt(0) are the same operation on an
               empty array), so no separate empty-state control is needed. Always visible
               (not hover-only) — it's the one spot with no block below it whose hover a
               user could stumble onto to discover the control. -->
              <div v-if="!isPreviewing" class="relative">
                <RCInsertAffordance
                  :quick-add-types
                  always-visible
                  @insert="appendType($event)"
                  @more="openInsertMenuAt(contents?.length ?? 0)"
                />
              </div>
            </main>
          </div>
        </div>
      </div>
    </component>
  </component>

  <!-- "Open in form" escape hatch — the exact same dialog forms mode uses. -->
  <RCSideBySideDialog
    :open="!!sideBySideContent"
    :content="sideBySideContent ?? EMPTY_PART"
    :tenant-id
    @update:open="(val) => { if (!val) sideBySideContent = null; }"
    @update:content="updateSideBySideContent"
  />

  <BlockPickerDialog
    :open="insertMenuAt !== null"
    :insert-label="$t('rich-content.insert_content_block')"
    @update:open="(val) => { if (!val) insertMenuAt = null; }"
    @select="handleInsertFromMenu"
  />
</template>

<script setup lang="ts">
import { computed, inject, provide, ref, watch } from 'vue';
import { ListTree } from 'lucide-vue-next';
import { moveArrayElement } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';

import { CONTENT_EDITOR_CONTEXT } from '../../contentEditorContext';
import { groupContent } from '../../groupContent';
import { collectHeadingIds, normalizeHeadingAnchors } from '../../headingAnchors';
import { extractAnchorLinks, type AnchorablePart } from '../../tocAnchors';
import { deriveBlockSummary } from '../blockSummary';
import BlockPickerDialog from '../../BlockPickerDialog.vue';
import RCInsertAffordance from '../RCInsertAffordance.vue';
import RCSideBySideDialog from '../RCSideBySideDialog.vue';
import { getQuickAddTypes } from '../quickAddTypes';
import { useContentPartPreview } from '../../composables/useContentPartPreview';
import { createContentItem, getContentType, type ContentPart } from '../../Types';
import { resolveBands, type BandResolution } from '../../bandLayout';

import RCFullscreenCanvasGroup from './RCFullscreenCanvasGroup.vue';
import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from './useActiveHotspot';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { SMART_TIPTAP_TOOLBAR_PORTAL_KEY } from '@/Components/TipTap/smartToolbarPortal';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import DarkModeButton from '@/Components/Buttons/DarkModeButton.vue';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import IFluentDismiss24Regular from '~icons/fluent/dismiss24-regular';
import IFluentArrowUndo24Filled from '~icons/fluent/arrow-undo24-filled';
import IFluentArrowRedo24Filled from '~icons/fluent/arrow-redo24-filled';
import IFluentEdit24Regular from '~icons/fluent/edit24-regular';
import IFluentEye24Regular from '~icons/fluent/eye24-regular';
import IFluentSave24Regular from '~icons/fluent/save24-regular';

const props = defineProps<{
  tenantId?: number | null;
  embedded?: boolean;
  /** Structural history stays separate from each text editor's undo history. */
  history: {
    commit: () => void;
    undo: () => void;
    redo: () => void;
    canUndo: boolean;
    canRedo: boolean;
  };
}>();

defineEmits<{
  (e: 'close'): void;
  (e: 'save'): void;
}>();

const contents = defineModel<ContentPart[]>('contents');
const isPreviewing = ref(false);
const context = inject(CONTENT_EDITOR_CONTEXT, null);
const outlineOpen = ref(false);
const outlineSpotlight = useFeatureSpotlight('content-editor-outline-v1');
const groups = computed(() => groupContent(contents.value ?? []));
const outlineAnchors = computed(() => extractAnchorLinks(contents.value as AnchorablePart[]).flatMap(anchor => [anchor, ...anchor.children]));
// Anchors already in the document may be linked to from elsewhere, so a retitle keeps them.
const lockedHeadingIds = collectHeadingIds(contents.value);
watch(contents, value => normalizeHeadingAnchors(value, lockedHeadingIds), { deep: true, immediate: true });
function scrollToAnchor(href: string) {
  canvasRef.value?.querySelector(`[id="${href.slice(1)}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  outlineOpen.value = false;
}
const canvasRef = ref<HTMLElement | null>(null);
const scrollRoot = ref<HTMLElement | null>(null);
const toolbarPortalRef = ref<HTMLElement | null>(null);

provide(ACTIVE_HOTSPOT_KEY, useActiveHotspot());
provide(SMART_TIPTAP_TOOLBAR_PORTAL_KEY, toolbarPortalRef);

const quickAddTypes = computed(getQuickAddTypes);

const EMPTY_PART: ContentPart = { type: 'tiptap', json_content: {} };

function getBlockKey(content: ContentPart): string {
  return String(content.key ?? content.id ?? '');
}

const bandMap = computed<Map<ContentPart, BandResolution>>(() => resolveBands(contents.value ?? []));

function scrollToBlock(blockKey: string): void {
  requestAnimationFrame(() => {
    Array.from(canvasRef.value?.querySelectorAll<HTMLElement>('[data-rc-block-key]') ?? [])
      .find(element => element.dataset.rcBlockKey === blockKey)
      ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
}

function moveBlock(from: number, to: number): void {
  if (!contents.value || to < 0 || to >= contents.value.length) return;
  const movedBlockKey = getBlockKey(contents.value[from]!);
  props.history.commit();
  moveArrayElement(contents.value, from, to);
  scrollToBlock(movedBlockKey);
  requestAnimationFrame(() => props.history.commit());
}

function removeAt(index: number): void {
  if (!contents.value) return;
  props.history.commit();
  contents.value.splice(index, 1);
  requestAnimationFrame(() => props.history.commit());
}

function insertAt(type: string, index: number): void {
  if (!contents.value) return;
  props.history.commit();
  const item = createContentItem(type);
  item.expanded = true;
  contents.value.splice(index, 0, item);
  requestAnimationFrame(() => props.history.commit());
}

function appendType(type: string): void {
  if (!contents.value) return;
  props.history.commit();
  const item = createContentItem(type);
  item.expanded = true;
  contents.value.push(item);
  requestAnimationFrame(() => props.history.commit());
}

const insertMenuAt = ref<number | null>(null);

function openInsertMenuAt(index: number): void {
  insertMenuAt.value = index;
}

function handleInsertFromMenu(type: string): void {
  if (insertMenuAt.value !== null) {
    insertAt(type, insertMenuAt.value);
  }
  insertMenuAt.value = null;
}

const sideBySideContent = ref<ContentPart | null>(null);

function updateSideBySideContent(value: ContentPart): void {
  sideBySideContent.value = value;
  const index = (contents.value ?? []).findIndex(c => getBlockKey(c) === getBlockKey(value));
  if (index !== -1 && contents.value) contents.value[index] = value;
}

// Server-resolved preview data for every resolvable block — the full-screen editor has
// no "preview all" toggle to gate this behind, it always shows the real thing. Keyed by
// `getBlockKey()` (saved id or the block's generated client-side key), NOT `content.id`
// — a *saved* id would leave a just-added or still-unsaved block (link-list, event-list,
// news, calendar) with no dynamic data at all until the page is saved once, which is
// exactly the "preview doesn't show the fetched events" gap this fixes.
const { debouncedFetchPreview } = useContentPartPreview(() => props.tenantId, () => context ? { kind: context.kind, record_id: context.form.id, locale: context.form.lang } : undefined);
const resolvedByBlockKey = ref<Record<string, unknown>>({});

watch(() => [contents.value, props.tenantId, context?.form.lang] as const, async ([currentContents]) => {
  const resolvableParts = (currentContents ?? []).filter(part => !!getContentType(part.type).serverResolved);
  if (resolvableParts.length === 0) {
    resolvedByBlockKey.value = {};
    return;
  }
  const resolved = await debouncedFetchPreview(resolvableParts.map(part => ({
    key: getBlockKey(part),
    type: part.type,
    json_content: part.json_content,
    options: part.options ?? null,
  })));
  // `debouncedFetchPreview` resolves a *superseded* call's promise to `undefined`
  // (vueuse's `useDebounceFn`, `rejectOnCancel: false` by default) rather than the
  // newer call's result — e.g. every keystroke on a toolbar's NumberField re-fires this
  // watcher, cancelling the previous in-flight request. Skip the assignment rather than
  // clobbering already-shown data with `undefined`: the call that actually wins the
  // debounce (the latest one) still resolves normally and updates this ref then.
  if (resolved) {
    resolvedByBlockKey.value = resolved;
  }
}, { deep: true, immediate: true });

</script>

<template>
  <Dialog :open="true" @update:open="v => !v && $emit('close')">
    <DialogContent
      class="fixed inset-0 top-0 left-0 block h-[100dvh] w-screen max-w-none overflow-y-auto overscroll-contain translate-x-0 translate-y-0 gap-0 rounded-none border-0 p-0 sm:max-w-none"
      :show-close-button="false"
    >
      <DialogTitle class="sr-only">
        {{ $t('rich-content.fullscreen_editor') }}
      </DialogTitle>
      <div data-surface="public" class="@container min-h-[100dvh] overflow-x-clip bg-background text-foreground font-public">
        <div class="sticky top-0 z-40 flex items-center gap-2 border-b border-border bg-background/95 px-4 py-2 backdrop-blur">
          <Button size="icon-xs" variant="ghost" :title="$t('rich-content.close_fullscreen_editor')" @click="$emit('close')">
            <IFluentDismiss24Regular class="size-4" />
          </Button>
          <Button
            size="icon-xs"
            variant="ghost"
            :aria-pressed="isPreviewing"
            :title="isPreviewing ? $t('rich-content.switch_to_edit') : $t('rich-content.switch_to_preview')"
            @click="isPreviewing = !isPreviewing"
          >
            <IFluentEdit24Regular v-if="isPreviewing" class="size-4" />
            <IFluentEye24Regular v-else class="size-4" />
          </Button>
          <ButtonGroup>
            <Button size="icon-xs" variant="outline" :disabled="!history.canUndo" @click="history.undo()">
              <IFluentArrowUndo24Filled class="size-3.5" />
            </Button>
            <Button size="icon-xs" variant="outline" :disabled="!history.canRedo" @click="history.redo()">
              <IFluentArrowRedo24Filled class="size-3.5" />
            </Button>
          </ButtonGroup>
          <DarkModeButton size="icon-xs" />
          <div class="ml-auto hidden items-center gap-2 sm:flex">
            <span
              :class="[
                'rounded-full px-2.5 py-1 text-xs font-semibold',
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
          <Button size="sm" @click="$emit('save')">
            <IFluentSave24Regular class="size-4" />
            {{ $t('Išsaugoti') }}
          </Button>
        </div>

        <main class="rc-canvas mx-auto pt-6 md:pt-8" style="--rc-measure: 44rem">
          <div
            v-for="(content, index) in contents ?? []" :key="getBlockKey(content)"
            :class="['relative', blockLayoutClasses(content)]"
          >
            <RCInsertAffordance
              v-if="!isPreviewing && index > 0"
              :quick-add-types
              @insert="insertAt($event, index)"
              @more="openInsertMenuAt(index)"
            />
            <RCFullscreenBlock
              :content
              :resolved="resolvedFor(content)"
              :band="bandMap.get(content)"
              :block-key="getBlockKey(content)"
              :can-move-up="index > 0"
              :can-move-down="(contents?.length ?? 0) > index + 1"
              :can-delete="(contents?.length ?? 0) > 1"
              :preview="isPreviewing"
              @update:content="(val) => { contents![index] = val; }"
              @move-up="moveBlock(index, index - 1)"
              @move-down="moveBlock(index, index + 1)"
              @open-form="sideBySideContent = content"
              @delete="removeAt(index)"
            />
          </div>

          <p v-if="(contents?.length ?? 0) === 0 && !isPreviewing" class="py-16 text-center text-sm text-zinc-400">
            {{ $t('rich-content.fullscreen_empty') }}
          </p>

          <!-- Trailing insert affordance: doubles as "add the first block" when the
               document is empty (appendType/insertAt(0) are the same operation on an
               empty array), so no separate empty-state control is needed. -->
          <div v-if="!isPreviewing" class="relative">
            <RCInsertAffordance
              :quick-add-types
              @insert="appendType($event)"
              @more="openInsertMenuAt(contents?.length ?? 0)"
            />
          </div>
        </main>
      </div>
    </DialogContent>
  </Dialog>

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
/**
 * Full-screen editing mode: authors see the real rendered page, edit text in place, and
 * reach structured fields through contextual popovers anchored to the element they
 * clicked (see `useActiveHotspot.ts`) — no persistent side panel. Additive — the
 * form-based editor (`RichContentEditor.vue`'s default view) stays as the data layer and
 * the "open full form" escape hatch for every type (not just the ones migrated to
 * inline editing — see `RCBlockToolbarShell.vue`'s docblock).
 *
 * `resolveBands(contents)` runs once here, the same way `RichContentParser` runs it for
 * the public page, so alternation here always matches what will actually publish.
 */
import { computed, provide, ref, watch } from 'vue';
import { moveArrayElement } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';

import BlockPickerDialog from '../../BlockPickerDialog.vue';
import RCInsertAffordance from '../RCInsertAffordance.vue';
import RCSideBySideDialog from '../RCSideBySideDialog.vue';
import { blockLayoutClasses } from '../../blockLayout';
import { getQuickAddTypes } from '../quickAddTypes';
import { useContentPartPreview } from '../../composables/useContentPartPreview';
import { createContentItem, getContentType, type ContentPart } from '../../Types';
import { resolveBands, type BandResolution } from '../../bandLayout';

import { ACTIVE_HOTSPOT_KEY, useActiveHotspot } from './useActiveHotspot';
import RCFullscreenBlock from './RCFullscreenBlock.vue';

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
  /** The same undo history hoisted in `RichContentEditor.vue` — keystrokes inside an
   *  inline field are deliberately NOT committed here; Tiptap/the browser's own
   *  contenteditable undo owns those. `commit()` brackets every structural mutation
   *  (insert/move/delete/presentation change) below. */
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

provide(ACTIVE_HOTSPOT_KEY, useActiveHotspot());

const quickAddTypes = computed(getQuickAddTypes);

const EMPTY_PART: ContentPart = { type: 'tiptap', json_content: {} };

function getBlockKey(content: ContentPart): string {
  return String(content.id ?? content.key ?? '');
}

const bandMap = computed<Map<ContentPart, BandResolution>>(() => resolveBands(contents.value ?? []));

function moveBlock(from: number, to: number): void {
  if (!contents.value || to < 0 || to >= contents.value.length) return;
  props.history.commit();
  moveArrayElement(contents.value, from, to);
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
const { debouncedFetchPreview } = useContentPartPreview(() => props.tenantId);
const resolvedByBlockKey = ref<Record<string, unknown>>({});

watch(contents, async (currentContents) => {
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

function resolvedFor(content: ContentPart): unknown {
  if (!getContentType(content.type).serverResolved) return undefined;
  return resolvedByBlockKey.value[getBlockKey(content)];
}
</script>

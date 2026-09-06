<template>
  <Teleport :to="toolbarPortalTarget ?? 'body'" :disabled="toolbarPortalTarget === null">
    <!-- Floating/Sticky Smart Toolbar -->
    <div
      v-show="isVisible && !isClosed"
      ref="toolbarRef"
      data-rc-smart-toolbar
      class="pointer-events-auto fixed z-40 flex flex-nowrap items-center gap-1 rounded-xl border border-border bg-card p-1.5 shadow-lg text-foreground transition-[top,left] duration-150 ease-out"
      :style="toolbarStyle"
      @mousedown.prevent
    >
      <!-- Heading Level Selector -->
      <Select :model-value="currentHeading" @update:model-value="setHeading($event as string)">
        <SelectTrigger size="sm" class="h-8 w-28 text-xs">
          <SelectValue />
        </SelectTrigger>
        <SelectContent data-rc-smart-toolbar-layer>
          <SelectItem value="paragraph">
            {{ $t('rich-content.heading_paragraph') }}
          </SelectItem>
          <SelectItem value="2">
            {{ $t('rich-content.heading_level_2') }}
          </SelectItem>
          <SelectItem value="3">
            {{ $t('rich-content.heading_level_3') }}
          </SelectItem>
          <SelectItem value="4">
            {{ $t('rich-content.heading_level_4') }}
          </SelectItem>
        </SelectContent>
      </Select>

      <Separator orientation="vertical" class="mx-0.5 h-5" />

      <!-- Marks: Bold, Italic, Underline -->
      <TiptapFormattingButtons :editor bubble />

      <Separator orientation="vertical" class="mx-0.5 h-5" />

      <!-- Link button -->
      <TiptapLinkButton :editor @submit="handleLinkSubmit" @document:submit="handleDocumentLinkSubmit">
        <Button size="icon-sm" :variant="editor.isActive('link') ? 'secondary' : 'ghost'" :title="$t('rich-content.link')">
          <IFluentLink24Regular class="size-4" />
        </Button>
      </TiptapLinkButton>

      <Separator orientation="vertical" class="mx-0.5 h-5" />

      <!-- Lists: Bullet & Ordered -->
      <Button
        size="icon-sm"
        :variant="editor.isActive('bulletList') ? 'secondary' : 'ghost'"
        :title="$t('rich-content.bullet_list')"
        @click="editor.chain().focus().toggleBulletList().run()"
      >
        <IFluentTextBulletList20Regular class="size-4" />
      </Button>
      <Button
        size="icon-sm"
        :variant="editor.isActive('orderedList') ? 'secondary' : 'ghost'"
        :title="$t('rich-content.ordered_list')"
        @click="editor.chain().focus().toggleOrderedList().run()"
      >
        <IFluentTextNumberListLtr20Regular class="size-4" />
      </Button>
      <Button
        size="icon-sm"
        :variant="editor.isActive('blockquote') ? 'secondary' : 'ghost'"
        :title="$t('rich-content.blockquote')"
        @click="editor.chain().focus().toggleBlockquote().run()"
      >
        <IFluentTextQuote20Regular class="size-4" />
      </Button>

      <Separator orientation="vertical" class="mx-0.5 h-5" />

      <!-- Image insert — as-child: without it TiptapImageButton renders its own
         button+icon and uses this slotted icon button as its label, so the
         toolbar showed two image icons. -->
      <TiptapImageButton as-child @submit:object="handleImageSubmit">
        <Button size="icon-sm" variant="ghost" :title="$t('rich-content.select_image')">
          <IFluentImage24Regular class="size-4" />
        </Button>
      </TiptapImageButton>

      <!-- Table insert -->
      <Button
        v-if="!editor.isActive('table')"
        size="icon-sm"
        variant="ghost"
        :title="$t('rich-content.insert_table')"
        @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"
      >
        <IFluentTableAdd24Regular class="size-4" />
      </Button>

      <Separator orientation="vertical" class="mx-0.5 h-5" />

      <TiptapSmartToolbarOverflow :editor />

      <!-- Undo / Redo -->
      <Button
        size="icon-sm"
        variant="ghost"
        :disabled="!editor.can().chain().focus().undo().run()"
        :title="$t('rich-content.undo')"
        @click="editor.chain().focus().undo().run()"
      >
        <IFluentArrowUndo20Regular class="size-3.5" />
      </Button>
      <Button
        size="icon-sm"
        variant="ghost"
        :disabled="!editor.can().chain().focus().redo().run()"
        :title="$t('rich-content.redo')"
        @click="editor.chain().focus().redo().run()"
      >
        <IFluentArrowRedo20Regular class="size-3.5" />
      </Button>

      <!-- Close / Minimize button -->
      <Separator orientation="vertical" class="mx-0.5 h-5" />
      <Button
        size="icon-sm"
        variant="ghost"
        class="text-muted-foreground hover:text-foreground"
        :title="$t('rich-content.close_toolbar')"
        @click="isClosed = true"
      >
        <IFluentDismiss12Regular class="size-3.5" />
      </Button>
    </div>

    <!-- Minimized Trigger Pill when closed -->
    <button
      v-show="isVisible && isClosed"
      type="button"
      data-rc-smart-toolbar-trigger
      class="pointer-events-auto fixed z-40 flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1 text-xs font-semibold text-foreground shadow-md transition-all hover:border-brand"
      :style="minimizedStyle"
      @click="isClosed = false"
    >
      <IFluentTextEffects20Regular class="size-3.5 text-brand" />
      <span>{{ $t('rich-content.show_toolbar') }}</span>
    </button>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Editor } from '@tiptap/core';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapFormattingButtons from './TiptapFormattingButtons.vue';
import TiptapLinkButton from './TiptapLinkButton.vue';
import TiptapSmartToolbarOverflow from './TiptapSmartToolbarOverflow.vue';
import TiptapImageButton from './TiptapImageButton.vue';
import { SMART_TIPTAP_TOOLBAR_PORTAL_KEY } from './smartToolbarPortal';

import { Button } from '@/Components/ui/button';
import { Separator } from '@/Components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import IFluentLink24Regular from '~icons/fluent/link-24-regular';
import IFluentTextBulletList20Regular from '~icons/fluent/text-bullet-list-20-regular';
import IFluentTextNumberListLtr20Regular from '~icons/fluent/text-number-list-ltr-20-regular';
import IFluentTextQuote20Regular from '~icons/fluent/text-quote-20-regular';
import IFluentTableAdd24Regular from '~icons/fluent/table-add-24-regular';
import IFluentImage24Regular from '~icons/fluent/image-24-regular';
import IFluentArrowUndo20Regular from '~icons/fluent/arrow-undo-20-regular';
import IFluentArrowRedo20Regular from '~icons/fluent/arrow-redo-20-regular';
import IFluentDismiss12Regular from '~icons/fluent/dismiss-12-regular';
import IFluentTextEffects20Regular from '~icons/fluent/text-effects-20-regular';

const props = withDefaults(defineProps<{
  editor: Editor;
  containerRef?: HTMLElement | null;
  stickyTopOffset?: number;
}>(), {
  containerRef: null,
  stickyTopOffset: 72,
});

const toolbarRef = ref<HTMLElement | null>(null);
const toolbarPortal = inject(SMART_TIPTAP_TOOLBAR_PORTAL_KEY, null);
const toolbarPortalTarget = computed(() => toolbarPortal?.value ?? null);
const isClosed = ref(false);
const isActive = ref(Boolean(props.editor?.isFocused));
const isContainerVisible = ref(true);

const isVisible = computed(() => isActive.value && isContainerVisible.value);

const currentHeading = computed(() => {
  if (props.editor.isActive('heading', { level: 2 })) return '2';
  if (props.editor.isActive('heading', { level: 3 })) return '3';
  if (props.editor.isActive('heading', { level: 4 })) return '4';
  return 'paragraph';
});

function setHeading(val: string): void {
  if (val === 'paragraph') {
    props.editor.chain().focus().setParagraph().run();
  }
  else {
    props.editor.chain().focus().toggleHeading({ level: Number(val) as 2 | 3 | 4 }).run();
  }
}

function handleLinkSubmit(linkData: { url: string; target?: string }): void {
  if (!linkData.url) {
    props.editor.chain().focus().unsetLink().run();
    return;
  }
  props.editor.chain().focus().extendMarkRange('link').setLink({
    href: linkData.url,
    target: linkData.target ?? '_blank',
  }).run();
}

function handleDocumentLinkSubmit(docData: { name: string; url: string }): void {
  props.editor.chain().focus().extendMarkRange('link').setLink({
    href: docData.url,
    target: '_blank',
  }).insertContent(docData.name).run();
}

function handleImageSubmit(image: { src: string; alt?: string }): void {
  if (props.editor.commands.setAccessibleImage) {
    props.editor.chain().focus().setAccessibleImage({
      src: image.src,
      alt: image.alt || '',
    }).run();
  }
  else {
    props.editor.chain().focus().setImage({ src: image.src, alt: image.alt }).run();
  }
}

// Positioning logic: Tracks active paragraph and sticks to top when scrolled past
const positionCoords = ref<{ top: number; left: number }>({ top: 0, left: 0 });

function updateContainerVisibility(): void {
  if (!props.containerRef) {
    isContainerVisible.value = true;
    return;
  }

  const rect = props.containerRef.getBoundingClientRect();
  isContainerVisible.value = rect.bottom > 0 && rect.top < window.innerHeight;
}

function updatePosition(): void {
  if (!props.editor || props.editor.isDestroyed) return;

  updateContainerVisibility();

  if (!isActive.value) {
    return;
  }

  const { selection } = props.editor.state;
  const { $from } = selection;

  try {
    const pos = $from.depth >= 1 ? $from.before(1) : $from.pos;
    const dom = props.editor.view.nodeDOM(pos);
    const nodeEl = dom instanceof HTMLElement ? dom : dom?.parentElement ?? null;

    if (nodeEl) {
      const rect = nodeEl.getBoundingClientRect();

      const toolbarHeight = toolbarRef.value?.offsetHeight || 48;
      // Normal target position: directly above the paragraph
      const targetTop = rect.top - toolbarHeight - 10;
      // Clamped sticky position: stick to top offset if targetTop is above sticky offset
      const clampedTop = Math.max(props.stickyTopOffset, targetTop);

      // Horizontal position: match start of paragraph, bounded by window width
      const maxWidth = window.innerWidth - (toolbarRef.value?.offsetWidth ?? 500) - 16;
      const clampedLeft = Math.min(Math.max(16, rect.left), Math.max(16, maxWidth));

      positionCoords.value = {
        top: clampedTop,
        left: clampedLeft,
      };
    }
  }
  catch {
    // Selection outside bounds
  }
}

const toolbarStyle = computed(() => ({
  top: `${positionCoords.value.top}px`,
  left: `${positionCoords.value.left}px`,
}));

const minimizedStyle = computed(() => ({
  top: `${positionCoords.value.top}px`,
  left: `${positionCoords.value.left}px`,
}));

let animationFrameId: number | null = null;

function onScrollOrResize(): void {
  if (animationFrameId !== null) {
    cancelAnimationFrame(animationFrameId);
  }
  animationFrameId = requestAnimationFrame(() => {
    updatePosition();
    animationFrameId = null;
  });
}

function activateToolbar(): void {
  isActive.value = true;
  updatePosition();
}

function onDocumentPointerDown(event: PointerEvent): void {
  const target = event.target instanceof Element ? event.target : null;
  if (!target) return;

  if (
    props.containerRef?.contains(target)
    || toolbarRef.value?.contains(target)
    || target.closest('[data-rc-smart-toolbar-layer]')
  ) {
    return;
  }

  isActive.value = false;
}

onMounted(() => {
  if (props.editor) {
    props.editor.on('selectionUpdate', updatePosition);
    props.editor.on('transaction', updatePosition);
    props.editor.on('focus', activateToolbar);
  }
  window.addEventListener('scroll', onScrollOrResize, { passive: true });
  window.addEventListener('resize', onScrollOrResize, { passive: true });
  document.addEventListener('scroll', onScrollOrResize, { capture: true, passive: true });
  document.addEventListener('pointerdown', onDocumentPointerDown, true);
  updatePosition();
});

onBeforeUnmount(() => {
  if (props.editor && !props.editor.isDestroyed) {
    props.editor.off('selectionUpdate', updatePosition);
    props.editor.off('transaction', updatePosition);
    props.editor.off('focus', activateToolbar);
  }
  window.removeEventListener('scroll', onScrollOrResize);
  window.removeEventListener('resize', onScrollOrResize);
  document.removeEventListener('scroll', onScrollOrResize, { capture: true });
  document.removeEventListener('pointerdown', onDocumentPointerDown, true);
  if (animationFrameId !== null) {
    cancelAnimationFrame(animationFrameId);
  }
});

watch(() => props.editor, updatePosition);

watch(() => props.containerRef, () => {
  updatePosition();
});
</script>

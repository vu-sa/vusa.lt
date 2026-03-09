<template>
  <div class="tiptap-editor" :class="[`tiptap-editor--${preset}`]">
    <!-- Bubble Menu (for compact and full presets) -->
    <BubbleMenu v-if="editor && preset !== 'minimal'"
      class="flex items-center gap-1 rounded-lg border bg-white p-1 shadow-md dark:bg-zinc-900 dark:border-zinc-700"
      :editor="editor" :tippy-options="{ duration: 50 }">
      <TiptapFormattingButtons v-model:editor="editor" />
      
      <!-- Link controls in bubble menu -->
      <template v-if="preset === 'full'">
        <Separator orientation="vertical" class="h-5 mx-1" />
        <TiptapLinkButton :editor="editor" @submit="handleLinkSubmit" @document:submit="handleDocumentLinkSubmit">
          <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
            <IFluentLink24Regular class="h-4 w-4" />
          </Button>
        </TiptapLinkButton>
        <Button v-if="editor.isActive('link')" variant="ghost" size="sm" class="h-8 w-8 p-0"
          @click="editor?.chain().focus().unsetLink().run()">
          <IFluentLinkDismiss20Filled class="h-4 w-4" />
        </Button>
      </template>
    </BubbleMenu>

    <!-- Toolbar (configurable visibility) -->
    <div v-if="editor && showToolbar"
      class="tiptap-toolbar flex flex-wrap items-center gap-2 rounded-lg border bg-white p-2 dark:bg-zinc-900 dark:border-zinc-700 mb-2">
      <!-- Formatting buttons -->
      <TiptapFormattingButtons v-model:editor="editor" />

      <!-- Link buttons -->
      <ButtonGroup>
        <TiptapLinkButton :editor="editor" @submit="handleLinkSubmit" @document:submit="handleDocumentLinkSubmit">
          <Button size="sm" variant="outline">
            <IFluentLink24Regular />
          </Button>
        </TiptapLinkButton>
        <Button size="sm" variant="outline" :disabled="!editor.isActive('link')"
          @click="editor?.chain().focus().unsetLink().run()">
          <IFluentLinkDismiss20Filled />
        </Button>
      </ButtonGroup>

      <!-- Clear formatting -->
      <Button size="sm" variant="outline" @click="editor?.chain().focus().unsetAllMarks().run()">
        <IFluentClearFormatting20Filled />
      </Button>

      <Separator orientation="vertical" class="h-5" />

      <!-- Headings (compact and full) -->
      <ButtonGroup v-if="preset !== 'minimal'">
        <Button size="sm" :variant="editor.isActive('paragraph') ? 'default' : 'outline'"
          @click="editor?.chain().focus().setParagraph().run()">
          <IFluentTextT24Regular />
        </Button>
        <Button size="sm" :variant="editor.isActive('heading', { level: 2 }) ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
          <TextHeader220Filled />
        </Button>
        <Button v-if="preset === 'full'" size="sm"
          :variant="editor.isActive('heading', { level: 3 }) ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
          <TextHeader320Filled />
        </Button>
      </ButtonGroup>

      <!-- Lists -->
      <ButtonGroup>
        <Button size="sm" :variant="editor.isActive('bulletList') ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleBulletList().run()">
          <IFluentTextBulletListLtr24Filled />
        </Button>
        <Button size="sm" :variant="editor.isActive('orderedList') ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleOrderedList().run()">
          <IFluentTextNumberListLtr24Filled />
        </Button>
      </ButtonGroup>

      <!-- Quote and horizontal rule (full preset) -->
      <template v-if="preset === 'full'">
        <Button size="sm" :variant="editor.isActive('blockquote') ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleBlockquote().run()">
          <IFluentTextQuote24Filled />
        </Button>
        <Button size="sm" variant="outline" @click="editor?.chain().focus().setHorizontalRule().run()">
          <LineHorizontal120Regular />
        </Button>
      </template>

      <Separator orientation="vertical" class="h-5" />

      <!-- Media buttons (compact and full) -->
      <template v-if="preset !== 'minimal'">
        <ButtonGroup>
          <Suspense>
            <TiptapImageButton @submit="attachImage" @submit:object="attachImage">
              <Button size="sm" variant="outline">
                <IFluentImage24Regular />
              </Button>
            </TiptapImageButton>
          </Suspense>
          <TiptapYoutubeButton @submit="(url) => editor?.commands.setYoutubeVideo({ src: url })">
            <Button size="sm" variant="outline">
              <IFluentVideoClip24Regular />
            </Button>
          </TiptapYoutubeButton>
        </ButtonGroup>
      </template>

      <!-- Video button (full preset) -->
      <template v-if="preset === 'full'">
        <TiptapVideoButton :show-modal="showVideoModal" @update:show-modal="showVideoModal = $event"
          @submit="attachVideo">
          <Button size="sm" variant="outline">
            <IFluentVideo24Regular />
          </Button>
        </TiptapVideoButton>
      </template>

      <!-- Table controls (full preset, when in table) -->
      <template v-if="preset === 'full' && !disableTables && editor.isActive('table')">
        <Separator orientation="vertical" class="h-5" />
        <ButtonGroup>
          <Button size="sm" variant="outline" @click="editor?.chain().focus().toggleHeaderRow().run()">
            <IFluentTableFreezeRow24Regular />
          </Button>
          <Button size="sm" variant="outline" @click="editor?.chain().focus().addColumnAfter().run()">
            <IFluentTableInsertColumn24Regular />
          </Button>
          <Button size="sm" variant="outline" @click="editor?.chain().focus().addRowAfter().run()">
            <IFluentTableInsertRow24Regular />
          </Button>
        </ButtonGroup>
        <ButtonGroup>
          <Button size="sm" variant="outline" :disabled="!editor.can().mergeCells()"
            @click="editor?.chain().focus().mergeCells().run()">
            <IFluentTableCellsMerge24Regular />
          </Button>
          <Button size="sm" variant="outline" :disabled="!editor.can().splitCell()"
            @click="editor?.chain().focus().splitCell().run()">
            <IFluentTableCellsSplit24Regular />
          </Button>
        </ButtonGroup>
        <ButtonGroup>
          <Button size="sm" variant="outline" @click="editor?.chain().focus().deleteColumn().run()">
            <IFluentTableDeleteColumn24Regular />
          </Button>
          <Button size="sm" variant="outline" @click="editor?.chain().focus().deleteRow().run()">
            <IFluentTableDeleteRow24Regular />
          </Button>
        </ButtonGroup>
        <Button size="sm" variant="outline" @click="editor?.chain().focus().fixTables().run()">
          <IFluentTableSettings24Regular />
        </Button>
      </template>

      <!-- Insert table button (full preset, when not in table) -->
      <Button v-if="preset === 'full' && !disableTables && !editor.isActive('table')" size="sm" variant="outline"
        @click="editor?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">
        <IFluentTableAdd24Regular />
      </Button>

      <!-- Image controls (full preset, when image is selected) -->
      <template v-if="preset === 'full' && editor.isActive('image')">
        <Separator orientation="vertical" class="h-5" />
        <ButtonGroup>
          <Button size="sm" :variant="isAlignmentActive('left') ? 'default' : 'outline'"
            @click="setAlignment('left')">
            <IFluentTextAlignLeft24Regular />
          </Button>
          <Button size="sm" :variant="isAlignmentActive('center') ? 'default' : 'outline'"
            @click="setAlignment('center')">
            <IFluentTextAlignCenter24Regular />
          </Button>
          <Button size="sm" :variant="isAlignmentActive('right') ? 'default' : 'outline'"
            @click="setAlignment('right')">
            <IFluentTextAlignRight24Regular />
          </Button>
        </ButtonGroup>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button size="sm" variant="outline">
              <IFluentResize20Regular />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="start">
            <DropdownMenuItem @click="setSizePreset('small')">
              {{ $t('Small') }} (300px)
            </DropdownMenuItem>
            <DropdownMenuItem @click="setSizePreset('medium')">
              {{ $t('Medium') }} (500px)
            </DropdownMenuItem>
            <DropdownMenuItem @click="setSizePreset('large')">
              {{ $t('Large') }} (800px)
            </DropdownMenuItem>
            <DropdownMenuItem @click="setSizePreset('full')">
              {{ $t('Full Width') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
        <Button size="sm" variant="outline" @click="openImageAccessibilityDialog">
          <IFluentAccessibility24Regular />
        </Button>
      </template>

      <!-- Undo/Redo -->
      <ButtonGroup class="ml-auto">
        <Button size="sm" variant="outline" :disabled="!editor.can().chain().focus().undo().run()"
          @click="editor?.chain().focus().undo().run()">
          <IFluentArrowUndo20Regular />
        </Button>
        <Button size="sm" variant="outline" :disabled="!editor.can().chain().focus().redo().run()"
          @click="editor?.chain().focus().redo().run()">
          <IFluentArrowRedo20Regular />
        </Button>
      </ButtonGroup>
    </div>

    <!-- Toolbar toggle (optional) -->
    <div v-if="showToolbarToggle && editor" class="flex justify-end mb-1">
      <Button size="sm" variant="ghost" @click="internalShowToolbar = !internalShowToolbar">
        <IFluentSettings16Filled v-if="!internalShowToolbar" class="h-3 w-3" />
        <IFluentSettings16Regular v-else class="h-3 w-3" />
        <span class="ml-1 text-xs">{{ internalShowToolbar ? $t('Hide toolbar') : $t('Show toolbar') }}</span>
      </Button>
    </div>

    <!-- Editor Content -->
    <div class="tiptap-content rounded-md border dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden">
      <EditorContent :editor="editor" />
    </div>

    <!-- Image Accessibility Dialog -->
    <ImageAccessibilityDialog
      v-model:open="showImageDialog"
      :image-data="currentImageData"
      @submit="handleImageAccessibilitySubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { BubbleMenu } from '@tiptap/vue-3/menus';
import { trans as $t } from 'laravel-vue-i18n';

// Import AccessibleImage command type definitions
import './accessible-image-commands.d.ts';

// Extensions
import { type EditorPreset, getExtensionsForPreset } from './extensions/presets';
import { useTiptapFileUpload } from './composables/useTiptapFileUpload';
import { useTiptapImageControls } from './composables/useTiptapImageControls';
import { latinizeId } from '@/Utils/String';

// UI Components
import TiptapFormattingButtons from './TiptapFormattingButtons.vue';
import TiptapImageButton from './TiptapImageButton.vue';
import TiptapLinkButton from './TiptapLinkButton.vue';
import TiptapVideoButton from './TiptapVideoButton.vue';
import TiptapYoutubeButton from './TiptapYoutubeButton.vue';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Separator } from '@/Components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import ImageAccessibilityDialog from './ImageAccessibilityDialog.vue';

// Icons
import IFluentLink24Regular from '~icons/fluent/link24-regular';
import IFluentLinkDismiss20Filled from '~icons/fluent/link-dismiss20-filled';
import IFluentClearFormatting20Filled from '~icons/fluent/clear-formatting20-filled';
import IFluentTextT24Regular from '~icons/fluent/text-t24-regular';
import TextHeader220Filled from '~icons/fluent/text-header-2-20-filled';
import TextHeader320Filled from '~icons/fluent/text-header-3-20-filled';
import IFluentTextBulletListLtr24Filled from '~icons/fluent/text-bullet-list-ltr24-filled';
import IFluentTextNumberListLtr24Filled from '~icons/fluent/text-number-list-ltr24-filled';
import IFluentTextQuote24Filled from '~icons/fluent/text-quote24-filled';
import LineHorizontal120Regular from '~icons/fluent/line-horizontal-1-20-regular';
import IFluentArrowUndo20Regular from '~icons/fluent/arrow-undo20-regular';
import IFluentArrowRedo20Regular from '~icons/fluent/arrow-redo20-regular';
import IFluentTableAdd24Regular from '~icons/fluent/table-add24-regular';
import IFluentTableFreezeRow24Regular from '~icons/fluent/table-freeze-row24-regular';
import IFluentTableInsertColumn24Regular from '~icons/fluent/table-insert-column24-regular';
import IFluentTableInsertRow24Regular from '~icons/fluent/table-insert-row24-regular';
import IFluentTableDeleteColumn24Regular from '~icons/fluent/table-delete-column24-regular';
import IFluentTableDeleteRow24Regular from '~icons/fluent/table-delete-row24-regular';
import IFluentTableCellsMerge24Regular from '~icons/fluent/table-cells-merge24-regular';
import IFluentTableCellsSplit24Regular from '~icons/fluent/table-cells-split24-regular';
import IFluentTableSettings24Regular from '~icons/fluent/table-settings24-regular';
import IFluentSettings16Filled from '~icons/fluent/settings16-filled';
import IFluentSettings16Regular from '~icons/fluent/settings16-regular';
import IFluentTextAlignLeft24Regular from '~icons/fluent/text-align-left24-regular';
import IFluentTextAlignCenter24Regular from '~icons/fluent/text-align-center24-regular';
import IFluentTextAlignRight24Regular from '~icons/fluent/text-align-right24-regular';
import IFluentResize20Regular from '~icons/fluent/resize20-regular';
import IFluentAccessibility24Regular from '~icons/fluent/accessibility24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';
import IFluentVideoClip24Regular from '~icons/fluent/video-clip24-regular';
import IFluentVideo24Regular from '~icons/fluent/video24-regular';

// Styles
import './tiptap-base.css';

const props = withDefaults(defineProps<{
  /** Editor preset: 'minimal' | 'compact' | 'full' */
  preset?: EditorPreset;
  /** Content (JSON object or HTML string) */
  modelValue: string | Record<string, unknown> | null;
  /** Output HTML instead of JSON */
  html?: boolean;
  /** Disable table support (full preset) */
  disableTables?: boolean;
  /** Maximum character count */
  maxCharacters?: number;
  /** Placeholder text */
  placeholder?: string;
  /** Show toolbar toggle button */
  showToolbarToggle?: boolean;
  /** Initial toolbar visibility (when showToolbarToggle is true) */
  toolbarVisible?: boolean;
}>(), {
  preset: 'full',
  html: false,
  disableTables: false,
  showToolbarToggle: false,
  toolbarVisible: true,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | Record<string, unknown> | null];
}>();

// Internal state
const internalShowToolbar = ref(props.toolbarVisible);
const showVideoModal = ref(false);

// Computed toolbar visibility
const showToolbar = computed(() => {
  if (props.showToolbarToggle) {
    return internalShowToolbar.value;
  }
  // Always show toolbar for full preset, hide for minimal
  return props.preset !== 'minimal';
});

// File upload composable
const { handleFileDrop, handleFilePaste, clearPendingUploads } = useTiptapFileUpload();

// Build extensions based on preset
const extensions = getExtensionsForPreset(props.preset, {
  placeholder: props.placeholder ?? $t('rich-content.text_placeholder'),
  maxCharacters: props.maxCharacters ?? null,
  disableTables: props.disableTables,
  onFileDrop: props.preset === 'full' ? handleFileDrop : undefined,
  onFilePaste: props.preset === 'full' ? handleFilePaste : undefined,
});

// Create editor
const editor = useEditor({
  editorProps: {
    attributes: {
      class: 'focus:outline-none px-3 py-2 w-full min-h-[80px]',
    },
  },
  extensions,
  content: props.modelValue ?? '',
  onUpdate: () => {
    if (props.preset === 'full') {
      updateHeadingIds();
    }

    nextTick(() => {
      if (props.html) {
        emit('update:modelValue', editor.value?.getHTML() ?? null);
      } else {
        emit('update:modelValue', editor.value?.getJSON() ?? null);
      }
    });
  },
});

// Heading ID generation for TOC support
function updateHeadingIds() {
  if (!editor.value) return;

  const innerHeadings: { level: number; text: string; id: string }[] = [];
  const transaction = editor.value.state.tr;

  editor.value.state.doc.descendants((node, pos) => {
    if (node.type.name === 'heading') {
      let id = latinizeId(node.textContent);

      let counter = 1;
      while (innerHeadings.some((heading) => heading.id === id)) {
        id = `${latinizeId(node.textContent)}-${counter}`;
        counter++;
      }

      if (node.attrs.id !== id) {
        transaction.setNodeAttribute(pos, 'id', id);
      }

      innerHeadings.push({
        level: node.attrs.level,
        text: node.textContent,
        id,
      });
    }
  });

  transaction.setMeta('addToHistory', false);
  transaction.setMeta('preventUpdate', true);

  editor.value.view.dispatch(transaction);
}

// Link handlers
function handleLinkSubmit(url: string, text?: string) {
  if (!editor.value) return;

  const { from, to } = editor.value.state.selection;
  const hasSelection = from !== to;

  if (hasSelection) {
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run();
  } else if (text) {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="">${text}</a>`).run();
  } else {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="">${url}</a>`).run();
  }
}

function handleDocumentLinkSubmit(url: string, text?: string) {
  if (!editor.value) return;

  const { from, to } = editor.value.state.selection;
  const hasSelection = from !== to;

  if (hasSelection) {
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run();
  } else if (text) {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="archive-document-link plain">${text}</a>`).run();
  } else {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="archive-document-link plain">${url}</a>`).run();
  }
}

// Media handlers
function attachImage(imageData: { src: string; alt?: string; title?: string } | string) {
  if (!editor.value) return;

  if (typeof imageData === 'string') {
    editor.value.chain().focus().setImage({ src: imageData }).run();
  } else {
    editor.value.chain().focus().setImage({
      src: imageData.src,
      alt: imageData.alt || '',
      title: imageData.title || '',
    }).run();
  }
}

function attachVideo(url: string) {
  editor.value?.chain().focus().setVideo(url).run();
  showVideoModal.value = false;
}

// Image controls composable (initialized after editor)
const {
  showImageDialog,
  currentImageData,
  isAlignmentActive,
  setAlignment,
  setSizePreset,
  openAccessibilityDialog: openImageAccessibilityDialog,
  submitAccessibilityChanges: handleImageAccessibilitySubmit,
} = useTiptapImageControls(editor);

// Cleanup
onBeforeUnmount(() => {
  clearPendingUploads();
  editor.value?.destroy();
});
</script>

<style scoped>
.tiptap-editor {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.tiptap-content {
  min-height: 80px;
  max-height: 400px;
  overflow-y: auto;
}

.tiptap-editor--full .tiptap-content {
  min-height: 120px;
}

.tiptap-editor--minimal .tiptap-content {
  min-height: 60px;
}
</style>

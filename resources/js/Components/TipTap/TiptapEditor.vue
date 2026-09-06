<template>
  <div class="tiptap-editor" :class="[`tiptap-editor--${preset}`]">
    <!-- Bubble Menu (for compact and full presets) -->
    <BubbleMenu v-if="editor && preset !== 'minimal'"
      class="flex items-center gap-0.5 rounded-lg border bg-white p-1 shadow-md dark:bg-zinc-900 dark:border-zinc-700"
      :editor plugin-key="textBubbleMenu" :should-show="shouldShowTextBubbleMenu" :options="{ placement: 'top', offset: 8 }"
      @mousedown.prevent>
      <TiptapFormattingButtons v-model:editor="editor" :show-bold="showBold" bubble />

      <!-- Link controls in bubble menu -->
      <template v-if="preset === 'full'">
        <Separator orientation="vertical" class="h-5 mx-0.5" />
        <TiptapLinkButton :editor @submit="handleLinkSubmit" @document:submit="handleDocumentLinkSubmit">
          <Button size="icon-sm" :variant="editor.isActive('link') ? 'secondary' : 'ghost'">
            <IFluentLink24Regular class="h-4 w-4" />
          </Button>
        </TiptapLinkButton>
        <Button v-if="editor.isActive('link')" variant="ghost" size="icon-sm"
          @click="editor?.chain().focus().unsetLink().run()">
          <IFluentLinkDismiss20Filled class="h-4 w-4" />
        </Button>
      </template>
    </BubbleMenu>

    <!-- Toolbar (configurable visibility) -->
    <div v-if="editor && showToolbar"
      class="tiptap-toolbar flex flex-wrap items-center gap-2 rounded-lg border bg-white p-2 dark:bg-zinc-900 dark:border-zinc-700 mb-2">
      <!-- Formatting buttons -->
      <TiptapFormattingButtons v-model:editor="editor" :show-bold="showBold" />

      <!-- Mobile-only toggle for the rest of the toolbar — on small screens the full
           control set doesn't fit above the keyboard, so only bold/italic/underline
           show by default and everything else is one tap away. `sm:hidden` means
           desktop always sees the full toolbar regardless of this state. -->
      <Button size="sm" variant="ghost" class="sm:hidden" data-testid="tiptap-toolbar-mobile-toggle"
        :title="mobileToolbarExpanded ? $t('rich-content.toolbar_less') : $t('rich-content.toolbar_more')"
        @click="mobileToolbarExpanded = !mobileToolbarExpanded">
        <IFluentChevronUp20Regular v-if="mobileToolbarExpanded" />
        <IFluentChevronDown20Regular v-else />
      </Button>

      <div data-testid="tiptap-toolbar-extra" :class="mobileToolbarExpanded ? 'contents' : 'hidden sm:contents'">
        <!-- Link buttons -->
        <ButtonGroup>
          <TiptapLinkButton :editor @submit="handleLinkSubmit" @document:submit="handleDocumentLinkSubmit">
            <Button size="sm" :variant="editor.isActive('link') ? 'default' : 'outline'">
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

        <!-- Headings (compact and full). `full` gets a level dropdown (up to h4) instead
             of the plain paragraph/h2 toggle pair, since it also needs room for size/
             accent below — a level Select scales to more options than a ButtonGroup. -->
        <ButtonGroup v-if="preset === 'compact'">
          <Button size="sm" :variant="editor.isActive('paragraph') ? 'default' : 'outline'"
            @click="editor?.chain().focus().setParagraph().run()">
            <IFluentTextT24Regular />
          </Button>
          <Button size="sm" :variant="editor.isActive('heading', { level: 2 }) ? 'default' : 'outline'"
            @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
            <TextHeader220Filled />
          </Button>
        </ButtonGroup>

        <template v-if="preset === 'full'">
          <Select :model-value="currentHeadingLevel" @update:model-value="setHeadingLevel($event as string)">
            <SelectTrigger size="sm" class="w-[104px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="paragraph">{{ $t('rich-content.heading_paragraph') }}</SelectItem>
              <SelectItem value="2">{{ $t('rich-content.heading_level_2') }}</SelectItem>
              <SelectItem value="3">{{ $t('rich-content.heading_level_3') }}</SelectItem>
              <SelectItem value="4">{{ $t('rich-content.heading_level_4') }}</SelectItem>
            </SelectContent>
          </Select>

          <!-- Heading style: size + color accent. Only meaningful on a heading — the
               trigger stays enabled either way so an author can set a style *then*
               turn the current block into a heading, but the attributes only render
               visually once a heading is actually active. -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button size="sm" variant="outline" :title="$t('rich-content.heading_style')">
                <IFluentTextEffects20Regular />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
              <DropdownMenuLabel class="text-xs font-normal text-zinc-400">
                {{ $t('rich-content.heading_size') }}
              </DropdownMenuLabel>
              <DropdownMenuItem v-for="size in headingSizes" :key="size.value" @click="setHeadingAttr('size', size.value)">
                <IFluentCheckmark12Regular class="mr-2 h-3.5 w-3.5" :class="currentHeadingSize === size.value ? 'opacity-100' : 'opacity-0'" />
                {{ size.label }}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuLabel class="text-xs font-normal text-zinc-400">
                {{ $t('rich-content.heading_accent') }}
              </DropdownMenuLabel>
              <DropdownMenuItem v-for="accent in headingAccents" :key="accent.value" @click="setHeadingAttr('accent', accent.value)">
                <IFluentCheckmark12Regular class="mr-2 h-3.5 w-3.5" :class="currentHeadingAccent === accent.value ? 'opacity-100' : 'opacity-0'" />
                <span v-if="accent.value !== 'none'" class="mr-2 inline-block size-2.5 rounded-full" :class="accent.swatch" />
                {{ accent.label }}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuLabel class="text-xs font-normal text-zinc-400">
                {{ $t('rich-content.heading_spacing') }}
              </DropdownMenuLabel>
              <DropdownMenuItem v-for="spacing in headingSpacings" :key="spacing.value" @click="setHeadingAttr('spacing', spacing.value)">
                <IFluentCheckmark12Regular class="mr-2 h-3.5 w-3.5" :class="currentHeadingSpacing === spacing.value ? 'opacity-100' : 'opacity-0'" />
                {{ spacing.label }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

        </template>

        <!-- Alignment + dot-tag mark: available in `compact` too (not just `full`) —
             content-grid cells and other compact-preset surfaces edit exactly this kind
             of content (e.g. the MembershipPage-style mascot column), so an author needs
             to be able to apply them there, not just view them if they arrived seeded. -->
        <template v-if="preset !== 'minimal'">
          <!-- Alignment — applies to whichever block type (heading or paragraph) has
               focus. Hidden while an image node is selected: it would sit next to the
               image's own alignment control doing something else entirely. -->
          <ButtonGroup v-if="!editor.isActive('image')">
            <Button size="sm" :variant="currentAlign === 'start' ? 'default' : 'outline'" @click="setAlign('start')">
              <IFluentTextAlignLeft24Regular />
            </Button>
            <Button size="sm" :variant="currentAlign === 'center' ? 'default' : 'outline'" @click="setAlign('center')">
              <IFluentTextAlignCenter24Regular />
            </Button>
            <Button size="sm" :variant="currentAlign === 'end' ? 'default' : 'outline'" @click="setAlign('end')">
              <IFluentTextAlignRight24Regular />
            </Button>
          </ButtonGroup>

          <!-- Dot-tag mark (see App/Tiptap/RCTag.php, RCTag.ts) — the MembershipPage-style pill. -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button size="sm" :variant="editor.isActive('rcTag') ? 'default' : 'outline'" :title="$t('rich-content.tag')">
                <IFluentTag24Regular />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
              <DropdownMenuLabel class="text-xs font-normal text-zinc-400">
                {{ $t('rich-content.tag_variant') }}
              </DropdownMenuLabel>
              <div class="flex gap-1 px-2 pb-2">
                <Button size="sm" :variant="tagVariant === 'filled' ? 'default' : 'outline'" @click="tagVariant = 'filled'">
                  {{ $t('rich-content.tag_variant_filled') }}
                </Button>
                <Button size="sm" :variant="tagVariant === 'plain' ? 'default' : 'outline'" @click="tagVariant = 'plain'">
                  {{ $t('rich-content.tag_variant_plain') }}
                </Button>
              </div>
              <DropdownMenuSeparator />
              <DropdownMenuItem v-for="color in tagColors" :key="color.value" @click="applyTag(color.value)">
                <span class="mr-2 inline-block size-2.5 rounded-full" :class="color.swatch" />
                {{ color.label }}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem :disabled="!editor.isActive('rcTag')" @click="editor?.chain().focus().unsetRCTag().run()">
                {{ $t('rich-content.tag_remove') }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </template>

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

        <!-- Media buttons (compact and full) -->
        <template v-if="preset !== 'minimal'">
          <Suspense>
            <TiptapImageButton as-child @submit:object="attachImage">
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
    <div
      class="tiptap-content rounded-md border dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden"
      :class="{ 'tiptap-content--prose': proseStyle }"
    >
      <EditorContent :editor />
    </div>

    <!-- Contextual image controls, rendered next to the selected image -->
    <TiptapImageMenu v-if="preset === 'full'" :editor />
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
import { normalizeContent } from './normalizeContent';

// UI Components
import TiptapFormattingButtons from './TiptapFormattingButtons.vue';
import TiptapImageButton from './TiptapImageButton.vue';
import TiptapLinkButton from './TiptapLinkButton.vue';
import TiptapVideoButton from './TiptapVideoButton.vue';
import TiptapYoutubeButton from './TiptapYoutubeButton.vue';
import TiptapImageMenu from './TiptapImageMenu.vue';
import { shouldShowTextBubbleMenu } from './bubbleMenuVisibility';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Separator } from '@/Components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { latinizeId } from '@/Utils/String';
import type { HeadingAccent, HeadingSize, HeadingSpacing } from './CustomHeading';
import type { RCTagColor, RCTagVariant } from './RCTag';
import type { TextAlignValue } from './TextAlign';

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
import IFluentImage24Regular from '~icons/fluent/image24-regular';
import IFluentVideoClip24Regular from '~icons/fluent/video-clip24-regular';
import IFluentVideo24Regular from '~icons/fluent/video24-regular';
import IFluentTextEffects20Regular from '~icons/fluent/text-effects20-regular';
import IFluentTag24Regular from '~icons/fluent/tag24-regular';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';
import IFluentChevronDown20Regular from '~icons/fluent/chevron-down-20-regular';
import IFluentChevronUp20Regular from '~icons/fluent/chevron-up-20-regular';

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
  /** Keep controls next to a selection instead of reserving space above the editor. */
  toolbar?: 'inline' | 'bubble';
  /** Hide bold where the surrounding component already enforces a bold display style. */
  showBold?: boolean;
  /**
   * Style the editing surface with `.rc-prose-editing` — the same flow/heading-scale
   * rules as the published rich-content output (`.rc-prose`) — so what you type looks
   * like what renders. Default false: comment/note/other non-rich-content callers keep
   * `tiptap-base.css`'s more compact styling unless they opt in.
   */
  proseStyle?: boolean;
}>(), {
  preset: 'full',
  html: false,
  disableTables: false,
  showToolbarToggle: false,
  toolbarVisible: true,
  toolbar: 'inline',
  showBold: true,
  proseStyle: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | Record<string, unknown> | null];
}>();

// Internal state
const internalShowToolbar = ref(props.toolbarVisible);
const showVideoModal = ref(false);
const mobileToolbarExpanded = ref(false);

// Computed toolbar visibility
const showToolbar = computed(() => {
  if (props.toolbar === 'bubble') {
    return false;
  }
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
      class: ['focus:outline-none px-3 py-2 w-full min-h-[80px]', props.proseStyle ? 'rc-prose-editing tracking-normal' : ''].filter(Boolean).join(' '),
    },
  },
  extensions,
  content: normalizeContent(props.modelValue),
  onUpdate: () => {
    if (props.preset === 'full') {
      updateHeadingIds();
    }

    nextTick(() => {
      if (props.html) {
        emit('update:modelValue', editor.value?.getHTML() ?? null);
      }
      else {
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
      while (innerHeadings.some(heading => heading.id === id)) {
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

// Heading level (full preset) — a Select rather than the compact preset's toggle
// buttons, since it needs to scale to 4 options (paragraph + h2/h3/h4) plus the
// size/accent/align controls alongside it.
const currentHeadingLevel = computed(() => {
  if (!editor.value) return 'paragraph';
  for (const level of [2, 3, 4] as const) {
    if (editor.value.isActive('heading', { level })) return String(level);
  }
  return 'paragraph';
});

function setHeadingLevel(value: string) {
  if (!editor.value) return;

  if (value === 'paragraph') {
    editor.value.chain().focus().setParagraph().run();
  }
  else {
    // `setHeading` (not `toggleHeading`) — a Select always sets the target level,
    // it never toggles back to paragraph on a repeat pick.
    editor.value.chain().focus().setHeading({ level: Number(value) as 2 | 3 | 4 }).run();
  }
}

// Heading size + color accent (CustomHeading's own attributes) — `updateAttributes`
// is a no-op unless the current selection is inside a heading, so picking one while
// focused on a paragraph simply does nothing until the block becomes a heading.
const headingSizes: { value: HeadingSize; label: string }[] = [
  { value: 'sm', label: $t('rich-content.heading_size_sm') },
  { value: 'md', label: $t('rich-content.heading_size_md') },
  { value: 'lg', label: $t('rich-content.heading_size_lg') },
  { value: 'xl', label: $t('rich-content.heading_size_xl') },
];

const headingAccents: { value: HeadingAccent; label: string; swatch?: string }[] = [
  { value: 'none', label: $t('rich-content.heading_accent_none') },
  { value: 'red', label: $t('rich-content.colors.red'), swatch: 'bg-red-500' },
  { value: 'yellow', label: $t('rich-content.colors.yellow'), swatch: 'bg-yellow-500' },
  { value: 'zinc', label: $t('rich-content.colors.gray'), swatch: 'bg-zinc-500' },
];

const headingSpacings: { value: HeadingSpacing; label: string }[] = [
  { value: 'default', label: $t('rich-content.heading_spacing_default') },
  { value: 'tight', label: $t('rich-content.heading_spacing_tight') },
  { value: 'loose', label: $t('rich-content.heading_spacing_loose') },
  { value: 'none', label: $t('rich-content.heading_spacing_none') },
];

const currentHeadingSize = computed<HeadingSize | null>(
  () => (editor.value?.getAttributes('heading').size as HeadingSize | undefined) ?? null,
);
const currentHeadingAccent = computed<HeadingAccent>(
  () => (editor.value?.getAttributes('heading').accent as HeadingAccent | undefined) ?? 'none',
);
// `null` (the schema default) means `default` spacing — normalized here so the
// dropdown's checkmark lands on the Default row when no spacing has been set.
const currentHeadingSpacing = computed<HeadingSpacing>(
  () => (editor.value?.getAttributes('heading').spacing as HeadingSpacing | undefined) ?? 'default',
);

function setHeadingAttr(attr: 'size' | 'accent' | 'spacing', value: string) {
  editor.value?.chain().focus().updateAttributes('heading', { [attr]: value }).run();
}

// Alignment — applies to whichever block type (heading or paragraph) currently has
// the selection; both carry the `align` global attribute (see TextAlign.ts).
const currentAlign = computed<TextAlignValue>(() => {
  if (!editor.value) return 'start';
  const type = editor.value.isActive('heading') ? 'heading' : 'paragraph';
  return (editor.value.getAttributes(type).align as TextAlignValue | undefined) ?? 'start';
});

function setAlign(align: TextAlignValue) {
  if (!editor.value) return;
  const type = editor.value.isActive('heading') ? 'heading' : 'paragraph';
  editor.value.chain().focus().updateAttributes(type, { align }).run();
}

// Dot-tag mark (RCTag.ts / App\Tiptap\RCTag) — the MembershipPage-style pill.
const tagVariant = ref<RCTagVariant>('filled');
const tagColors: { value: RCTagColor; label: string; swatch: string }[] = [
  { value: 'zinc', label: $t('rich-content.colors.gray'), swatch: 'bg-zinc-500' },
  { value: 'red', label: $t('rich-content.colors.red'), swatch: 'bg-red-500' },
  { value: 'yellow', label: $t('rich-content.colors.yellow'), swatch: 'bg-yellow-500' },
  { value: 'green', label: $t('rich-content.colors.green'), swatch: 'bg-green-500' },
];

function applyTag(color: RCTagColor) {
  editor.value?.chain().focus().setRCTag({ variant: tagVariant.value, color }).run();
}

// Link handlers
function handleLinkSubmit(url: string, text?: string) {
  if (!editor.value) return;

  const { from, to } = editor.value.state.selection;
  const hasSelection = from !== to;

  if (hasSelection) {
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run();
  }
  else if (text) {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="">${text}</a>`).run();
  }
  else {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="">${url}</a>`).run();
  }
}

function handleDocumentLinkSubmit(url: string, text?: string) {
  if (!editor.value) return;

  const { from, to } = editor.value.state.selection;
  const hasSelection = from !== to;

  if (hasSelection) {
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run();
  }
  else if (text) {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="archive-document-link plain">${text}</a>`).run();
  }
  else {
    editor.value.chain().focus().insertContent(`<a href="${url}" class="archive-document-link plain">${url}</a>`).run();
  }
}

// Media handlers
function attachImage(imageData: { src: string; alt?: string; title?: string } | string) {
  if (!editor.value) return;

  if (typeof imageData === 'string') {
    editor.value.chain().focus().setImage({ src: imageData }).run();
  }
  else {
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

/* Rich-content blocks get more room — a keyhole editing viewport for a full article
   discourages exactly the long-form writing this preset exists to support. */
.tiptap-content--prose {
  max-height: min(70vh, 40rem);
}

.tiptap-editor--full .tiptap-content {
  min-height: 120px;
}

.tiptap-editor--minimal .tiptap-content {
  min-height: 60px;
}
</style>

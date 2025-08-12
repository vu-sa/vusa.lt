///
<reference path="./accessible-image-commands.d.ts" />

<template>
  <div class="modern-tiptap">
    <!-- Enhanced Bubble Menu -->
    <BubbleMenu v-if="editor" :editor>
      <div
        class="modern-bubble-menu flex items-center gap-1 rounded-lg border bg-white p-1 shadow-md dark:bg-zinc-900 dark:border-zinc-700">
        <Button v-for="action in bubbleMenuActions" :key="action.name" :variant="action.isActive ? 'default' : 'ghost'"
          size="sm" class="h-8 w-8 p-0" @click="action.command">
          <component :is="action.icon" class="h-4 w-4" />
        </Button>
      </div>
    </BubbleMenu>

    <!-- Main Toolbar -->
    <div v-if="editor" class="modern-toolbar">
      <div
        class="flex flex-wrap items-center gap-2 rounded-lg border bg-white p-2 dark:bg-zinc-900 dark:border-zinc-700">
        <!-- Text Formatting -->
        <div class="flex items-center gap-1">
          <TooltipProvider>
            <Tooltip v-for="action in formattingActions" :key="action.name">
              <TooltipTrigger as-child>
                <Button :variant="action.isActive ? 'default' : 'ghost'" size="sm" class="h-8 w-8 p-0"
                  @click="action.command">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ action.label }}</p>
                <p v-if="action.shortcut" class="text-xs opacity-60">
                  {{ action.shortcut }}
                </p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <Separator orientation="vertical" class="separator-vertical" />

        <!-- Text Structure -->
        <div class="flex items-center gap-1">
          <TooltipProvider>
            <Tooltip v-for="action in structureActions" :key="action.name">
              <TooltipTrigger as-child>
                <Button :variant="action.isActive ? 'default' : 'ghost'" size="sm" class="h-8 w-8 p-0"
                  @click="action.command">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ action.label }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <Separator orientation="vertical" class="separator-vertical" />

        <!-- Lists and Quotes -->
        <div class="flex items-center gap-1">
          <TooltipProvider>
            <Tooltip v-for="action in listActions" :key="action.name">
              <TooltipTrigger as-child>
                <Button :variant="action.isActive ? 'default' : 'ghost'" size="sm" class="h-8 w-8 p-0"
                  @click="action.command">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ action.label }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <Separator orientation="vertical" class="separator-vertical" />

        <!-- Media and Links -->
        <div class="flex items-center gap-1">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <div>
                  <TiptapImageButton @submit:object="attachImageWithAlt" @submit="attachImageWithAlt">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                      <IFluentImage24Regular class="h-4 w-4" />
                    </Button>
                  </TiptapImageButton>
                </div>
              </TooltipTrigger>
              <TooltipContent>Add Image</TooltipContent>
            </Tooltip>

            <Tooltip>
              <TooltipTrigger as-child>
                <div>
                  <TiptapLinkButton :editor
                    @submit="handleLinkSubmit"
                    @document:submit="handleDocumentLinkSubmit">
                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                      <IFluentLink24Regular class="h-4 w-4" />
                    </Button>
                  </TiptapLinkButton>
                </div>
              </TooltipTrigger>
              <TooltipContent>Add Link</TooltipContent>
            </Tooltip>

            <Tooltip v-if="editor.isActive('link')">
              <TooltipTrigger as-child>
                <Button variant="ghost" size="sm" class="h-8 w-8 p-0"
                  @click="editor?.chain().focus().unsetLink().run()">
                  <IFluentLinkDismiss20Filled class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Remove Link</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <Separator orientation="vertical" class="separator-vertical" />

        <!-- Insert Menu -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 px-3 text-xs">
              Insert
              <IFluentChevronDown16Regular class="ml-1 h-3 w-3" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="start" class="w-48">
            <DropdownMenuItem @click="insertHorizontalRule">
              <LineHorizontal120Regular class="mr-2 h-4 w-4" />
              Horizontal Rule
            </DropdownMenuItem>
            <DropdownMenuItem @click="toggleBlockquote">
              <IFluentTextQuote24Filled class="mr-2 h-4 w-4" />
              Quote Block
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="showVideoModal = true">
              <IFluentVideoClip24Regular class="mr-2 h-4 w-4" />
              Video
            </DropdownMenuItem>
            <DropdownMenuItem @click="showYoutubeModal = true">
              <IFluentVideoClip24Regular class="mr-2 h-4 w-4" />
              YouTube Video
            </DropdownMenuItem>
            <DropdownMenuSeparator v-if="!disableTables" />
            <DropdownMenuItem v-if="!disableTables" @click="insertTable">
              <IFluentTableAdd24Regular class="mr-2 h-4 w-4" />
              Insert Table
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Table Toolbar (only show when in table) -->
        <div v-if="editor.isActive('table')" class="flex items-center gap-1">
          <Separator orientation="vertical" class="separator-vertical" />
          <TooltipProvider>
            <Tooltip v-for="action in tableActions" :key="action.name">
              <TooltipTrigger as-child>
                <Button variant="ghost" size="sm" class="h-8 w-8 p-0"
                  :disabled="action.canExecute !== undefined && !action.canExecute" @click="action.command">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ action.label }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Image Toolbar (only show when image is selected) -->
        <div v-if="editor.isActive('image')" class="flex items-center gap-1">
          <Separator orientation="vertical" class="h-6" />
          <TooltipProvider>
            <!-- Alignment Controls -->
            <Tooltip v-for="action in imageAlignmentActions" :key="action.name">
              <TooltipTrigger as-child>
                <Button :variant="action.isActive ? 'default' : 'ghost'" size="sm" class="h-8 w-8 p-0"
                  @click="action.command">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ action.label }}</p>
              </TooltipContent>
            </Tooltip>

            <!-- Image Size Controls -->
            <DropdownMenu v-model:open="imageResizeMenuOpen">
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="openImageResizeMenu" :disabled="!editor?.isActive('image')">
                  <IFluentResize20Regular class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" class="w-44">
                <DropdownMenuItem @click="selectResizePreset('small')">
                  Small (300px)
                </DropdownMenuItem>
                <DropdownMenuItem @click="selectResizePreset('medium')">
                  Medium (500px)
                </DropdownMenuItem>
                <DropdownMenuItem @click="selectResizePreset('large')">
                  Large (800px)
                </DropdownMenuItem>
                <DropdownMenuItem @click="selectResizePreset('full')">
                  Full Width
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <!-- Edit Image Alt/Title -->
            <Tooltip>
              <TooltipTrigger as-child>
                <Button variant="ghost" size="sm" class="h-8 w-8 p-0" @click="editImageAccessibility">
                  <IFluentAccessibility24Regular class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Edit Alt Text</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <Separator orientation="vertical" class="separator-vertical" />

        <!-- History -->
        <div class="flex items-center gap-1">
          <TooltipProvider>
            <Tooltip v-for="action in historyActions" :key="action.name">
              <TooltipTrigger as-child>
                <Button variant="ghost" size="sm" class="h-8 w-8 p-0" :disabled="!action.canExecute"
                  @click="action.command">
                  <component :is="action.icon" class="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{{ action.label }}</p>
                <p v-if="action.shortcut" class="text-xs opacity-60">
                  {{ action.shortcut }}
                </p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <!-- Format Menu -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 px-3 text-xs">
              Format
              <IFluentChevronDown16Regular class="ml-1 h-3 w-3" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-48">
            <DropdownMenuItem @click="clearFormatting">
              <IFluentClearFormatting20Filled class="mr-2 h-4 w-4" />
              Clear Formatting
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Editor Content -->
    <div class="modern-editor-content">
      <EditorContent :editor class="typography" />
    </div>
    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
      Galite vilkti ir mesti nuotraukas tiesiai į teksto redaktorių
    </div>

    <!-- Image Accessibility Dialog -->
    <ImageAccessibilityDialog v-model:open="showImageDialog" :image-data="currentImageData"
      @submit="handleImageAccessibilitySubmit" />

    <!-- YouTube Modal -->
    <Dialog :open="showYoutubeModal" @update:open="showYoutubeModal = $event">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Įkelti YouTube filmuką</DialogTitle>
          <DialogDescription>
            Įveskite YouTube vaizdo įrašo nuorodą, kurią norite įterpti.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="youtube-url">YouTube nuoroda</Label>
            <Input id="youtube-url" v-model="youtubeUrl" placeholder="https://www.youtube.com/watch?v=..." type="url" />
            <p class="text-xs text-muted-foreground">
              Įklijuokite YouTube vaizdo įrašo nuorodą
            </p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showYoutubeModal = false">
            Atšaukti
          </Button>
          <Button :disabled="!youtubeUrl.trim()" @click="handleYoutubeSubmit(youtubeUrl)">
            Įkelti
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Video Upload Modal -->
    <TiptapVideoButton :show-modal="showVideoModal" @update:show-modal="showVideoModal = $event"
      @submit="handleVideoSubmit" />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref } from "vue";
import { router } from '@inertiajs/vue3';
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { BubbleMenu } from "@tiptap/vue-3/menus";
import { CharacterCount, Placeholder } from "@tiptap/extensions";
import { FileHandler } from "@tiptap/extension-file-handler";
import { StarterKit } from "@tiptap/starter-kit";
import { TableKit } from '@tiptap/extension-table';
import { Youtube } from "@tiptap/extension-youtube";
import { Subscript } from '@tiptap/extension-subscript';
import { Superscript } from '@tiptap/extension-superscript';
import latinize from "latinize";
import { trans as $t } from "laravel-vue-i18n";

import { AccessibleImage } from "./AccessibleImage";
import './accessible-image-commands.d.ts'; // Import command type definitions
import { CustomHeading } from "./CustomHeading";
import { Video } from './Video';
// UI Components
import TiptapImageButton from "./TiptapImageButton.vue";
import TiptapLinkButton from "./TiptapLinkButton.vue";
import TiptapVideoButton from "./TiptapVideoButton.vue";
import ImageAccessibilityDialog from "./ImageAccessibilityDialog.vue";

import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger
} from "@/Components/ui/tooltip";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from "@/Components/ui/dialog";
import { useToasts } from '@/Composables/useToasts';
// Icons
import IFluentTextBold20Regular from "~icons/fluent/text-bold20-regular";
import IFluentTextItalic20Regular from "~icons/fluent/text-italic20-regular";
import IFluentTextUnderline20Regular from "~icons/fluent/text-underline20-regular";
import IFluentTextSubscript20Regular from "~icons/fluent/text-subscript20-regular";
import IFluentTextSuperscript20Regular from "~icons/fluent/text-superscript20-regular";
import TextHeader220Filled from "~icons/fluent/text-header-2-20-filled";
import TextHeader320Filled from "~icons/fluent/text-header-3-20-filled";
import IFluentTextT24Regular from "~icons/fluent/text-t24-regular";
import IFluentTextBulletListLtr24Filled from "~icons/fluent/text-bullet-list-ltr24-filled";
import IFluentTextNumberListLtr24Filled from "~icons/fluent/text-number-list-ltr24-filled";
import IFluentTextQuote24Filled from "~icons/fluent/text-quote24-filled";
import IFluentImage24Regular from "~icons/fluent/image24-regular";
import IFluentLink24Regular from "~icons/fluent/link24-regular";
import IFluentLinkDismiss20Filled from "~icons/fluent/link-dismiss20-filled";
import IFluentArrowUndo20Regular from "~icons/fluent/arrow-undo20-regular";
import IFluentArrowRedo20Regular from "~icons/fluent/arrow-redo20-regular";
import IFluentChevronDown16Regular from "~icons/fluent/chevron-down16-regular";
import IFluentClearFormatting20Filled from "~icons/fluent/clear-formatting20-filled";
import LineHorizontal120Regular from "~icons/fluent/line-horizontal-1-20-regular";
import IFluentVideoClip24Regular from "~icons/fluent/video-clip24-regular";
// Table Icons
import IFluentTableAdd24Regular from "~icons/fluent/table-add24-regular";
import IFluentTableFreezeRow24Regular from "~icons/fluent/table-freeze-row24-regular";
import IFluentTableInsertColumn24Regular from "~icons/fluent/table-insert-column24-regular";
import IFluentTableInsertRow24Regular from "~icons/fluent/table-insert-row24-regular";
import IFluentTableDeleteColumn24Regular from "~icons/fluent/table-delete-column24-regular";
import IFluentTableDeleteRow24Regular from "~icons/fluent/table-delete-row24-regular";
import IFluentTableDismiss24Regular from "~icons/fluent/table-dismiss24-regular";
import IFluentTableCellsMerge24Regular from "~icons/fluent/table-cells-merge24-regular";
import IFluentTableCellsSplit24Regular from "~icons/fluent/table-cells-split24-regular";
import IFluentTableSettings24Regular from "~icons/fluent/table-settings24-regular";
// Image Icons
import IFluentTextAlignLeft24Regular from "~icons/fluent/text-align-left24-regular";
import IFluentTextAlignCenter24Regular from "~icons/fluent/text-align-center24-regular";
import IFluentTextAlignRight24Regular from "~icons/fluent/text-align-right24-regular";
import IFluentResize20Regular from "~icons/fluent/resize20-regular";
import IFluentAccessibility24Regular from "~icons/fluent/accessibility24-regular";

const props = defineProps<{
  disableTables?: boolean;
  maxCharacters?: number;
  html?: boolean;
  modelValue: string | Record<string, unknown> | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string | Record<string, unknown> | null];
}>();
const modelValue = ref(props.modelValue);

const toasts = useToasts();

// Upload state management
const uploadingFiles = ref(new Map<string, { fileName: string; placeholder: any }>());

// Image accessibility dialog state
const showImageDialog = ref(false);
const currentImageData = ref({ src: '', alt: '', title: '' });

// Modal states for insert menu
const showVideoModal = ref(false);
const showYoutubeModal = ref(false);
const youtubeUrl = ref('');

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "focus:outline-none px-4 py-3 w-full min-h-[120px]",
    },
    handleDOMEvents: {
      focusin: (view, event) => {
        // Remove aria-hidden from ancestors when editor gains focus to fix accessibility issue
        let element = event.target as HTMLElement | null;
        while (element) {
          if (element.getAttribute('aria-hidden') === 'true') {
            element.setAttribute('data-was-aria-hidden', 'true');
            element.removeAttribute('aria-hidden');
          }
          element = element.parentElement;
        }
        return false;
      },
      focusout: (view, event) => {
        // Restore aria-hidden when editor loses focus
        let element = event.target as HTMLElement | null;
        while (element) {
          if (element.getAttribute('data-was-aria-hidden') === 'true') {
            element.setAttribute('aria-hidden', 'true');
            element.removeAttribute('data-was-aria-hidden');
          }
          element = element.parentElement;
        }
        return false;
      },
    },
  },
  extensions: [
    StarterKit.configure({
      heading: false,
      codeBlock: false,
      link: {
        openOnClick: false,
        HTMLAttributes: {
          class: "text-blue-500 underline",
        },
      },
    }),
    CharacterCount.configure({
      limit: props.maxCharacters ?? null,
    }),
    CustomHeading.configure({
      levels: [2, 3],
    }),
  // Text formatting marks (order normally doesn't matter but keep core marks grouped)
  Subscript,
  Superscript,
    Placeholder.configure({
      placeholder: $t ? $t('rich-content.text_placeholder') : "Type something...",
    }),
    AccessibleImage.configure({
      HTMLAttributes: {
        class: "max-w-full h-auto rounded-md",
        loading: "lazy",
      },
      allowBase64: true,
    }),
    TableKit.configure({
      table: { resizable: true },
    }),
    Video,
    Youtube.configure({
      HTMLAttributes: {
        class: "aspect-video h-36 w-auto my-2",
      },
    }),
    FileHandler.configure({
      allowedMimeTypes: [
        // Images
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        // Text files
        'text/plain', 'text/csv',
        // Archives
        'application/zip', 'application/x-rar-compressed',
        // Web files
        'text/html', 'text/css', 'text/javascript', 'application/javascript', 'application/json', 'text/xml', 'application/xml',
        // Audio/Video
        'audio/mpeg', 'video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/webm'
      ],
      onDrop: (currentEditor, files, pos) => {
        handleFileDrop(currentEditor, files, pos);
      },
      onPaste: (currentEditor, files, htmlContent) => {
        handleFilePaste(currentEditor, files);
      }
    }),
  ],
  content: modelValue.value ?? "",
  onCreate: ({ editor }) => {
  // editor initialized
  },
  onUpdate: () => {
    handleUpdate();
    nextTick(() => {
      if (props.html) {
        emit("update:modelValue", editor.value?.getHTML());
      } else {
        emit("update:modelValue", editor.value?.getJSON());
      }
    });
  },
});

// Action definitions
const formattingActions = computed(() => [
  {
    name: 'bold',
    label: 'Bold',
    shortcut: 'Ctrl+B',
    icon: IFluentTextBold20Regular,
    isActive: editor.value?.isActive('bold') ?? false,
    command: () => editor.value?.chain().focus().toggleBold().run(),
  },
  {
    name: 'italic',
    label: 'Italic',
    shortcut: 'Ctrl+I',
    icon: IFluentTextItalic20Regular,
    isActive: editor.value?.isActive('italic') ?? false,
    command: () => editor.value?.chain().focus().toggleItalic().run(),
  },
  {
    name: 'underline',
    label: 'Underline',
    shortcut: 'Ctrl+U',
    icon: IFluentTextUnderline20Regular,
    isActive: editor.value?.isActive('underline') ?? false,
    command: () => editor.value?.chain().focus().toggleUnderline().run(),
  },
  {
    name: 'subscript',
    label: 'Subscript',
    icon: IFluentTextSubscript20Regular,
    isActive: editor.value?.isActive('subscript') ?? false,
    command: () => editor.value?.chain().focus().toggleSubscript().run(),
  },
  {
    name: 'superscript',
    label: 'Superscript',
    icon: IFluentTextSuperscript20Regular,
    isActive: editor.value?.isActive('superscript') ?? false,
    command: () => editor.value?.chain().focus().toggleSuperscript().run(),
  },
]);

const structureActions = computed(() => [
  {
    name: 'paragraph',
    label: 'Paragraph',
    icon: IFluentTextT24Regular,
    isActive: editor.value?.isActive('paragraph') ?? false,
    command: () => editor.value?.chain().focus().setParagraph().run(),
  },
  {
    name: 'heading2',
    label: 'Heading 2',
    icon: TextHeader220Filled,
    isActive: editor.value?.isActive('heading', { level: 2 }) ?? false,
    command: () => editor.value?.chain().focus().toggleHeading({ level: 2 }).run(),
  },
  {
    name: 'heading3',
    label: 'Heading 3',
    icon: TextHeader320Filled,
    isActive: editor.value?.isActive('heading', { level: 3 }) ?? false,
    command: () => editor.value?.chain().focus().toggleHeading({ level: 3 }).run(),
  },
]);

const listActions = computed(() => [
  {
    name: 'bulletList',
    label: 'Bullet List',
    icon: IFluentTextBulletListLtr24Filled,
    isActive: editor.value?.isActive('bulletList') ?? false,
    command: () => editor.value?.chain().focus().toggleBulletList().run(),
  },
  {
    name: 'orderedList',
    label: 'Numbered List',
    icon: IFluentTextNumberListLtr24Filled,
    isActive: editor.value?.isActive('orderedList') ?? false,
    command: () => editor.value?.chain().focus().toggleOrderedList().run(),
  },
]);

const bubbleMenuActions = computed(() => [
  ...formattingActions.value,
]);

const historyActions = computed(() => [
  {
    name: 'undo',
    label: 'Undo',
    shortcut: 'Ctrl+Z',
    icon: IFluentArrowUndo20Regular,
    canExecute: editor.value?.can().chain().focus().undo().run() ?? false,
    command: () => editor.value?.chain().focus().undo().run(),
  },
  {
    name: 'redo',
    label: 'Redo',
    shortcut: 'Ctrl+Y',
    icon: IFluentArrowRedo20Regular,
    canExecute: editor.value?.can().chain().focus().redo().run() ?? false,
    command: () => editor.value?.chain().focus().redo().run(),
  },
]);

const tableActions = computed(() => [
  {
    name: 'toggleHeaderRow',
    label: 'Toggle Header Row',
    icon: IFluentTableFreezeRow24Regular,
    command: () => editor.value?.chain().focus().toggleHeaderRow().run(),
  },
  {
    name: 'addColumnAfter',
    label: 'Add Column After',
    icon: IFluentTableInsertColumn24Regular,
    command: () => editor.value?.chain().focus().addColumnAfter().run(),
  },
  {
    name: 'addRowAfter',
    label: 'Add Row After',
    icon: IFluentTableInsertRow24Regular,
    command: () => editor.value?.chain().focus().addRowAfter().run(),
  },
  {
    name: 'mergeCells',
    label: 'Merge Cells',
    icon: IFluentTableCellsMerge24Regular,
    command: () => editor.value?.chain().focus().mergeCells().run(),
    canExecute: editor.value?.can().mergeCells() ?? false,
  },
  {
    name: 'splitCell',
    label: 'Split Cell',
    icon: IFluentTableCellsSplit24Regular,
    command: () => editor.value?.chain().focus().splitCell().run(),
    canExecute: editor.value?.can().splitCell() ?? false,
  },
  {
    name: 'deleteColumn',
    label: 'Delete Column',
    icon: IFluentTableDeleteColumn24Regular,
    command: () => editor.value?.chain().focus().deleteColumn().run(),
  },
  {
    name: 'deleteRow',
    label: 'Delete Row',
    icon: IFluentTableDeleteRow24Regular,
    command: () => editor.value?.chain().focus().deleteRow().run(),
  },
  {
    name: 'fixTables',
    label: 'Fix Table Structure',
    icon: IFluentTableSettings24Regular,
    command: () => editor.value?.chain().focus().fixTables().run(),
  },
  {
    name: 'deleteTable',
    label: 'Delete Table',
    icon: IFluentTableDismiss24Regular,
    command: () => editor.value?.chain().focus().deleteTable().run(),
  },
]);

// Image alignment actions
const imageAlignmentActions = computed(() => {
  const currentAttributes = editor.value?.getAttributes('image') || {};
  const currentAlign = currentAttributes.align || 'center';

  return [
    {
      name: 'alignLeft',
      label: 'Align Left',
      icon: IFluentTextAlignLeft24Regular,
      isActive: currentAlign === 'left',
      command: () => editor.value?.chain().focus().updateImageAlignment('left').run(),
    },
    {
      name: 'alignCenter',
      label: 'Align Center',
      icon: IFluentTextAlignCenter24Regular,
      isActive: currentAlign === 'center',
      command: () => editor.value?.chain().focus().updateImageAlignment('center').run(),
    },
    {
      name: 'alignRight',
      label: 'Align Right',
      icon: IFluentTextAlignRight24Regular,
      isActive: currentAlign === 'right',
      command: () => editor.value?.chain().focus().updateImageAlignment('right').run(),
    },
  ];
});

// Command functions
const insertHorizontalRule = () => editor.value?.chain().focus().setHorizontalRule().run();
const toggleBlockquote = () => {
  if (!editor.value) return;
  // Ensure selection is expanded to paragraph if empty to allow applying blockquote
  const chain = editor.value.chain().focus();
  chain.toggleBlockquote().run();
};
const insertTable = () => editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
const clearFormatting = () => editor.value?.chain().focus().unsetAllMarks().run();

function attachVideoUrl(url: string) {
  editor.value?.chain().focus().setVideo(url).run();
}


function handleYoutubeSubmit(url: string) {
  editor.value?.commands.setYoutubeVideo({ src: url });
  showYoutubeModal.value = false;
  youtubeUrl.value = '';
}

function handleVideoSubmit(url: string) {
  attachVideoUrl(url);
  showVideoModal.value = false;
}

function handleLinkSubmit(url: string, text?: string) {
  if (!editor.value) return;
  
  const { from, to } = editor.value.state.selection;
  const hasSelection = from !== to;
  
  if (hasSelection) {
    // Replace selected text with link
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run();
  } else if (text) {
    // Insert new link with provided text
    editor.value.chain().focus().insertContent(`<a href="${url}" class="">${text}</a>`).run();
  } else {
    // Insert link with URL as text
    editor.value.chain().focus().insertContent(`<a href="${url}" class="">${url}</a>`).run();
  }
}

function handleDocumentLinkSubmit(url: string, text?: string) {
  if (!editor.value) return;
  
  const { from, to } = editor.value.state.selection;
  const hasSelection = from !== to;
  
  if (hasSelection) {
    // Replace selected text with document link
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run();
  } else if (text) {
    // Insert new document link with provided text
    editor.value.chain().focus().insertContent(`<a href="${url}" class="archive-document-link plain">${text}</a>`).run();
  } else {
    // Insert document link with URL as text
    editor.value.chain().focus().insertContent(`<a href="${url}" class="archive-document-link plain">${url}</a>`).run();
  }
}

function attachImageWithAlt(imageData: { src: string; alt?: string; title?: string } | string) {
  if (typeof imageData === 'string') {
    editor.value?.chain().focus().setImageWithAlt({
      src: imageData,
      alt: '',
      title: '',
      align: 'center'
    }).run();
  } else {
    editor.value?.chain().focus().setImageWithAlt({
      src: imageData.src,
      alt: imageData.alt || '',
      title: imageData.title || '',
      align: 'center'
    }).run();
  }
}

// Image control functions
function resizeImage(size: string) {

  const sizeMap = {
    small: { width: '300px' },
    medium: { width: '500px' },
    large: { width: '800px' },
    full: { width: '100%' },
  };

  type SizeKey = keyof typeof sizeMap;
  const dimensions = sizeMap[size as SizeKey];

  if (dimensions && editor.value) {
    // Use updateAttributes directly since it should work
  editor.value.chain().focus().updateAttributes('image', dimensions).run();
  }
}

// Controlled image resize dropdown state
const imageResizeMenuOpen = ref(false);

function openImageResizeMenu() {
  if (!editor.value?.isActive('image')) return; // only open when image selected
  imageResizeMenuOpen.value = true;
}

function selectResizePreset(size: string) {
  resizeImage(size);
  imageResizeMenuOpen.value = false;
}

function editImageAccessibility() {
  const currentAttributes = editor.value?.getAttributes('image') || {};
  currentImageData.value = {
    src: currentAttributes.src || '',
    alt: currentAttributes.alt || '',
    title: currentAttributes.title || '',
  };
  showImageDialog.value = true;
}

function handleImageAccessibilitySubmit(data: { alt: string; title: string }) {
  editor.value?.chain().focus().updateImageAlt(data.alt).run();
  editor.value?.chain().focus().updateImageTitle(data.title).run();
}

// File handling functions (keeping original implementation)
async function handleFileDrop(currentEditor: any, files: File[], pos?: number) {
  for (const file of files) {
    if (file.type.startsWith('image/')) {
      await processImageUpload(currentEditor, file, pos);
    } else {
      await processFileUpload(currentEditor, file, pos);
    }
  }
}

async function handleFilePaste(currentEditor: any, files: File[]) {
  for (const file of files) {
    if (file.type.startsWith('image/')) {
      await processImageUpload(currentEditor, file);
    } else {
      await processFileUpload(currentEditor, file);
    }
  }
}

interface UploadResult {
  url: string;
  name: string;
  originalSize: number;
  compressedSize: number;
  compressionRatio: number;
  message: string;
}

async function processImageUpload(currentEditor: any, file: File, pos?: number) {
  const uploadId = `upload-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

  try {
    const placeholder = insertUploadPlaceholder(currentEditor, file.name, uploadId, pos);
    uploadingFiles.value.set(uploadId, { fileName: file.name, placeholder });

    const uploadData = {
      image: file,
      name: file.name,
      path: `content/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}`
    };

    await new Promise<UploadResult>((resolve, reject) => {
      router.post(route('files.uploadImage'), uploadData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          const result = page.props.flash?.data as UploadResult;

          if (result) {
            replacePlaceholderWithImage(currentEditor, uploadId, {
              src: result.url,
              alt: file.name,
              title: `Uploaded: ${file.name}`
            });

            resolve(result);
          } else {
            reject(new Error('Upload succeeded but no data received'));
          }
        },
        onError: (errors) => {
          const errorMessage = Object.values(errors).flat().join(', ') || 'Upload failed';
          reject(new Error(errorMessage));
        }
      });
    });

  } catch (error: any) {
    console.error('Upload failed:', error);
    replacePlaceholderWithError(currentEditor, uploadId, error.message);
    toasts.error(`Failed to upload ${file.name}`, {
      description: error.message
    });
  } finally {
    uploadingFiles.value.delete(uploadId);
  }
}

async function processFileUpload(currentEditor: any, file: File, pos?: number) {
  const uploadId = `upload-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

  try {
    const placeholder = insertUploadPlaceholder(currentEditor, file.name, uploadId, pos);
    uploadingFiles.value.set(uploadId, { fileName: file.name, placeholder });

    const formData = new FormData();
    formData.append('files[0][file]', file);
    formData.append('path', `content/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}`);

    await new Promise<any>((resolve, reject) => {
      router.post(route('files.store'), formData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          const fileName = file.name;
          const fileUrl = `/uploads/files/content/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}/${fileName}`;

          replacePlaceholderWithFileLink(currentEditor, uploadId, fileName, fileUrl);
          resolve(page);
        },
        onError: (errors) => {
          const errorMessage = Object.values(errors).flat().join(', ') || 'Upload failed';
          reject(new Error(errorMessage));
        }
      });
    });

  } catch (error: any) {
    console.error('File upload failed:', error);
    replacePlaceholderWithError(currentEditor, uploadId, error.message);
    toasts.error(`Failed to upload ${file.name}`, {
      description: error.message
    });
  } finally {
    uploadingFiles.value.delete(uploadId);
  }
}

function insertUploadPlaceholder(currentEditor: any, fileName: string, uploadId: string, pos?: number) {
  const placeholderText = `🔄 Uploading and compressing ${fileName}...`;

  if (pos !== undefined) {
    currentEditor.chain().focus().insertContentAt(pos, placeholderText).run();
  } else {
    currentEditor.chain().focus().insertContent(placeholderText).run();
  }

  return { uploadId, text: placeholderText };
}

function replacePlaceholderWithImage(currentEditor: any, uploadId: string, imageData: { src: string; alt: string; title: string }) {
  const uploadInfo = uploadingFiles.value.get(uploadId);
  if (!uploadInfo) return;

  const { doc } = currentEditor.state;
  let found = false;

  doc.descendants((node: any, pos: number) => {
    if (found) return false;

    if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
      const from = pos;
      const to = pos + uploadInfo.placeholder.text.length;

      currentEditor
        .chain()
        .focus()
        .deleteRange({ from, to })
        .insertContentAt(from, {
          type: 'image',
          attrs: imageData
        })
        .run();

      found = true;
    }
  });
}

function replacePlaceholderWithFileLink(currentEditor: any, uploadId: string, fileName: string, fileUrl: string) {
  const uploadInfo = uploadingFiles.value.get(uploadId);
  if (!uploadInfo) return;

  const { doc } = currentEditor.state;
  let found = false;

  doc.descendants((node: any, pos: number) => {
    if (found) return false;

    if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
      const fileLink = `<a href="${fileUrl}" target="_blank" class="file-download-link">${fileName}</a>`;

      currentEditor
        .chain()
        .focus()
        .setTextSelection({ from: pos, to: pos + node.text.length })
        .insertContent(fileLink)
        .run();

      found = true;
      return false;
    }
  });
}

function replacePlaceholderWithError(currentEditor: any, uploadId: string, errorMessage: string) {
  const uploadInfo = uploadingFiles.value.get(uploadId);
  if (!uploadInfo) return;

  const { doc } = currentEditor.state;
  let found = false;

  doc.descendants((node: any, pos: number) => {
    if (found) return false;

    if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
      const from = pos;
      const to = pos + uploadInfo.placeholder.text.length;

      currentEditor
        .chain()
        .focus()
        .deleteRange({ from, to })
        .insertContent(`❌ Upload failed: ${errorMessage}`)
        .run();

      found = true;
    }
  });
}

function handleUpdate() {
  const innerHeadings: { level: number; text: string; id: string }[] = []
  const transaction = editor.value?.state.tr

  function latinizeId(text: string) {
    return latinize(text)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
      .substring(0, 100)
  }

  editor.value?.state.doc.descendants((node, pos) => {
    if (node.type.name === 'heading') {
      let id = latinizeId(node.textContent)

      let counter = 1
      while (innerHeadings.some((heading) => heading.id === id)) {
        id = `${latinizeId(node.textContent)}-${counter}`
        counter++
      }

      if (node.attrs.id !== id) {
        transaction?.setNodeAttribute(pos, 'id', id)
      }

      innerHeadings.push({
        level: node.attrs.level,
        text: node.textContent,
        id,
      })
    }
  })

  transaction?.setMeta('addToHistory', false)
  transaction?.setMeta('preventUpdate', true)

  if (transaction) {
    editor.value?.view.dispatch(transaction)
  }
}

onBeforeUnmount(() => {
  // Clear any pending uploads to prevent memory leaks
  uploadingFiles.value.clear();
  
  // Destroy editor instance
  editor.value?.destroy();
});

// Remove the old setTimeout call since we now use onCreate
// (Legacy image handle code removed)
</script>

<style scoped>
.modern-tiptap {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modern-toolbar {
  position: sticky;
  top: 1rem;
  z-index: 10;
}

.modern-editor-content {
  border-radius: 0.5rem;
  border: 1px solid rgb(228 228 231);
  background-color: white;
  padding: 0.25rem;
}

.dark .modern-editor-content {
  border-color: rgb(63 63 70);
  background-color: rgb(24 24 27);
}

.modern-bubble-menu {
  z-index: 50;
}

.modern-toolbar .separator-vertical {
  width: 1px;
  height: 1.5rem;
  background: #d4d4d8;
  margin: 0 0.25rem;
}

.dark .modern-toolbar .separator-vertical {
  background: #52525b;
}

/* Simple image styles */
.tiptap-image {
  cursor: pointer;
  transition: outline 0.2s;
}

.tiptap-image:hover {
  outline: 2px solid #3b82f610;
  outline-offset: 2px;
}

/* Blockquote & list styling inside editor (no Tailwind @apply) */
.modern-editor-content .ProseMirror blockquote {
  border-left: 4px solid #e4e4e7;
  margin: 1rem 0;
  padding: 0.5rem 1rem;
  font-style: italic;
  background: #f8f8f9;
  border-radius: 0 0.25rem 0.25rem 0;
}

.dark .modern-editor-content .ProseMirror blockquote {
  border-left-color: #52525b;
  background: #27272a;
}

.modern-editor-content .ProseMirror ul,
.modern-editor-content .ProseMirror ol {
  padding-left: 1.5rem;
  margin: 0.75rem 0 0.75rem 0;
}

.modern-editor-content .ProseMirror ul li {
  list-style: disc;
  margin: 0.25rem 0;
}

.modern-editor-content .ProseMirror ol li {
  list-style: decimal;
  margin: 0.25rem 0;
}

/* Ensure nested list indentation */
.modern-editor-content .ProseMirror li > ul,
.modern-editor-content .ProseMirror li > ol {
  margin: 0.25rem 0;
}
</style>

<!-- existing global style modifications -->
<style>
/* Ensure vertical separators visible using data attribute fallback */
.modern-toolbar [data-separator="vertical"] {
  width: 1px;
  background: rgba(0, 0, 0, 0.12);
  height: 24px;
  margin: 0 4px;
}

.dark .modern-toolbar [data-separator="vertical"] {
  background: rgba(255, 255, 255, 0.2);
}
</style>

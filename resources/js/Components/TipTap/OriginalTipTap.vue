<template>
  <div class="gap flex w-full flex-col">
    <BubbleMenu v-if="editor" class="bg-white dark:bg-zinc-900" :editor>
      <TiptapFormattingButtons v-model:editor="editor" secondary />
    </BubbleMenu>
    <div v-if="showToolbar && editor" class="mt-1 flex min-h-8 flex-wrap items-center gap-2">
      <TiptapFormattingButtons v-model:editor="editor" />
      <ButtonGroup>
        <TiptapLinkButton :editor="editor"
          @submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run()"
          @document:submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run()" />
        <Button size="sm" variant="outline" :disabled="!editor.isActive('link')" @click="editor?.chain().focus().unsetLink().run()">
          <IFluentLinkDismiss20Filled />
        </Button>
      </ButtonGroup>
      <Button size="sm" variant="outline" @click="editor?.chain().focus().unsetAllMarks().run()">
        <IFluentClearFormatting20Filled />
      </Button>
      <Separator orientation="vertical" />
      <ButtonGroup>
        <Button size="sm" :variant="editor.isActive('paragraph') ? 'default' : 'outline'"
          @click="editor?.chain().focus().setParagraph().run()">
          <IFluentTextT24Regular />
        </Button>
        <Button size="sm" :variant="editor.isActive('heading', { level: 2 }) ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
          <TextHeader220Filled />
        </Button>
        <Button size="sm" :variant="editor.isActive('heading', { level: 3 }) ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
          <TextHeader320Filled />
        </Button>
      </ButtonGroup>
      <ButtonGroup>
        <Button size="sm" :variant="editor.isActive('bulletList') ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleBulletList().run()">
          <IFluentTextBulletListLtr24Filled />
        </Button>
        <Button size="sm" :variant="editor.isActive('orderedList') ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleOrderedList().run()">
          <IFluentTextNumberListLtr24Filled />
        </Button>
        <Button size="sm" :variant="editor.isActive('blockquote') ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleBlockquote().run()">
          <IFluentTextQuote24Filled />
        </Button>
      </ButtonGroup>
      <Button size="sm" :variant="editor.isActive('horizontalRule') ? 'default' : 'outline'"
        @click="editor?.chain().focus().setHorizontalRule().run()">
        <LineHorizontal120Regular />
      </Button>
      <ButtonGroup>
        <Suspense>
          <TiptapImageButton @submit="attachImageWithAlt" />
        </Suspense>
        <Suspense>
          <TiptapVideoButton @submit="attachVideoUrl" />
        </Suspense>
        <TiptapYoutubeButton @submit="(youtubeUrl) => editor?.commands.setYoutubeVideo({ src: youtubeUrl })" />
      </ButtonGroup>
      <ButtonGroup>
        <Button size="sm" variant="outline" @click="editor?.chain().focus().undo().run()">
          <IFluentArrowUndo20Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor?.chain().focus().redo().run()">
          <IFluentArrowRedo20Regular />
        </Button>
      </ButtonGroup>
    </div>
    <div v-if="showTableToolbar && editor" class="mt-1 flex items-center gap-2">
      <ButtonGroup>
        <Button size="sm" variant="outline" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">
          <IFluentTableAdd24Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor.chain().focus().toggleHeaderRow().run()">
          <IFluentTableFreezeRow24Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor.chain().focus().addColumnAfter().run()">
          <IFluentTableInsertColumn24Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor.chain().focus().addRowBefore().run()">
          <IFluentTableInsertRow24Regular />
        </Button>
      </ButtonGroup>
      <ButtonGroup>
        <Button size="sm" variant="outline" @click="editor.chain().focus().deleteColumn().run()">
          <IFluentTableDeleteColumn24Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor.chain().focus().deleteRow().run()">
          <IFluentTableDeleteRow24Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor.chain().focus().deleteTable().run()">
          <IFluentTableDismiss24Regular />
        </Button>
      </ButtonGroup>
      <ButtonGroup>
        <Button size="sm" variant="outline" @click="editor.chain().focus().mergeCells().run()">
          <IFluentTableCellsMerge24Regular />
        </Button>
        <Button size="sm" variant="outline" @click="editor.chain().focus().splitCell().run()">
          <IFluentTableCellsSplit24Regular />
        </Button>
      </ButtonGroup>
      <Button size="sm" variant="outline" @click="editor.chain().focus().fixTables().run()">
        <IFluentTableSettings24Regular />
      </Button>
    </div>
    <div class="mt-3 grid grid-cols-[auto__30px] items-center gap-2 only:mt-0">
      <div class="max-h-96 w-full overflow-y-auto overflow-x-hidden">
        <EditorContent :editor="editor" class="min-h-24 rounded-md border dark:border-0 dark:bg-zinc-800" />
        <div v-if="editor && maxCharacters" class="mt-4 text-xs text-gray-500 dark:text-gray-400">
          {{ editor.storage.characterCount.characters() }}
        </div>
        <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
          <IFluentImage24Regular class="h-4 w-4" />
          <span>{{ $t('rich-content.drag_drop_images_info') }}</span>
        </div>
      </div>
      <div class="flex flex-col gap-2">
        <Button size="sm" :variant="showToolbar ? 'default' : 'outline'" @click="showToolbar = !showToolbar">
          <IFluentSettings24Filled />
        </Button>
        <Button v-if="!disableTables" size="sm" :variant="showTableToolbar ? 'default' : 'outline'"
          @click="showTableToolbar = !showTableToolbar">
          <IFluentTable24Regular />
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { BubbleMenu } from "@tiptap/vue-3/menus";
import { CharacterCount, Placeholder } from "@tiptap/extensions";
import { FileHandler } from "@tiptap/extension-file-handler";
import { nextTick, onBeforeUnmount, ref } from "vue";
import { AccessibleImage } from "./AccessibleImage";
import { StarterKit } from "@tiptap/starter-kit";

import { TableKit } from '@tiptap/extension-table'
import { Youtube } from "@tiptap/extension-youtube";

import LineHorizontal120Regular from "~icons/fluent/line-horizontal-1-20-regular"
import TextHeader220Filled from "~icons/fluent/text-header-2-20-filled"
import TextHeader320Filled from "~icons/fluent/text-header-3-20-filled"
import IFluentImage24Regular from "~icons/fluent/image-24-regular"

import { Button } from "@/Components/ui/button";
import { ButtonGroup } from "@/Components/ui/button-group";
import { CustomHeading } from "./CustomHeading";
import { Video } from './Video';
import TiptapFormattingButtons from "./TiptapFormattingButtons.vue";
import TiptapImageButton from "./TiptapImageButton.vue";
import TiptapLinkButton from "./TiptapLinkButton.vue";
import TiptapVideoButton from "./TiptapVideoButton.vue";
import TiptapYoutubeButton from "./TiptapYoutubeButton.vue";
import { latinizeId } from "@/Utils/String";
import { trans as $t } from "laravel-vue-i18n";
import { Separator } from "../ui/separator";
import { useToasts } from '@/Composables/useToasts';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps<{
  disableTables?: boolean;
  maxCharacters?: number;
  html?: boolean;
  modelValue: string | Record<string, unknown> | null;
}>();

const emit = defineEmits(["update:modelValue"]);
const modelValue = ref(props.modelValue);

const showToolbar = ref(true);
const showTableToolbar = ref(false);
const toasts = useToasts();

// Upload state management
const uploadingFiles = ref(new Map<string, { fileName: string; placeholder: any }>());

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "focus:outline-hidden px-3 py-2 w-full",
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
    Placeholder.configure({
      placeholder: $t ? $t('rich-content.text_placeholder') : "Tekstas...",
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
  onUpdate: () => {

    handleUpdate()

    nextTick(() => {
      if (props.html) {
        emit("update:modelValue", editor.value?.getHTML());
      } else {
        emit("update:modelValue", editor.value?.getJSON());
      }
    });
  },
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});

function attachVideoUrl(url: string) {
  editor.value?.chain().focus().setVideo(url).run();
}

function attachImageWithAlt(imageData: { src: string; alt?: string; title?: string } | string) {
  if (typeof imageData === 'string') {
    // Backward compatibility - if just a URL is passed
    editor.value?.chain().focus().setImage({ src: imageData }).run();
  } else {
    // Use the new accessible image format with alt and title
    editor.value?.chain().focus().setImage({ 
      src: imageData.src,
      alt: imageData.alt || '',
      title: imageData.title || '',
    }).run();
  }
}

// File handling functions
async function handleFileDrop(currentEditor: any, files: File[], pos?: number) {
  // Process all files (images and non-images)
  for (const file of files) {
    if (file.type.startsWith('image/')) {
      await processImageUpload(currentEditor, file, pos);
    } else {
      await processFileUpload(currentEditor, file, pos);
    }
  }
}

async function handleFilePaste(currentEditor: any, files: File[]) {
  // Process all files (images and non-images)
  for (const file of files) {
    if (file.type.startsWith('image/')) {
      await processImageUpload(currentEditor, file);
    } else {
      await processFileUpload(currentEditor, file);
    }
  }
}

// Add type definitions for upload response
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
    // 1. Insert upload placeholder
    const placeholder = insertUploadPlaceholder(currentEditor, file.name, uploadId, pos);
    uploadingFiles.value.set(uploadId, { fileName: file.name, placeholder });
    
    // 2. Upload and compress image using Inertia
    const uploadData = {
      image: file,
      name: file.name,
      path: `content/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}`
    };
    
    // Use Inertia router.post - it will automatically convert to FormData
    await new Promise<UploadResult>((resolve, reject) => {
      router.post(route('files.uploadImage'), uploadData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          // Get the upload result from flash data
          const result = page.props.flash?.data as UploadResult;
          
          if (result) {
            // 3. Replace placeholder with actual image
            replacePlaceholderWithImage(currentEditor, uploadId, {
              src: result.url,
              alt: file.name,
              title: `Uploaded: ${file.name}`
            });
            
            // Note: Success notification is handled automatically by Inertia flash system
            
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
    
    // Replace placeholder with error message
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
    // 1. Insert upload placeholder
    const placeholder = insertUploadPlaceholder(currentEditor, file.name, uploadId, pos);
    uploadingFiles.value.set(uploadId, { fileName: file.name, placeholder });
    
    // 2. Upload file using regular files.store route (for content folder placement)
    const formData = new FormData();
    formData.append('files[0][file]', file);
    formData.append('path', `content/${new Date().getFullYear()}/${String(new Date().getMonth() + 1).padStart(2, '0')}`);
    
    // Use Inertia router.post
    await new Promise<any>((resolve, reject) => {
      router.post(route('files.store'), formData, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          // 3. Replace placeholder with file link
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
    
    // Replace placeholder with error message
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
    // Insert at specific position (drag-and-drop)
    currentEditor.chain().focus().insertContentAt(pos, placeholderText).run();
  } else {
    // Insert at current cursor position (paste)
    currentEditor.chain().focus().insertContent(placeholderText).run();
  }
  
  return { uploadId, text: placeholderText };
}

function replacePlaceholderWithImage(currentEditor: any, uploadId: string, imageData: { src: string; alt: string; title: string }) {
  const uploadInfo = uploadingFiles.value.get(uploadId);
  if (!uploadInfo) return;
  
  // Find and replace the placeholder text with the image
  const doc = currentEditor.state.doc;
  let found = false;
  
  doc.descendants((node: any, pos: number) => {
    if (found) return false;
    
    if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
      // Replace the placeholder text with an image
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
      // Replace placeholder with file link
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
  
  // Find and replace the placeholder text with error message
  const doc = currentEditor.state.doc;
  let found = false;
  
  doc.descendants((node: any, pos: number) => {
    if (found) return false;
    
    if (node.isText && node.text?.includes(uploadInfo.placeholder.text)) {
      // Replace the placeholder text with error message
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

  editor.value?.state.doc.descendants((node, pos) => {
    if (node.type.name === 'heading') {

      // Get node text slug
      let id = latinizeId(node.textContent)

      // Check if id doesn't repeat
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
</script>

<style>
.tiptap {
  word-wrap: break-word;
  overflow-wrap: break-word;

  p,
  ul,
  ol,
  blockquote {
    margin: 0.4rem 0 0.4rem 0;
  }

  ul {
    list-style-type: disc;
  }

  ol {
    list-style-type: decimal;
  }

  ul,
  ol {
    padding-left: 1.5rem;
  }

  a {
    color: #bd2835;
    text-decoration: underline;
    font-weight: 500;
  }

  blockquote {
    padding-left: 1rem;
    border-left: 4px solid #e2e8f0;
  }

  /* For placeholder  */
  p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
  }

  h2,
  h3,
  h4,
  h5,
  h6 {
    margin-bottom: 1rem;
  }

  img {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    margin: 0.5rem 0;
  }

  table {
    border-collapse: collapse;
    width: 100%;

    .selectedCell:after {
      z-index: 2;
      position: absolute;
      content: "";
      left: 0;
      right: 0;
      top: 0;
      bottom: 0;
      background: rgba(189, 40, 53, 0.08);
      pointer-events: none;
    }
  }

  th,
  td {
    border: 1px solid #e2e8f0;
    position: relative;
  }

  td {
    padding: 0 0.4rem;
  }
}
</style>

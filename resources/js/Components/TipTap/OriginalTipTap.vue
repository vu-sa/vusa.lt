<template>
  <div class="gap flex w-full flex-col">
    <BubbleMenu v-if="editor" class="bg-white dark:bg-zinc-900" :editor>
      <TiptapFormattingButtons v-model:editor="editor" secondary />
    </BubbleMenu>
    <div v-if="showToolbar && editor" class="mt-1 flex min-h-8 flex-wrap items-center gap-2">
      <TiptapFormattingButtons v-model:editor="editor" />
      <NButtonGroup size="small">
        <TiptapLinkButton :editor="editor"
          @submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run()"
          @document:submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run()" />
        <NButton :disabled="!editor.isActive('link')" @click="editor?.chain().focus().unsetLink().run()">
          <template #icon>
            <IFluentLinkDismiss20Filled />
          </template>
        </NButton>
      </NButtonGroup>
      <NButton size="small" @click="editor?.chain().focus().unsetAllMarks().run()">
        <template #icon>
          <IFluentClearFormatting20Filled />
        </template>
      </NButton>
      <Separator orientation="vertical" />
      <NButtonGroup size="small">
        <NButton :type="editor.isActive('paragraph') ? 'primary' : 'default'"
          @click="editor?.chain().focus().setParagraph().run()">
          <template #icon>
            <IFluentTextT24Regular />
          </template>
        </NButton>
        <NButton :type="editor.isActive('heading', { level: 2 }) ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
          <template #icon>
            <TextHeader220Filled />
          </template>
        </NButton>
        <NButton :type="editor.isActive('heading', { level: 3 }) ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
          <template #icon>
            <TextHeader320Filled />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton :type="editor.isActive('bulletList') ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleBulletList().run()">
          <template #icon>
            <IFluentTextBulletListLtr24Filled />
          </template>
        </NButton>
        <NButton :type="editor.isActive('orderedList') ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleOrderedList().run()">
          <template #icon>
            <IFluentTextNumberListLtr24Filled />
          </template>
        </NButton>
        <NButton :type="editor.isActive('blockquote') ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleBlockquote().run()">
          <IFluentTextQuote24Filled />
        </NButton>
      </NButtonGroup>
      <NButton size="small" :type="editor.isActive('horizontalRule') ? 'primary' : 'default'"
        @click="editor?.chain().focus().setHorizontalRule().run()">
        <template #icon>
          <LineHorizontal120Regular />
        </template>
      </NButton>
      <NButtonGroup size="small">
        <Suspense>
          <TiptapImageButton @submit="(url) => editor?.chain().focus().setImage({ src: url }).run()" />
        </Suspense>
        <Suspense>
          <TiptapVideoButton @submit="attachVideoUrl" />
        </Suspense>
        <TiptapYoutubeButton @submit="(youtubeUrl) => editor?.commands.setYoutubeVideo({ src: youtubeUrl })" />
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton @click="editor?.chain().focus().undo().run()">
          <template #icon>
            <IFluentArrowUndo20Regular />
          </template>
        </NButton>
        <NButton @click="editor?.chain().focus().redo().run()">
          <template #icon>
            <IFluentArrowRedo20Regular />
          </template>
        </NButton>
      </NButtonGroup>
    </div>
    <div v-if="showTableToolbar && editor" class="mt-1 flex items-center gap-2">
      <NButtonGroup size="small">
        <NButton @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">
          <template #icon>
            <IFluentTableAdd24Regular />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().toggleHeaderRow().run()">
          <template #icon>
            <IFluentTableFreezeRow24Regular />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().addColumnAfter().run()">
          <template #icon>
            <IFluentTableInsertColumn24Regular />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().addRowBefore().run()">
          <template #icon>
            <IFluentTableInsertRow24Regular />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton @click="editor.chain().focus().deleteColumn().run()">
          <template #icon>
            <IFluentTableDeleteColumn24Regular />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().deleteRow().run()">
          <template #icon>
            <IFluentTableDeleteRow24Regular />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().deleteTable().run()">
          <template #icon>
            <IFluentTableDismiss24Regular />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton @click="editor.chain().focus().mergeCells().run()">
          <template #icon>
            <IFluentTableCellsMerge24Regular />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().splitCell().run()">
          <template #icon>
            <IFluentTableCellsSplit24Regular />
          </template>
        </NButton>
      </NButtonGroup>
      <NButton size="small" @click="editor.chain().focus().fixTables().run()">
        <template #icon>
          <IFluentTableSettings24Regular />
        </template>
      </NButton>
    </div>
    <div class="mt-3 grid grid-cols-[auto__30px] items-center gap-2 only:mt-0">
      <div class="max-h-96  w-full overflow-y-scroll">
        <EditorContent :editor="editor" class="min-h-24 rounded-md border dark:border-0 dark:bg-zinc-800" />
        <div v-if="editor && maxCharacters" class="mt-4 text-xs text-gray-500 dark:text-gray-400">
          {{ editor.storage.characterCount.characters() }}
        </div>
      </div>
      <div class="flex flex-col gap-2">
        <NButton :type="showToolbar ? 'primary' : 'default'" size="small" @click="showToolbar = !showToolbar">
          <template #icon>
            <IFluentSettings24Filled />
          </template>
        </NButton>
        <NButton v-if="!disableTables" :type="showTableToolbar ? 'primary' : 'default'" size="small"
          @click="showTableToolbar = !showTableToolbar">
          <template #icon>
            <IFluentTable24Regular />
          </template>
        </NButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { BubbleMenu } from "@tiptap/vue-3/menus";
import { CharacterCount, Placeholder } from "@tiptap/extensions";
import { nextTick, onBeforeUnmount, ref } from "vue";
import { Image } from "@tiptap/extension-image";
import { StarterKit } from "@tiptap/starter-kit";

import { TableKit } from '@tiptap/extension-table'
import { Youtube } from "@tiptap/extension-youtube";

import LineHorizontal120Regular from "~icons/fluent/line-horizontal-1-20-regular"
import TextHeader220Filled from "~icons/fluent/text-header-2-20-filled"
import TextHeader320Filled from "~icons/fluent/text-header-3-20-filled"

import { CustomHeading } from "./CustomHeading";
import { Video } from './Video';
import TiptapFormattingButtons from "./TiptapFormattingButtons.vue";
import TiptapImageButton from "./TiptapImageButton.vue";
import TiptapLinkButton from "./TiptapLinkButton.vue";
import TiptapVideoButton from "./TiptapVideoButton.vue";
import TiptapYoutubeButton from "./TiptapYoutubeButton.vue";
import latinize from "latinize";
import { trans as $t } from "laravel-vue-i18n";
import { Separator } from "../ui/separator";

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
    BubbleMenu,
    Placeholder.configure({
      placeholder: $t ? $t('rich-content.text_placeholder') : "Tekstas...",
    }),
    Image.configure({
      HTMLAttributes: {
        class: "w-96",
      },
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

function handleUpdate() {
  const innerHeadings = []
  const transaction = editor.value?.state.tr

  function latinizeId(text: string) {
    return latinize(text)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
      .substring(0, 100) // Limit ID length for better compatibility
  }

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

  editor.value?.view.dispatch(transaction)
}
</script>

<style>
.tiptap {

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

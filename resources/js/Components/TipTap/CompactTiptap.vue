<template>
  <div class="gap flex w-full flex-col">
    <!-- Bubble menu with essential formatting options -->
    <BubbleMenu v-if="editor"
      class="bg-white dark:bg-zinc-900 shadow-md rounded-md border border-zinc-200 dark:border-zinc-700"
      :editor="editor" :tippy-options="{ duration: 50 }">
      <div class="flex items-center p-1">
        <TiptapFormattingButtons v-model:editor="editor" secondary />

        <!-- Link controls -->
        <ButtonGroup>
          <TiptapLinkButton :editor="editor"
            @submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run()"
            @document:submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run()" />
          <Button size="sm" variant="outline" :disabled="!editor.isActive('link')" @click="editor?.chain().focus().unsetLink().run()">
            <IFluentLinkDismiss20Filled />
          </Button>
        </ButtonGroup>

        <!-- Text styles -->
        <ButtonGroup class="ml-1">
          <Button size="sm" :variant="editor.isActive('paragraph') ? 'default' : 'outline'"
            @click="editor?.chain().focus().setParagraph().run()">
            <IFluentTextT24Regular />
          </Button>
          <Button size="sm" :variant="editor.isActive('heading', { level: 2 }) ? 'default' : 'outline'"
            @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
            <TextHeader220Filled />
          </Button>
        </ButtonGroup>

        <!-- More options button that shows a popover with additional controls -->
        <NPopover trigger="click" placement="bottom" class="p-0">
          <template #trigger>
            <Button size="sm" variant="ghost" class="ml-1">
              <IFluentMoreHorizontal24Regular />
            </Button>
          </template>
          <div class="p-2 flex flex-col gap-2">
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
              <Button size="sm" :variant="editor.isActive('blockquote') ? 'default' : 'outline'"
                @click="editor?.chain().focus().toggleBlockquote().run()">
                <IFluentTextQuote24Filled />
              </Button>
            </ButtonGroup>

            <!-- Media buttons -->
            <ButtonGroup>
              <Suspense>
                <TiptapImageButton @submit="(url) => editor?.chain().focus().setImage({ src: url }).run()" />
              </Suspense>
              <TiptapYoutubeButton @submit="(youtubeUrl) => editor?.commands.setYoutubeVideo({ src: youtubeUrl })" />
            </ButtonGroup>

            <!-- Undo/Redo -->
            <ButtonGroup>
              <Button size="sm" variant="outline" @click="editor?.chain().focus().undo().run()">
                <IFluentArrowUndo20Regular />
              </Button>
              <Button size="sm" variant="outline" @click="editor?.chain().focus().redo().run()">
                <IFluentArrowRedo20Regular />
              </Button>
            </ButtonGroup>
          </div>
        </NPopover>
      </div>
    </BubbleMenu>

    <!-- Toggle for showing/hiding the toolbar -->
    <div v-if="showToolbarToggle" class="flex justify-end mb-1">
      <Button size="xs" variant="ghost" @click="showToolbar = !showToolbar">
        <IFluentSettings16Filled v-if="!showToolbar" />
        <IFluentSettings16Regular v-else />
        <span class="text-xs">{{ showToolbar ? 'Hide toolbar' : 'Show toolbar' }}</span>
      </Button>
    </div>

    <!-- Optional toolbar - hidden by default -->
    <div v-if="showToolbar && editor"
      class="mb-2 flex min-h-8 flex-wrap items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2">
      <TiptapFormattingButtons v-model:editor="editor" />
      <ButtonGroup>
        <TiptapLinkButton :editor="editor"
          @submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: '' }).run()"
          @document:submit="(url) => editor?.chain().focus().extendMarkRange('link').setLink({ href: url, class: 'archive-document-link plain' }).run()" />
        <Button size="sm" variant="outline" :disabled="!editor.isActive('link')" @click="editor?.chain().focus().unsetLink().run()">
          <IFluentLinkDismiss20Filled />
        </Button>
      </ButtonGroup>
      <ButtonGroup>
        <Button size="sm" :variant="editor.isActive('paragraph') ? 'default' : 'outline'"
          @click="editor?.chain().focus().setParagraph().run()">
          <IFluentTextT24Regular />
        </Button>
        <Button size="sm" :variant="editor.isActive('heading', { level: 2 }) ? 'default' : 'outline'"
          @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
          <TextHeader220Filled />
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
      </ButtonGroup>
      <ButtonGroup>
        <Suspense>
          <TiptapImageButton @submit="(url) => editor?.chain().focus().setImage({ src: url }).run()" />
        </Suspense>
        <TiptapYoutubeButton @submit="(youtubeUrl) => editor?.commands.setYoutubeVideo({ src: youtubeUrl })" />
      </ButtonGroup>
    </div>

    <!-- Editor content -->
    <EditorContent :editor="editor" class="min-h-[80px] rounded-md border dark:border-0 dark:bg-zinc-800" />
  </div>
</template>

<script setup lang="ts">
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { nextTick, onBeforeUnmount, ref } from "vue";
import { BubbleMenu } from "@tiptap/vue-3/menus";
import { StarterKit } from "@tiptap/starter-kit";
import { Image } from "@tiptap/extension-image";
import { CharacterCount, Placeholder } from "@tiptap/extensions";
import { Youtube } from "@tiptap/extension-youtube";

import TextHeader220Filled from "~icons/fluent/text-header-2-20-filled";

import { Button } from "@/Components/ui/button";
import { ButtonGroup } from "@/Components/ui/button-group";
import { CustomHeading } from "./CustomHeading";
import TiptapFormattingButtons from "./TiptapFormattingButtons.vue";
import TiptapImageButton from "./TiptapImageButton.vue";
import TiptapLinkButton from "./TiptapLinkButton.vue";
import TiptapYoutubeButton from "./TiptapYoutubeButton.vue";
import { trans as $t } from "laravel-vue-i18n";

const props = defineProps<{
  showToolbarToggle?: boolean;
  modelValue: string | Record<string, unknown> | null;
}>();

const emit = defineEmits(["update:modelValue"]);
const showToolbar = ref(false);

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
      },
    }),
    CustomHeading.configure({
      levels: [2],
    }),
    BubbleMenu,
    Placeholder.configure({
      placeholder: $t ? $t('rich-content.text_placeholder') : "Tekstas...",
    }),
    Image.configure({
      HTMLAttributes: {
        class: "w-full",
      },
    }),
    Youtube.configure({
      HTMLAttributes: {
        class: "aspect-video w-auto my-2",
      },
    }),
  ],
  content: props.modelValue ?? "",
  onUpdate: () => {
    nextTick(() => {
      emit("update:modelValue", editor.value?.getJSON());
    });
  },
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});
</script>

<style>
/* Keep the base styles from OriginalTipTap.vue */
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
}
</style>

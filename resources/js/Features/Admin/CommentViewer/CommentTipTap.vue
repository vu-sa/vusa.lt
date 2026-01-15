<template>
  <div class="flex flex-col" style="max-height: 280px">
    <div :class="{ 'rounded-t-md': roundedTop }"
      class="grid grid-cols-[60px_1fr] overflow-y-scroll rounded-b-md border dark:border-zinc-600">
      <div ref="commentContainer" class="flex justify-center items-center">
        <UserAvatar :size="23" class="sticky top-4" :user="$page.props.auth?.user" />
      </div>
      <EditorContent :editor class="leading-normal" />
    </div>
    <div v-if="editor" class="border-top-0 flex items-center justify-between gap-2 border-zinc-400 p-4">
      <div class="flex flex-wrap items-center gap-2">
        <TiptapFormattingButtons v-model:editor="editor" />
        <TipTapButton :editor type="bulletList"
          :callback="() => editor?.chain().focus().toggleBulletList().run()">
          <template #icon>
            <IFluentTextBulletListLtr24Filled />
          </template>
        </TipTapButton>
        <TipTapButton :editor type="orderedList"
          :callback="() => editor?.chain().focus().toggleOrderedList().run()">
          <template #icon>
            <IFluentTextNumberListLtr24Filled />
          </template>
        </TipTapButton>
      </div>
      <Button size="sm" :disabled="disabled || loading" @click="$emit('submit:comment')">
        <Spinner v-if="loading" />
        <IFluentSend24Filled v-else />
        {{ submitText ?? $t("Pateikti") }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { onBeforeUnmount, ref } from "vue";
import { StarterKit } from "@tiptap/starter-kit";

import TipTapButton from "./TipTap/TipTapButton.vue";

import { Button } from "@/Components/ui/button";
import { Spinner } from "@/Components/ui/spinner";
import TiptapFormattingButtons from "@/Components/TipTap/TiptapFormattingButtons.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const props = defineProps<{
  text: string | null;
  disabled: boolean;
  loading: boolean;
  roundedTop?: boolean;
  submitText?: string;
}>();

const emit = defineEmits(["update:text", "submit:comment"]);

const commentContainer = ref<HTMLElement | null>(null);

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "focus:outline-hidden py-4",
    },
  },
  extensions: [
    StarterKit.configure({
      link: {
        openOnClick: false
      }
    }),
  ],
  content: props.text,
  onUpdate: () => {
    // HTML
    emit("update:text", editor.value?.getHTML());
  },
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});
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

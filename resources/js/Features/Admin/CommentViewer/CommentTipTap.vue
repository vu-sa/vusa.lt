<template>
  <div class="flex flex-col" style="max-height: 280px">
    <div :class="{ 'rounded-t-md': roundedTop }"
      class="grid grid-cols-[60px_1fr] overflow-y-scroll rounded-b-md border dark:border-zinc-600">
      <div ref="commentContainer" class="flex justify-center">
        <UserAvatar :size="23" class="sticky top-4" :user="$page.props.auth?.user" />
      </div>
      <EditorContent :editor="editor" class="leading-normal" />
      <!-- <NBackTop v-if="commentContainer" :to="commentContainer" /> -->
    </div>
    <div v-if="editor" class="border-top-0 flex items-center justify-between gap-2 border-zinc-400 p-4">
      <div class="flex flex-wrap items-center gap-2">
        <TiptapFormattingButtons v-model:editor="editor" />
        <TipTapButton :editor="editor" :type="'bulletList'"
          :callback="() => editor?.chain().focus().toggleBulletList().run()">
          <template #icon>
            <IFluentTextBulletListLtr24Filled />
          </template>
        </TipTapButton>
        <TipTapButton :editor="editor" :type="'orderedList'"
          :callback="() => editor?.chain().focus().toggleOrderedList().run()">
          <template #icon>
            <IFluentTextNumberListLtr24Filled />
          </template>
        </TipTapButton>
      </div>
      <NButtonGroup v-if="enableApprove" size="small" vertical class="gap-2">
        <NButton :loading type="success" secondary @click="$emit('submit:comment', 'approve')">
          <template #icon>
            <IFluentCommentCheckmark24Regular />
          </template>
          {{ approveText ?? capitalize($t("states.decision.approve")) }}
        </NButton>
        <NButton :loading type="warning" secondary @click="$emit('submit:comment', 'reject')">
          <template #icon>
            <IFluentCommentError24Regular />
          </template>
          {{ rejectText ?? `... ${$t("states.decision.reject")}` }}
        </NButton>
      </NButtonGroup>
      <NButton v-else size="small" :disabled type="primary" :loading="loading" icon-placement="right"
        @click="$emit('submit:comment')">
        <template #icon>
          <IFluentSend24Filled />
        </template>{{ submitText ?? $t("Pateikti") }}
      </NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { capitalize, onBeforeUnmount, ref } from "vue";

import StarterKit from "@tiptap/starter-kit";
import TipTapButton from "./TipTap/TipTapButton.vue";
import TipTapLink from "@tiptap/extension-link";
import TiptapFormattingButtons from "@/Components/TipTap/TiptapFormattingButtons.vue";
import Underline from "@tiptap/extension-underline";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const props = defineProps<{
  text: string | null;
  disabled: boolean;
  loading: boolean;
  roundedTop?: boolean;
  enableApprove?: boolean;
  submitText?: string;
  approveText?: string;
  rejectText?: string;
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
    StarterKit,
    TipTapLink.configure({
      openOnClick: false,
    }),
    Underline,
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

  ul, ol {
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

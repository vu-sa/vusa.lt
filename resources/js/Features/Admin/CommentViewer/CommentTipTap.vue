<template>
  <div class="flex flex-col">
    <EditorContent
      :editor="editor"
      class="min-h-[6rem] overflow-y-scroll border bg-white outline-zinc-400 dark:border-zinc-600 dark:bg-zinc-900"
    />
    <div
      v-if="editor"
      class="border-top-0 flex items-center justify-between gap-2 border-zinc-400 p-4"
    >
      <div class="flex flex-wrap items-center gap-2">
        <TipTapMarkButton
          :editor="editor"
          :type="'bold'"
          :icon="TextBold20Regular"
        />
        <TipTapMarkButton
          :editor="editor"
          :type="'italic'"
          :icon="TextItalic20Regular"
        />
        <TipTapMarkButton
          :editor="editor"
          :type="'underline'"
          :icon="TextUnderline20Regular"
        />

        <TipTapButton
          :editor="editor"
          :type="'bulletList'"
          :icon="TextBulletListLtr24Filled"
          :callback="() => editor.chain().focus().toggleBulletList().run()"
        />
        <TipTapButton
          :editor="editor"
          :type="'orderedList'"
          :icon="TextNumberListLtr24Filled"
          :callback="() => editor.chain().focus().toggleOrderedList().run()"
        />
      </div>
      <NButtonGroup type="primary" size="small">
        <NButton
          :disabled="disabled"
          type="primary"
          :loading="loading"
          @click="$emit('submit:comment')"
          ><template #icon><NIcon :component="Send20Filled"></NIcon></template
          >SIŲSTI</NButton
        >
        <NPopover trigger="click" class="rounded-md" raw :show-arrow="false">
          <div class="flex flex-col rounded-md bg-zinc-50 dark:bg-zinc-800">
            <NButtonGroup size="medium" vertical>
              <NButton
                type="success"
                secondary
                @click="$emit('submit:comment', 'approve')"
                ><template #icon
                  ><NIcon :component="CommentCheckmark24Regular"> </NIcon
                ></template>
                ... ir patvirtinti</NButton
              >
              <NButton
                type="warning"
                tertiary
                ghost
                @click="$emit('submit:comment', 'reject')"
                ><template #icon
                  ><NIcon :component="CommentError24Regular"> </NIcon></template
                >... ir grąžinti peržiūrai</NButton
              >
            </NButtonGroup>
          </div>
          <template #trigger>
            <NButton type="primary" secondary
              ><template #icon
                ><NIcon :component="CaretDown24Filled"></NIcon></template
            ></NButton>
          </template>
        </NPopover>
      </NButtonGroup>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  CaretDown24Filled,
  CommentCheckmark24Regular,
  CommentError24Regular,
  Send20Filled,
  TextBold20Regular,
  TextBulletListLtr24Filled,
  TextItalic20Regular,
  TextNumberListLtr24Filled,
  TextUnderline20Regular,
} from "@vicons/fluent";
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { NButton, NButtonGroup, NIcon, NPopover } from "naive-ui";
import { onBeforeUnmount, ref, watch } from "vue";
import StarterKit from "@tiptap/starter-kit";
import TipTapButton from "./TipTap/TipTapButton.vue";
import TipTapLink from "@tiptap/extension-link";
import TipTapMarkButton from "./TipTap/TipTapMarkButton.vue";
import Underline from "@tiptap/extension-underline";

const props = defineProps<{
  text: string | null;
  loading: boolean;
  disabled: boolean;
}>();

// :disabled="editor?.getHTML() == '<p></p>'"

const emit = defineEmits(["update:text", "submit:comment"]);

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "focus:outline-none min-h-[4rem] p-4",
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

<style lang="scss" scoped>
/* Basic editor styles */
.ProseMirror {
  > * + * {
    margin-top: 0.75em;
  }

  ul,
  ol {
    padding: 0 1rem;
  }

  h1,
  h2,
  h3,
  h4,
  h5,
  h6 {
    line-height: 1.1;
  }

  code {
    background-color: rgba(#616161, 0.1);
    color: #616161;
  }

  pre {
    background: #0d0d0d;
    color: #fff;
    font-family: "JetBrainsMono", monospace;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;

    code {
      color: inherit;
      padding: 0;
      background: none;
      font-size: 0.8rem;
    }
  }

  img {
    max-width: 100%;
    height: auto;

    &.ProseMirror-selectednode {
      outline: 3px solid #68cef8;
    }
  }

  blockquote {
    padding-left: 1rem;
    border-left: 2px solid rgba(#0d0d0d, 0.1);
  }

  hr {
    border: none;
    border-top: 2px solid rgba(#0d0d0d, 0.1);
    margin: 2rem 0;
  }

  table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 0;
    overflow: hidden;

    td,
    th {
      min-width: 1em;
      border: 2px solid #ced4da;
      padding: 3px 5px;
      vertical-align: top;
      box-sizing: border-box;
      position: relative;

      > * {
        margin-bottom: 0;
      }
    }

    th {
      font-weight: bold;
      text-align: left;
      background-color: #f1f3f5;
    }

    .selectedCell:after {
      z-index: 2;
      position: absolute;
      content: "";
      left: 0;
      right: 0;
      top: 0;
      bottom: 0;
      background: rgba(200, 200, 255, 0.4);
      pointer-events: none;
    }

    .column-resize-handle {
      position: absolute;
      right: -2px;
      top: 0;
      bottom: -2px;
      width: 4px;
      background-color: #adf;
      pointer-events: none;
    }

    p {
      margin: 0;
    }
  }

  table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 0;
    overflow: hidden;

    td,
    th {
      min-width: 1em;
      border: 2px solid #ced4da;
      padding: 3px 5px;
      vertical-align: top;
      box-sizing: border-box;
      position: relative;

      > * {
        margin-bottom: 0;
      }
    }

    th {
      font-weight: bold;
      text-align: left;
      background-color: #f1f3f5;
    }

    .selectedCell:after {
      z-index: 2;
      position: absolute;
      content: "";
      left: 0;
      right: 0;
      top: 0;
      bottom: 0;
      background: rgba(200, 200, 255, 0.4);
      pointer-events: none;
    }

    .column-resize-handle {
      position: absolute;
      right: -2px;
      top: 0;
      bottom: -2px;
      width: 4px;
      background-color: #adf;
      pointer-events: none;
    }

    p {
      margin: 0;
    }
  }
}
</style>

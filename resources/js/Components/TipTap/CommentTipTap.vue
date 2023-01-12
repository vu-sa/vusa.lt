<template>
  <div class="my-4 rounded-sm shadow-lg lg:max-w-2xl">
    <EditorContent
      :editor="editor"
      class="min-h-[4em] rounded-t-lg border-vusa-yellow/50 bg-stone-50/40 shadow-inner dark:bg-zinc-800/70"
    />
    <div
      v-if="editor"
      class="tiptap-navbar flex items-center justify-between overflow-auto bg-gradient-to-tr from-zinc-200 to-zinc-100 p-2 shadow-sm dark:from-zinc-800/90 dark:to-zinc-700/90"
    >
      <div class="flex items-center">
        <button
          type="button"
          class="regular-button"
          :class="{ 'is-active': editor.isActive('bold') }"
          @click="editor?.chain().focus().toggleBold().run()"
        >
          <NIcon><TextBold20Regular></TextBold20Regular></NIcon>
        </button>

        <button
          type="button"
          class="regular-button"
          :class="{ 'is-active': editor.isActive('italic') }"
          @click="editor?.chain().focus().toggleItalic().run()"
        >
          <NIcon><TextItalic20Regular></TextItalic20Regular></NIcon>
        </button>
      </div>
      <div class="flex items-center gap-2">
        <UserAvatar :user="$page.props.auth.user" />
        <NButton
          secondary
          :disabled="editor?.getHTML() == '<p></p>'"
          :loading="loading"
          round
          class="w-full"
          @click="submitComment($page.props.auth.user)"
          ><template #icon><NIcon :component="Icons.COMMENT"></NIcon></template
          >Pridėti komentarą</NButton
        >
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon } from "naive-ui";
import { TextBold20Regular, TextItalic20Regular } from "@vicons/fluent";
import { onBeforeUnmount, ref } from "vue";
import StarterKit from "@tiptap/starter-kit";
import TipTapLink from "@tiptap/extension-link";

import Icons from "@/Types/Icons/regular";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import type { ModelEnum } from "@/Types/enums";

const props = defineProps<{
  text: string;
  commentable: Record<string, any>;
  modelName: ModelEnum;
}>();

const emit = defineEmits(["update:text"]);

const text = ref(props.text);
const loading = ref(false);

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "prose dark:prose-invert focus:outline-none p-4 h-full",
    },
  },
  extensions: [
    StarterKit,
    TipTapLink.configure({
      openOnClick: false,
    }),
  ],
  content: text.value,
  onUpdate: () => {
    // HTML
    emit("update:text", editor.value?.getHTML());
  },
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});

const submitComment = (user: unknown) => {
  loading.value = true;
  Inertia.post(
    route("users.comments.store", user.id),
    {
      commentable_type: props.modelName,
      commentable_id: props.commentable.id,
      comment: editor.value?.getHTML(),
      route: route().current(),
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        editor.value?.chain().focus().setContent("").run();
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      },
    }
  );
};
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
}

.ProseMirror {
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

.tiptap-navbar {
  button.regular-button {
    color: #000;
    background-color: #fff;
    /* border: 1px solid #000; */
    border-radius: 0.25rem;
    padding: 0.1rem 0.25rem;
    margin: 0.25rem 0.2rem;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s ease-in-out;
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  button.regular-button:hover {
    background-color: rgb(215, 215, 215);
  }

  button.is-active {
    color: #fff;
    background-color: #000;
    border: 1px solid #000;
  }

  img {
    max-width: 100%;
    height: auto;
  }
}

.tableWrapper {
  overflow-x: auto;
}

.resize-cursor {
  cursor: ew-resize;
  cursor: col-resize;
}
</style>

<template>
  <div
    class="border-vusa-red/22 mt-2 w-full border-separate rounded-lg shadow-lg"
  >
    <div
      v-if="editor"
      class="tiptap-navbar flex items-center rounded-t-lg bg-gradient-to-tr from-zinc-200 to-zinc-100 p-2 shadow-sm dark:from-zinc-800/90 dark:to-zinc-700/90"
    >
      <!-- <strong class="mb-4">Funkcijos</strong> -->
      <!-- <br /> -->
      <!-- <div class="flex items-center"> -->
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('bold') }"
        @click="editor?.chain().focus().toggleBold().run()"
      >
        <NIcon><TextBold20Regular></TextBold20Regular></NIcon>
      </button>

      <button
        type="button"
        :class="{ 'is-active': editor.isActive('italic') }"
        @click="editor?.chain().focus().toggleItalic().run()"
      >
        <NIcon><TextItalic20Regular></TextItalic20Regular></NIcon>
      </button>
      <!-- <button @click="editor.chain().focus().clearNodes().run()">clear nodes</button> -->
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('link') }"
        @click="getLinkAndModal"
      >
        <NIcon><Link20Regular></Link20Regular></NIcon>
      </button>
      <button
        type="button"
        :disabled="!editor.isActive('link')"
        @click="editor?.chain().focus().unsetLink().run()"
      >
        <NIcon><LinkDismiss20Filled></LinkDismiss20Filled></NIcon>
      </button>
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('paragraph') }"
        @click="editor?.chain().focus().setParagraph().run()"
      >
        P
      </button>
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
        @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()"
      >
        H1
      </button>
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
        @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
      >
        H2
      </button>
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
        @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
      >
        H3
      </button>
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        @click="editor?.chain().focus().toggleBulletList().run()"
      >
        <NIcon><TextBulletListLtr24Filled></TextBulletListLtr24Filled></NIcon>
      </button>
      <button
        type="button"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        @click="editor?.chain().focus().toggleOrderedList().run()"
      >
        <NIcon><TextNumberListLtr20Filled></TextNumberListLtr20Filled></NIcon>
      </button>
      <!-- <button
        type="button"
        :class="{ 'is-active': editor.isActive('blockquote') }"
        @click="editor?.chain().focus().toggleBlockquote().run()"
      >
        <NIcon><TextQuote20Filled></TextQuote20Filled></NIcon>
      </button> -->
      <button
        type="button"
        @click="editor?.chain().focus().setHorizontalRule().run()"
      >
        <NIcon><LineHorizontal120Regular></LineHorizontal120Regular></NIcon>
      </button>
      <!-- </div> -->
      <!-- <div> -->
      <button type="button" @click="editor?.chain().focus().undo().run()">
        <NIcon><ArrowUndo20Filled></ArrowUndo20Filled></NIcon>
      </button>
      <button type="button" @click="editor?.chain().focus().redo().run()">
        <NIcon><ArrowRedo20Filled></ArrowRedo20Filled></NIcon>
      </button>
      <button
        type="button"
        @click="editor?.chain().focus().unsetAllMarks().run()"
      >
        <NIcon><ClearFormatting20Filled></ClearFormatting20Filled></NIcon>
      </button>
      <!-- </div> -->
      <!-- <button
        @click="editor.chain().focus().toggleCodeBlock().run()"
        :class="{ 'is-active': editor.isActive('codeBlock') }"
      >
        code block
      </button> -->

      <!-- <button @click="editor.chain().focus().setHardBreak().run()">
        hard break
      </button> -->
    </div>

    <EditorContent
      :editor="editor"
      class="min-h-[12em] rounded-b-lg border-vusa-yellow/50 bg-stone-50/40 shadow-inner"
    />
  </div>
  <NModal v-model:show="showFileModal">
    <div class="w-1/2 rounded-sm bg-white p-4">
      <NTabs type="line" animated>
        <NTabPane name="link" tab="Pridėti nuorodą">
          <NInput
            v-model:value="previousUrl"
            placeholder="https://atstovavimas.vusa.lt"
          ></NInput>
          <div class="mt-2">
            <NButton @click="updateLink">Atnaujinti</NButton>
          </div>
        </NTabPane>
        <NTabPane name="file" tab="Pridėti failą, kaip nuorodą">
          <p class="my-2">
            Įrašyk failo pavadinimą ir pasirink, kad būtų pridėtas!
            <a
              class="text-vusa-red"
              target="_blank"
              :href="route('files.index')"
              >Failo įkėlimas</a
            >
          </p>

          <NSelect
            v-model:value="previousUrl"
            filterable
            placeholder="Ieškoti failo pagal pavadinimą...(pvz.: Darbo reglamentas)"
            clearable
            :options="files"
            remote
            @search="getFiles"
          />
          <div class="mt-2">
            <NButton @click="updateLink">Atnaujinti</NButton>
          </div>
        </NTabPane>
        <NTabPane name="image" tab="Pridėti paveikslėlį">
          <p class="my-2">
            Įrašyk paveikslėlio pavadinimą ir pasirink!
            <a
              class="text-vusa-red"
              target="_blank"
              :href="route('files.index')"
              >Failo įkėlimas</a
            >
          </p>

          <NSelect
            v-model:value="previousUrl"
            filterable
            placeholder="Ieškoti paveikslėlio...(jeigu įkėlėte paveikslėlį, rašykite jo pavadinimo bent tris raides)"
            clearable
            :options="files"
            remote
            @search="getImages"
          />
          <div class="mt-2">
            <NButton @click="placeImage">Atnaujinti</NButton>
          </div>
        </NTabPane>
      </NTabs>
    </div>
  </NModal>
</template>

<script setup lang="ts">
import {
  ArrowRedo20Filled,
  ArrowUndo20Filled,
  ClearFormatting20Filled,
  LineHorizontal120Regular,
  Link20Regular,
  LinkDismiss20Filled,
  TextBold20Regular,
  TextBulletListLtr24Filled,
  TextItalic20Regular,
  TextNumberListLtr20Filled,
  // TextQuote20Filled,
} from "@vicons/fluent";
import { EditorContent, useEditor } from "@tiptap/vue-3";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NIcon,
  NInput,
  NModal,
  NSelect,
  NTabPane,
  NTabs,
  createDiscreteApi,
} from "naive-ui";
import { debounce } from "lodash";
import { onBeforeUnmount, ref } from "vue";
import Image from "@tiptap/extension-image";
import StarterKit from "@tiptap/starter-kit";
import Table from "@tiptap/extension-table";
import TableCell from "@tiptap/extension-table-cell";
import TableHeader from "@tiptap/extension-table-header";
import TableRow from "@tiptap/extension-table-row";
import TipTapLink from "@tiptap/extension-link";
import route from "ziggy-js";

const props = defineProps<{
  modelValue: string;
  searchFiles: Record<string, unknown>;
}>();

const emit = defineEmits(["update:modelValue"]);

const showFileModal = ref(false);
const previousUrl = ref("");
const files = ref([]);
const modelValue = ref(props.modelValue);

const getLinkAndModal = () => {
  previousUrl.value = editor.value?.getAttributes("link").href;
  showFileModal.value = true;
};

const getFiles = debounce((query) => {
  if (query.length > 2) {
    message.loading("Ieškoma...");
    Inertia.post(
      route("files.search"),
      {
        data: {
          search: query,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          message.success("Pabaigta.");
          const searchFiles = Object.values(props.searchFiles);
          console.log(searchFiles);
          files.value = searchFiles.map((file) => ({
            // get the file name from the url
            label: `${file.split("/").pop()} (${file})`,
            value: file,
          }));
        },
      }
    );
  }
}, 500);

const getImages = debounce((query) => {
  if (query.length > 2) {
    message.loading("Ieškoma...");
    Inertia.post(
      route("images.search"),
      {
        data: {
          search: query,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          message.success("Pabaigta.");
          const searchFiles = Object.values(props.searchFiles);
          console.log(searchFiles);
          files.value = searchFiles.map((file) => ({
            // get the file name from the url
            label: `${file.split("/").pop()} (${file})`,
            // remove 'public' and add slash to the beginning
            value: `/uploads${file.replace("public", "")}`,
            // value: file,
          }));
        },
      }
    );
  }
}, 500);

const updateLink = () => {
  const url = previousUrl.value;
  editor.value
    ?.chain()
    .focus()
    .extendMarkRange("link")
    .setLink({ href: url })
    .run();
  showFileModal.value = false;
};

const placeImage = () => {
  const url = previousUrl.value;
  editor.value?.chain().focus().setImage({ src: url }).run();
  showFileModal.value = false;
};

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "prose-sm sm:prose focus:outline-none p-4 h-full",
    },
  },
  extensions: [
    StarterKit,
    Image,
    Table.configure({
      resizable: true,
    }),
    TableCell,
    TableHeader,
    TableRow,
    TipTapLink.configure({
      openOnClick: false,
    }),
  ],
  content: modelValue.value,
  onUpdate: () => {
    // HTML
    // console.log(editor);
    emit("update:modelValue", editor.value?.getHTML());
  },
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});

// must be called after everything
const { message } = createDiscreteApi(["message"]);
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
  button {
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

  button:hover {
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

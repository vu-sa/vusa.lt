<template>
  <div class="relative rounded-xl border-4 border-black">
    <div
      v-if="editor"
      class="absolute top-0 p-4 w-full border-b-4 border-black bg-white rounded-t-xl z-10"
    >
      <!-- <strong class="mb-4">Funkcijos</strong> -->
      <!-- <br /> -->
      <div>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('bold') }"
          @click="editor.chain().focus().toggleBold().run()"
        >
          <NIcon><TextBold20Regular></TextBold20Regular></NIcon>
        </button>

        <button
          type="button"
          :class="{ 'is-active': editor.isActive('italic') }"
          @click="editor.chain().focus().toggleItalic().run()"
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
          @click="editor.chain().focus().unsetLink().run()"
        >
          <NIcon><LinkDismiss20Filled></LinkDismiss20Filled></NIcon>
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('paragraph') }"
          @click="editor.chain().focus().setParagraph().run()"
        >
          P
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
        >
          H1
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        >
          H2
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        >
          H3
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('bulletList') }"
          @click="editor.chain().focus().toggleBulletList().run()"
        >
          <NIcon><TextBulletListLtr24Filled></TextBulletListLtr24Filled></NIcon>
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('orderedList') }"
          @click="editor.chain().focus().toggleOrderedList().run()"
        >
          <NIcon><TextNumberListLtr20Filled></TextNumberListLtr20Filled></NIcon>
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('blockquote') }"
          @click="editor.chain().focus().toggleBlockquote().run()"
        >
          <NIcon><TextQuote20Filled></TextQuote20Filled></NIcon>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().setHorizontalRule().run()"
        >
          <NIcon><LineHorizontal120Regular></LineHorizontal120Regular></NIcon>
        </button>
      </div>
      <div>
        <button type="button" @click="editor.chain().focus().undo().run()">
          <NIcon><ArrowUndo20Filled></ArrowUndo20Filled></NIcon>
        </button>
        <button type="button" @click="editor.chain().focus().redo().run()">
          <NIcon><ArrowRedo20Filled></ArrowRedo20Filled></NIcon>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().unsetAllMarks().run()"
        >
          <NIcon><ClearFormatting20Filled></ClearFormatting20Filled></NIcon>
        </button>
      </div>
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
      class="mt-2 shadow-xl px-2 py-8 rounded-lg overflow-y-auto h-fit max-h-[40rem]"
      :editor="editor"
    />
  </div>
  <NModal v-model:show="showFileModal">
    <div class="bg-white p-4 rounded-sm w-1/2">
      <NTabs class="" type="line" animated>
        <NTabPane name="link" tab="Pridėti nuorodą">
          <NInput v-model:value="previousUrl"></NInput>
          <NButton class="mt-2" type="success" @click="updateLink"
            >Atnaujinti</NButton
          >
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
            placeholder="Ieškoti puslapio..."
            clearable
            :options="files"
            remote
            @search="getFiles"
          />
          <NButton class="mt-2" type="success" @click="updateLink"
            >Atnaujinti</NButton
          >
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
            placeholder="Ieškoti puslapio..."
            clearable
            :options="files"
            remote
            @search="getImages"
          />
          <NButton class="mt-4" type="primary" @click="placeImage"
            >Atnaujinti</NButton
          >
        </NTabPane>
      </NTabs>
    </div>
  </NModal>
</template>

<script setup>
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
  TextQuote20Filled,
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
  useMessage,
} from "naive-ui";
import Image from "@tiptap/extension-image";
import StarterKit from "@tiptap/starter-kit";
import TipTapLink from "@tiptap/extension-link";
// import { usePage } from "@inertiajs/inertia-vue3";
import { Link } from "@inertiajs/inertia-vue3";
import { onBeforeUnmount, ref } from "vue";

const props = defineProps({
  modelValue: String,
  searchFiles: Object,
});

const emit = defineEmits(["update:modelValue"]);

const showFileModal = ref(false);
const previousUrl = ref("");
const files = ref([]);
const modelValue = props.modelValue;
const message = useMessage();
// const searchFiles = ref(props.searchFiles);

const addImage = () => {
  const url = window.prompt("URL");

  if (url) {
    editor.chain().focus().setImage({ src: url }).run();
  }
};

const getLinkAndModal = () => {
  previousUrl.value = editor.value.getAttributes("link").href;
  showFileModal.value = true;
};

const getFiles = _.debounce((query) => {
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
          let searchFiles = Object.values(props.searchFiles);
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

const getImages = _.debounce((query) => {
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
          let searchFiles = Object.values(props.searchFiles);
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
    .chain()
    .focus()
    .extendMarkRange("link")
    .setLink({ href: url })
    .run();
  showFileModal.value = false;
};

const placeImage = () => {
  const url = previousUrl.value;
  editor.value.chain().focus().setImage({ src: url }).run();
  showFileModal.value = false;
};

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "prose prose-sm sm:prose m-5 focus:outline-none mt-24",
    },
  },
  extensions: [
    StarterKit,
    Image,
    TipTapLink.configure({
      openOnClick: false,
    }),
  ],
  content: modelValue,
  onUpdate: () => {
    // HTML
    // console.log(editor);
    emit("update:modelValue", editor.value.getHTML());
  },
});

onBeforeUnmount(() => {
  editor.value.destroy();
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
}

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

/* .bubble-menu {
  display: flex;
  background-color: #0D0D0D;
  padding: 0.2rem;
  border-radius: 0.5rem;

  button {
    border: none;
    background: none;
    color: #FFF;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0 0.2rem;
    opacity: 0.6;

    &:hover,
    &.is-active {
      opacity: 1;
    }
  }
} */
</style>

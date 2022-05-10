<template>
  <div class="relative rounded-xl border-4 border-black">
    <div
      class="absolute top-0 p-4 w-full border-b-4 border-black bg-white rounded-t-xl z-10"
      v-if="editor"
    >
      <!-- <strong class="mb-4">Funkcijos</strong> -->
      <!-- <br /> -->
      <div>
        <button
          type="button"
          @click="editor.chain().focus().toggleBold().run()"
          :class="{ 'is-active': editor.isActive('bold') }"
        >
          <NIcon><TextBold20Regular></TextBold20Regular></NIcon>
        </button>

        <button
          type="button"
          @click="editor.chain().focus().toggleItalic().run()"
          :class="{ 'is-active': editor.isActive('italic') }"
        >
          <NIcon><TextItalic20Regular></TextItalic20Regular></NIcon>
        </button>
        <!-- <button @click="editor.chain().focus().clearNodes().run()">clear nodes</button> -->
        <button
          type="button"
          @click="getLinkAndModal"
          :class="{ 'is-active': editor.isActive('link') }"
        >
          <NIcon><Link20Regular></Link20Regular></NIcon>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().unsetLink().run()"
          :disabled="!editor.isActive('link')"
        >
          <NIcon><LinkDismiss20Filled></LinkDismiss20Filled></NIcon>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().setParagraph().run()"
          :class="{ 'is-active': editor.isActive('paragraph') }"
        >
          P
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
        >
          H1
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
        >
          H2
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
        >
          H3
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleBulletList().run()"
          :class="{ 'is-active': editor.isActive('bulletList') }"
        >
          <NIcon><TextBulletListLtr24Filled></TextBulletListLtr24Filled></NIcon>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleOrderedList().run()"
          :class="{ 'is-active': editor.isActive('orderedList') }"
        >
          <NIcon><TextNumberListLtr20Filled></TextNumberListLtr20Filled></NIcon>
        </button>
        <button
          type="button"
          @click="editor.chain().focus().toggleBlockquote().run()"
          :class="{ 'is-active': editor.isActive('blockquote') }"
        >
          <NIcon><TextQuote20Filled></TextQuote20Filled></NIcon>
        </button>
        <button type="button" @click="editor.chain().focus().setHorizontalRule().run()">
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
        <button type="button" @click="editor.chain().focus().unsetAllMarks().run()">
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
      <n-tabs class="" type="line" animated>
        <n-tab-pane name="link" tab="Add link...">
          <NInput v-model:value="previousUrl"></NInput>
        </n-tab-pane>
        <n-tab-pane name="file" tab="Add file..."
          ><NSelect
            v-model:value="previousUrl"
            filterable
            placeholder="Ieškoti puslapio..."
            :options="fileOptions"
            clearable
            remote
            @search="getFiles"
          />
        </n-tab-pane>
        <n-tab-pane name="jay chou" tab="Jay Chou"> Qilixiang </n-tab-pane>
      </n-tabs>
      <NButton @click="updateLink">Update</NButton>
    </div>
  </NModal>
</template>

<script setup>
import { useEditor, EditorContent } from "@tiptap/vue-3";
import Link from "@tiptap/extension-link";
import StarterKit from "@tiptap/starter-kit";
import Image from "@tiptap/extension-image";
import {
  Link20Regular,
  LinkDismiss20Filled,
  TextBulletListLtr24Filled,
  TextNumberListLtr20Filled,
  ArrowUndo20Filled,
  ArrowRedo20Filled,
  ClearFormatting20Filled,
  TextBold20Regular,
  TextItalic20Regular,
  LineHorizontal120Regular,
  TextQuote20Filled,
} from "@vicons/fluent";
import {
  NIcon,
  NModal,
  NTabs,
  NTabPane,
  NInput,
  NButton,
  NSelect,
  useMessage,
} from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";
import { onMounted, watch, ref, onBeforeUnmount } from "vue";

const props = defineProps({
  modelValue: String,
  files: Array,
});

const emit = defineEmits(["update:modelValue"]);

const showFileModal = ref(false);
const previousUrl = ref("");
const files = [];
const modelValue = props.modelValue;
const message = useMessage();

const addImage = () => {
  const url = window.prompt("URL");

  if (url) {
    editor.chain().focus().setImage({ src: url }).run();
  }
};

const getLinkAndModal = () => {
  previousUrl.value = editor.value.getAttributes("link").href;
  showFileModal.value = true;
  // const url = window.prompt("URL", previousUrl);
  // const url = null;

  // cancelled
  // if (url === null) {
  //   return;
  // }

  // empty
  // if (url === "") {
  //   this.editor.chain().focus().extendMarkRange("link").unsetLink().run();

  //   return;
  // }

  // update link
  //
};

const getFiles = () =>
  _.debounce((query) => {
    if (query.length > 2) {
      // this.message.loading("Ieškoma...");
      console.log(query);
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
            message.success("Ieškoma...");
            files = usePage().data.props.search.other.map((file) => {
              return {
                label: file,
                value: file,
              };
            });
          },
        }
      );
    }
  }, 500);

const updateLink = () => {
  const url = previousUrl.value;
  editor.value.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
  showFileModal.value = false;
};

// watch(modelValue, () => {
//   const isSame = editor.value.getHTML() === value;

//   if (isSame) {
//     return;
//   }

//   editor.commands.setContent(value, false);
// });

// watch: {
//   modelValue(value) {
//     const isSame = this.editor.getHTML() === value;

//     if (isSame) {
//       return;
//     }

//     this.editor.commands.setContent(value, false);
//   },
// },

const editor = useEditor({
  editorProps: {
    attributes: {
      class: "prose prose-sm sm:prose m-5 focus:outline-none mt-24",
    },
  },
  extensions: [
    StarterKit,
    Image,
    Link.configure({
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
  editor.destroy();
});
</script>

<style scoped lang="scss">
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

<template>
  <div class="gap flex w-full flex-col">
    <BubbleMenu v-if="editor" class="bg-white dark:bg-zinc-900" :editor="editor" :tippy-options="{ duration: 50 }">
      <NButtonGroup secondary>
        <TipTapMarkButton :editor="editor" type="bold" :icon="TextBold20Regular" />
        <TipTapMarkButton :editor="editor" type="italic" :icon="TextItalic20Regular" />
        <NButton size="small" :type="editor.isActive('underline') ? 'primary' : 'default'"
          @click="editor.chain().focus().toggleUnderline().run()">
          <template #icon>
            <NIcon :component="TextUnderline20Regular" />
          </template>
        </NButton>
      </NButtonGroup>
    </BubbleMenu>
    <div v-if="showToolbar && editor" class="mt-1 flex min-h-8 flex-wrap items-center gap-2">
      <NButtonGroup>
        <TipTapMarkButton :editor="editor" type="bold" :icon="TextBold20Regular" />
        <TipTapMarkButton :editor="editor" type="italic" :icon="TextItalic20Regular" />
        <NButton size="small" :type="editor.isActive('underline') ? 'primary' : 'default'"
          @click="editor.chain().focus().toggleUnderline().run()">
          <template #icon>
            <NIcon :component="TextUnderline20Regular" />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup>
        <TipTapButton :editor="editor" type="link" :icon="Link20Regular" @click="getLinkAndModal" />
        <TipTapButton :editor="editor" type="other" :disabled="!editor.isActive('link')" :icon="LinkDismiss20Filled"
          @click="editor?.chain().focus().unsetLink().run()" />
      </NButtonGroup>
      <NButton size="small" @click="editor?.chain().focus().unsetAllMarks().run()">
        <template #icon>
          <NIcon :component="ClearFormatting20Filled" />
        </template>
      </NButton>
      <NDivider vertical />
      <NButtonGroup size="small">
        <NButton :type="editor.isActive('paragraph') ? 'primary' : 'default'"
          @click="editor?.chain().focus().setParagraph().run()">
          <template #icon>
            <NIcon :component="TextT24Regular" />
          </template>
        </NButton>
        <NButton :type="editor.isActive('heading', { level: 2 }) ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()">
          <template #icon>
            <NIcon :component="TextHeader220Filled" />
          </template>
        </NButton>
        <NButton :type="editor.isActive('heading', { level: 3 }) ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()">
          <template #icon>
            <NIcon :component="TextHeader320Filled" />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton :type="editor.isActive('bulletList') ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleBulletList().run()">
          <template #icon>
            <NIcon :component="TextBulletListLtr24Filled" />
          </template>
        </NButton>
        <NButton :type="editor.isActive('orderedList') ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleOrderedList().run()">
          <template #icon>
            <NIcon :component="TextNumberListLtr20Filled" />
          </template>
        </NButton>
        <NButton :type="editor.isActive('blockquote') ? 'primary' : 'default'"
          @click="editor?.chain().focus().toggleBlockquote().run()">
          <NIcon :component="TextQuote20Filled" />
        </NButton>
      </NButtonGroup>
      <NButton size="small" :type="editor.isActive('horizontalRule') ? 'primary' : 'default'"
        @click="editor?.chain().focus().setHorizontalRule().run()">
        <template #icon>
          <NIcon :component="LineHorizontal120Regular" />
        </template>
      </NButton>
      <TiptapYoutubeButton @submit="(youtubeUrl) => editor?.commands.setYoutubeVideo({ src: youtubeUrl })" />
      <NButtonGroup size="small">
        <NButton @click="editor?.chain().focus().undo().run()">
          <template #icon>
            <NIcon :component="ArrowUndo20Filled" />
          </template>
        </NButton>
        <NButton @click="editor?.chain().focus().redo().run()">
          <template #icon>
            <NIcon :component="ArrowRedo20Filled" />
          </template>
        </NButton>
      </NButtonGroup>
    </div>
    <div v-if="showTableToolbar && editor" class="mt-1 flex items-center gap-2">
      <NButtonGroup size="small">
        <NButton @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">
          <template #icon>
            <NIcon :component="TableAdd24Regular" />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().toggleHeaderRow().run()">
          <template #icon>
            <NIcon :component="TableFreezeRow24Regular" />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().addColumnAfter().run()">
          <template #icon>
            <NIcon :component="TableInsertColumn24Regular" />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().addRowBefore().run()">
          <template #icon>
            <NIcon :component="TableInsertRow24Regular" />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton @click="editor.chain().focus().deleteColumn().run()">
          <template #icon>
            <NIcon :component="TableDeleteColumn24Regular" />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().deleteRow().run()">
          <template #icon>
            <NIcon :component="TableDeleteRow24Regular" />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().deleteTable().run()">
          <template #icon>
            <NIcon :component="TableDismiss24Regular" />
          </template>
        </NButton>
      </NButtonGroup>
      <NButtonGroup size="small">
        <NButton @click="editor.chain().focus().mergeCells().run()">
          <template #icon>
            <NIcon :component="TableCellsMerge24Regular" />
          </template>
        </NButton>
        <NButton @click="editor.chain().focus().splitCell().run()">
          <template #icon>
            <NIcon :component="TableCellsSplit24Regular" />
          </template>
        </NButton>
      </NButtonGroup>
      <NButton size="small" @click="editor.chain().focus().fixTables().run()">
        <template #icon>
          <NIcon :component="TableSettings24Regular" />
        </template>
      </NButton>
    </div>
    <div class="mt-3 grid grid-cols-[auto,_30px] items-center gap-2 only:mt-0">
      <EditorContent :editor="editor"
        class="max-h-96 min-h-24 w-full overflow-y-scroll rounded-md border dark:border-zinc-900/80 dark:bg-zinc-800/70" />
      <div class="flex flex-col gap-2">
        <NButton :type="showToolbar ? 'primary' : 'default'" size="small" @click="showToolbar = !showToolbar">
          <template #icon>
            <NIcon :component="Settings16Filled" />
          </template>
        </NButton>
        <NButton v-if="!disableTables" :type="showTableToolbar ? 'primary' : 'default'" size="small"
          @click="showTableToolbar = !showTableToolbar">
          <template #icon>
            <NIcon :component="Table24Regular" />
          </template>
        </NButton>
      </div>
    </div>
    <CardModal v-model:show="showFileModal" title="Sukurti nuorodą" @close="showFileModal = false">
      <div class="rounded-sm">
        <NTabs type="line" animated>
          <NTabPane name="link" tab="Pridėti nuorodą">
            <NInput v-model:value="previousUrl" placeholder="https://atstovavimas.vusa.lt" />
            <div class="mt-2">
              <NButton @click="updateLink">
                Atnaujinti
              </NButton>
            </div>
          </NTabPane>
          <NTabPane name="file" tab="Pridėti failą, kaip nuorodą">
            <p class="my-2">
              Įrašyk failo pavadinimą ir pasirink, kad būtų pridėtas!
              <a class="text-vusa-red" target="_blank" :href="route('files.index')">Failo įkėlimas</a>
            </p>

            <NSelect v-model:value="previousUrl" filterable
              placeholder="Ieškoti failo pagal pavadinimą...(pvz.: Darbo reglamentas)" clearable :options="files" remote
              @search="getFiles" />
            <div class="mt-2">
              <NButton @click="updateLink">
                Atnaujinti
              </NButton>
            </div>
          </NTabPane>
          <NTabPane name="image" tab="Pridėti paveikslėlį">
            <p class="my-2">
              Įrašyk paveikslėlio pavadinimą ir pasirink!
              <a class="text-vusa-red" target="_blank" :href="route('files.index')">Failo įkėlimas</a>
            </p>

            <NSelect v-model:value="previousUrl" filterable
              placeholder="Ieškoti paveikslėlio...(jeigu įkėlėte paveikslėlį, rašykite jo pavadinimo bent tris raides)"
              clearable :options="files" remote @search="getImages" />
            <div class="mt-2">
              <NButton @click="placeImage">
                Atnaujinti
              </NButton>
            </div>
          </NTabPane>
        </NTabs>
      </div>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import {
  ArrowRedo20Filled,
  ArrowUndo20Filled,
  ClearFormatting20Filled,
  LineHorizontal120Regular,
  Link20Regular,
  LinkDismiss20Filled,
  Settings16Filled,
  Table24Regular,
  TableAdd24Regular,
  TableCellsMerge24Regular,
  TableCellsSplit24Regular,
  TableDeleteColumn24Regular,
  TableDeleteRow24Regular,
  TableDismiss24Regular,
  TableFreezeRow24Regular,
  TableInsertColumn24Regular,
  TableInsertRow24Regular,
  TableSettings24Regular,
  TextBold20Regular,
  TextBulletListLtr24Filled,
  TextHeader220Filled,
  TextHeader320Filled,
  TextItalic20Regular,
  TextNumberListLtr20Filled,
  TextQuote20Filled,
  TextT24Regular,
  TextUnderline20Regular,
  // TextQuote20Filled,
} from "@vicons/fluent";
import { BubbleMenu, EditorContent, useEditor } from "@tiptap/vue-3";
import {
  NButton,
  NButtonGroup,
  NDivider,
  NIcon,
  NInput,
  NSelect,
  NTabPane,
  NTabs,
  createDiscreteApi,
} from "naive-ui";
import { onBeforeUnmount, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import Image from "@tiptap/extension-image";
import Placeholder from '@tiptap/extension-placeholder'
import StarterKit from "@tiptap/starter-kit";
import Table from "@tiptap/extension-table";
import TableCell from "@tiptap/extension-table-cell";
import TableHeader from "@tiptap/extension-table-header";
import TableRow from "@tiptap/extension-table-row";
import TipTapLink from "@tiptap/extension-link";
import UnderlineExtension from "@tiptap/extension-underline";
import YoutubeExtension from "@tiptap/extension-youtube";

import CardModal from "@/Components/Modals/CardModal.vue";
import TipTapButton from "@/Features/Admin/CommentViewer/TipTap/TipTapMarkButton.vue";
import TipTapMarkButton from "@/Features/Admin/CommentViewer/TipTap/TipTapMarkButton.vue";
import TiptapYoutubeButton from "./TiptapYoutubeButton.vue";

const props = defineProps<{
  disableTables?: boolean;
  html?: boolean;
  modelValue: string | Record<string, unknown> | null;
  searchFiles?: Record<string, unknown>;
}>();

const emit = defineEmits(["update:modelValue"]);
const modelValue = ref(props.modelValue);

const showToolbar = ref(true);
const showTableToolbar = ref(false);

const showFileModal = ref(false);
const previousUrl = ref("");
const files = ref([]);


function getLinkAndModal() {
  previousUrl.value = editor.value?.getAttributes("link").href;
  showFileModal.value = true;
}

const getFiles = useDebounceFn((query) => {
  if (query.length > 2) {
    message.loading("Ieškoma...");
    router.post(
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
          files.value = searchFiles.map((file) => ({
            // get the file name from the url
            label: `${file.split("/").pop()} (${file})`,
            // value: file,
            value: file.replace("public", "/uploads"),
          }));
        },
      }
    );
  }
}, 500);

const getImages = useDebounceFn((query) => {
  if (query.length > 2) {
    message.loading("Ieškoma...");
    router.post(
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
      class: "focus:outline-none px-3 py-2 w-full",
    },
  },
  extensions: [
    StarterKit.configure({
      heading: {
        levels: [2, 3],
      },
      codeBlock: false
    }),
    BubbleMenu,
    Placeholder.configure({
      placeholder: "Tekstas...",
    }),
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
    UnderlineExtension,
    YoutubeExtension.configure({
      HTMLAttributes: {
        class: "aspect-video h-36 w-auto my-2",
      },
    }),
  ],
  content: modelValue.value,
  onUpdate: () => {
    // HTML

    if (props.html) {
      emit("update:modelValue", editor.value?.getHTML());
    } else {
      emit("update:modelValue", editor.value?.getJSON());
    }
  },
});

onBeforeUnmount(() => {
  editor.value?.destroy();
});

// must be called after everything
const { message } = createDiscreteApi(["message"]);
</script>

<style>
.tiptap {

  p,
  ul,
  ol,
  blockquote {
    margin: 0.4rem 0 0.4rem 0;
  }

  a {
    color: #bd2835;
    text-decoration: underline;
    font-weight: 500;
  }

  blockquote {
    padding-left: 1rem;
    @apply border-l-4 border-gray-200;
  }

  /* For placeholder  */
  p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
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

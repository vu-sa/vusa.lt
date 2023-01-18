<template>
  <NDrawer
    v-model:show="active"
    :mask-closable="false"
    :auto-focus="false"
    :show-mask="false"
    :trap-focus="false"
    :width="350"
    placement="right"
  >
    <NDrawerContent closable>
      <FadeTransition>
        <div
          class="mt-8 flex flex-col items-center justify-center gap-4 transition"
        >
          <NIcon class="mr-2" size="96" :component="fileIcon" />
          <span class="text-center text-xl tracking-wide">{{
            activeDocument.name
          }}</span>
          <div class="flex gap-2">
            <NButton class="mt-4" @click="handleOpen">Atidaryti</NButton>
            <NButton
              :loading="loadingDelete"
              type="error"
              class="mt-4"
              @click="handleDelete(activeDocument.id)"
              >Ištrinti</NButton
            >
          </div>
          <NTable class="mt-4">
            <tbody class="text-xs">
              <tr>
                <td class="font-bold">Sukūrimo data</td>
                <td>{{ activeDocument.createdDateTime.date }}</td>
              </tr>
              <tr>
                <td class="font-bold">Dydis</td>
                <td>{{ fileSize(activeDocument.size) }}</td>
              </tr>
              <tr>
                <td class="font-bold">Tipas</td>
                <td>
                  <NTag size="small">
                    <NEllipsis style="max-width: 140px">{{
                      activeDocument.type
                    }}</NEllipsis>
                  </NTag>
                </td>
              </tr>
              <tr>
                <td class="font-bold">Aprašymas</td>
                <td>{{ activeDocument.description }}</td>
              </tr>
            </tbody>
          </NTable>
          <CommentPart
            :commentable_type="'sharepoint_document'"
            :text="currentCommentText"
            :model="document"
          ></CommentPart>
        </div>
      </FadeTransition>
    </NDrawerContent>
  </NDrawer>
</template>

<script setup lang="ts">
import { File, FilePdf, FileWord } from "@vicons/fa";
import {
  NButton,
  NDrawer,
  NDrawerContent,
  NEllipsis,
  NIcon,
  NTable,
  NTag,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

import { fileSize } from "@/Utils/Calc";
import CommentPart from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

// define emit for close
const emit = defineEmits<{ (event: "closeDrawer"): void }>();

const props = defineProps<{
  document: App.Entities.SharepointDocument;
}>();

const active = ref(false);
const activeDocument = ref(null);
const currentCommentText = ref("");

const loadingDelete = ref(false);

watch(
  () => props.document,
  (newDocument) => {
    if (newDocument.name === "") {
      return;
    }
    active.value = true;
    activeDocument.value = newDocument;
  }
);

watch(
  () => active.value,
  (newActive) => {
    console.log("newActive", newActive);
    if (!newActive) {
      emit("closeDrawer");
    }
  }
);

const fileIcon = computed(() => {
  console.log("activeDocument.value", activeDocument.value);
  if (activeDocument.value === null) {
    return File;
  }

  if (
    props.document.file.mimeType ===
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
  ) {
    return FileWord;
  }

  if (activeDocument.value.file.mimeType === "application/pdf") {
    return FilePdf;
  } else {
    return File;
  }
});

const handleOpen = () => {
  window.open(activeDocument.value.webUrl);
};

const handleDelete = (id) => {
  loadingDelete.value = true;
  router.delete(route("sharepoint.destroy", id), {
    preserveState: true,
    onSuccess: () => {
      loadingDelete.value = false;
      active.value = false;
    },
  });
};
</script>

<style>
/* make td sizes consistent */
.n-table td {
  min-width: 100px;
}

/* make table responsive */
.n-table {
  overflow-x: auto;
}
</style>

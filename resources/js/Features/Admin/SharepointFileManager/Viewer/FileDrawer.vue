<template>
  <NDrawer
    v-model:width="width"
    :show="active"
    :auto-focus="false"
    :show-mask="false"
    :trap-focus="false"
    placement="right"
    resizable
    @mask-click="$emit('hide:drawer')"
    @update:show="$emit('hide:drawer')"
  >
    <NDrawerContent closable>
      <FadeTransition>
        <div
          class="mt-8 flex flex-col items-center justify-center gap-4 transition"
        >
          <NIcon class="mr-2" size="96" :component="fileIcon" />
          <span class="text-center text-xl tracking-wide">{{
            file?.name
          }}</span>
          <div class="flex gap-2">
            <NButton class="mt-4" @click="handleOpen">Atidaryti</NButton>
            <!-- <NButton
              :loading="loadingDelete"
              type="error"
              class="mt-4"
              @click="handleDelete(file?.id)"
              >Ištrinti</NButton
            > -->
          </div>
          <NTable class="mt-4">
            <tbody class="text-xs">
              <tr>
                <td class="font-bold">Failo data</td>
                <td>
                  {{
                    formatStaticTime(file?.listItem?.fields?.properties.Date)
                  }}
                </td>
              </tr>
              <tr>
                <td class="font-bold">Dydis</td>
                <td>{{ fileSize(file?.size) }}</td>
              </tr>
              <tr>
                <td class="font-bold">Tipas</td>
                <td>
                  <NTag size="small">
                    <NEllipsis style="max-width: 140px">{{
                      file?.listItem?.fields?.properties?.Type
                    }}</NEllipsis>
                  </NTag>
                </td>
              </tr>
              <tr>
                <td class="font-bold">Aprašymas</td>
                <td>
                  {{ file?.listItem?.fields?.properties?.Description0 }}
                </td>
              </tr>
            </tbody>
          </NTable>
          <CommentPart
            :commentable_type="'sharepoint_file'"
            :text="currentCommentText"
            :model="file?.sharepointFile"
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
// import { router } from "@inertiajs/vue3";

import { fileSize } from "@/Utils/Calc";
import { formatStaticTime } from "@/Utils/IntlTime";
import { useStorage } from "@vueuse/core";
import CommentPart from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

// define emit for close
defineEmits<{ (event: "hide:drawer"): void }>();

const props = defineProps<{
  file: MyDriveItem | null;
}>();

const active = computed(() => !!props.file);
const currentCommentText = ref("");
const width = useStorage("file-drawer-width", 350);

watch(width, (val) => {
  if (val < 300) width.value = 300;
  if (val > 600) width.value = 600;
});

const fileIcon = computed(() => {
  if (
    props.file?.file?.mimeType ===
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
  ) {
    return FileWord;
  }

  if (props.file?.file?.mimeType === "application/pdf") {
    return FilePdf;
  } else {
    return File;
  }
});

const handleOpen = () => {
  if (props.file?.webUrl) window.open(props.file?.webUrl, "_blank");
};

// const handleDelete = (id) => {
//   loadingDelete.value = true;
//   router.delete(route("sharepoint.destroy", id), {
//     preserveState: true,
//     onSuccess: () => {
//       loadingDelete.value = false;
//       active.value = false;
//     },
//   });
// };
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

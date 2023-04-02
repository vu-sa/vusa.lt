<template>
  <NDrawer
    v-model:width="width"
    :show="active"
    :auto-focus="false"
    :show-mask="false"
    :mask-closable="false"
    :trap-focus="false"
    placement="right"
    resizable
    @update:show="$emit('hide:drawer')"
  >
    <NDrawerContent closable>
      <FadeTransition>
        <div
          class="mt-4 flex flex-col items-center justify-center gap-4 transition"
        >
          <NIcon class="mr-2" size="96" :component="fileIcon" />
          <span class="text-center text-xl tracking-wide">{{
            file?.name
          }}</span>
          <div class="flex gap-2">
            <NSpin
              size="small"
              :stroke-width="12"
              :show="loadingPublicPermission"
            >
              <div v-if="publicPermission?.link?.webUrl" class="flex gap-2">
                <NButtonGroup size="small">
                  <NButton
                    type="primary"
                    tag="a"
                    target="_blank"
                    :href="publicPermission?.link?.webUrl"
                  >
                    <template #icon
                      ><NIcon :component="Open24Regular"
                    /></template>
                    Atidaryti</NButton
                  >
                  <NMessageProvider>
                    <CopyToClipboardButton
                      :text-to-copy="publicPermission?.link?.webUrl"
                    ></CopyToClipboardButton>
                  </NMessageProvider>
                </NButtonGroup>
              </div>
              <NButton
                v-else-if="!loadingPublicPermission"
                size="small"
                @click="createPublicPermission"
                >Sukurti viešą nuorodą
                <template #icon
                  ><NIcon :component="DocumentLink24Regular"></NIcon
                ></template>
              </NButton>
            </NSpin>
            <!-- <NButton
              :loading="loadingDelete"
              type="error"
              class="mt-4"
              @click="handleDelete(file?.id)"
              >Ištrinti</NButton
            > -->
          </div>
          <NTable>
            <tbody class="text-xs">
              <tr>
                <td class="font-bold">Failo data</td>
                <td>
                  {{
                    formatStaticTime(file?.listItem?.fields?.properties.Date)
                  }}
                </td>
              </tr>
              <!-- <tr>
                <td class="font-bold">Dydis</td>
                <td>{{ fileSize(file?.size) }}</td>
              </tr> -->
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
  NButtonGroup,
  NDrawer,
  NDrawerContent,
  NEllipsis,
  NIcon,
  NMessageProvider,
  NSpin,
  NTable,
  NTag,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { useAxios } from "@vueuse/integrations/useAxios";
import { useStorage } from "@vueuse/core";
import type { Permission } from "@microsoft/microsoft-graph-types";
// import { router } from "@inertiajs/vue3";

import {
  ClipboardLink24Regular,
  DocumentLink24Regular,
  Open24Regular,
} from "@vicons/fluent";
import { fileSize } from "@/Utils/Calc";
import { formatStaticTime } from "@/Utils/IntlTime";
import CommentPart from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import CopyToClipboardButton from "@/Components/Buttons/CopyToClipboardButton.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

// define emit for close
defineEmits<{ (event: "hide:drawer"): void }>();

const props = defineProps<{
  file: MyDriveItem | null;
}>();

const active = computed(() => !!props.file);
const currentCommentText = ref("");
const publicPermission = ref<Permission | null | undefined>(null);
const loadingPublicPermission = ref(false);
const width = useStorage("file-drawer-width", 350);

watch(width, (val) => {
  if (val < 300) width.value = 300;
  if (val > 600) width.value = 600;
});

watch(
  () => props.file?.id,
  async (val) => {
    publicPermission.value = null;

    if (val) {
      loadingPublicPermission.value = true;

      let { data, isFinished } = await useAxios<Permission>(
        route("sharepoint.getDriveItemPermissions", props.file?.id)
      );
      loadingPublicPermission.value = !isFinished;
      publicPermission.value = data.value;
    }
  }
);

const createPublicPermission = async () => {
  loadingPublicPermission.value = true;

  let { data, isFinished } = await useAxios<Permission>(
    route("sharepoint.createPublicPermission", props.file?.id),
    {
      method: "POST",
    }
  );
  loadingPublicPermission.value = !isFinished;
  publicPermission.value = data.value;
};

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

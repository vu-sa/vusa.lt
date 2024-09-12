<template>
  <NDrawer v-model:width="width" :show="active" :auto-focus="false" :show-mask="false" :mask-closable="false"
    :trap-focus="false" placement="right" resizable @update:show="$emit('hide:drawer')">
    <NDrawerContent closable>
      <FadeTransition>
        <div class="mt-4 flex flex-col items-center justify-center gap-4 transition">
          <NIcon class="mr-2" size="96" :component="fileIcon" />
          <span class="text-center text-xl tracking-wide">{{
            file?.name
            }}</span>
          <div class="flex gap-2">
            <NSpin content-class="flex gap-2 items-center" size="small" :stroke-width="12"
              :show="loadingPublicPermission">
              <div v-if="publicWebUrl" class="flex gap-2">
                <NButtonGroup size="small">
                  <NButton type="primary" tag="a" target="_blank" :href="publicWebUrl">
                    <template #icon>
                      <IFluentOpen24Regular />
                    </template>
                    Atidaryti
                  </NButton>
                  <CopyToClipboardButton show-icon :text-to-copy="publicWebUrl">
                    Kopijuoti
                  </CopyToClipboardButton>
                </NButtonGroup>
              </div>
              <NButton v-else-if="!loadingPublicPermission" size="small" @click="createPublicPermission">
                Sukurti viešą
                nuorodą
                <template #icon>
                  <IFluentDocumentLink24Regular />
                </template>
              </NButton>
              <NButton v-if="!route().current('sharepointFiles.index')" size="tiny" :loading="loadingDelete"
                type="error" class="mt-4" @click="handleDelete(file?.sharepointFile)">
                <template #icon>
                  <IFluentDelete24Filled />
                </template>
              </NButton>
            </NSpin>
          </div>
          <NTable>
            <tbody class="text-xs">
              <tr>
                <td class="font-bold">
                  Failo data
                </td>
                <td>
                  {{
                    formatStaticTime(file?.listItem?.fields?.Date)
                  }}
                </td>
              </tr>
              <!-- <tr>
                <td class="font-bold">Dydis</td>
                <td>{{ fileSize(file?.size) }}</td>
              </tr> -->
              <tr>
                <td class="font-bold">
                  Tipas
                </td>
                <td>
                  <NTag size="small">
                    <NEllipsis style="max-width: 140px">
                      {{
                        file?.listItem?.fields?.Type
                      }}
                    </NEllipsis>
                  </NTag>
                </td>
              </tr>
              <tr>
                <td class="font-bold">
                  Aprašymas
                </td>
                <td>
                  {{ file?.listItem?.fields?.Description0 }}
                </td>
              </tr>
            </tbody>
          </NTable>
        </div>
      </FadeTransition>
    </NDrawerContent>
  </NDrawer>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useAxios } from "@vueuse/integrations/useAxios";
import { useStorage } from "@vueuse/core";

import File from "~icons/mdi/file";
import FilePdf from "~icons/mdi/file-pdf";
import FileWord from "~icons/mdi/file-word";

import { formatStaticTime } from "@/Utils/IntlTime";
import CopyToClipboardButton from "@/Components/Buttons/CopyToClipboardButton.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

// define emit for close
const props = defineProps<{
  file: MyDriveItem | null;
}>();

const emit = defineEmits<{
  (event: "hide:drawer"): void;
  (event: "file:deleted", id: number): void;
}>();

const active = computed(() => !!props.file);
const publicWebUrl = ref<string | null | undefined>(null);

const loadingPublicPermission = ref(false);
const loadingDelete = ref(false);
const width = useStorage("file-drawer-width", 350);

watch(width, (val) => {
  if (val < 300) width.value = 300;
  if (val > 600) width.value = 600;
});

watch(
  () => props.file?.id,
  async (val) => {
    publicWebUrl.value = null;

    if (val) {
      loadingPublicPermission.value = true;

      let { data, isFinished } = await useAxios(
        // eslint-disable-next-line no-secrets/no-secrets
        route("sharepoint.getDriveItemPublicLink", props.file?.id)
      );

      loadingPublicPermission.value = !isFinished;

      // Check if empty object
      if (Object.keys(data.value).length === 0) {
        publicWebUrl.value = null;
      } else {
        publicWebUrl.value = data.value;
      }
    }
  }
);

const createPublicPermission = async () => {
  loadingPublicPermission.value = true;

  let { data, isFinished } = await useAxios(
    route("sharepoint.createPublicPermission", props.file?.id),
    {
      method: "POST",
    }
  );
  loadingPublicPermission.value = !isFinished;
  publicWebUrl.value = data.value;
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

const handleDelete = (sharepointFile) => {
  loadingDelete.value = true;
  router.delete(route("sharepointFiles.destroy", sharepointFile.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      loadingDelete.value = false;
      emit("hide:drawer");
      emit("file:deleted", sharepointFile.sharepoint_id);
    },
    onError: () => {
      loadingDelete.value = false;
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

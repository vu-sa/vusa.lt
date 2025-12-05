<template>
  <Drawer :open="active" direction="right" @update:open="(o) => { if (!o) { $emit('hide:drawer') } }">
    <DrawerContent class="p-6">
      <FadeTransition>
        <div class="mt-4 flex flex-col items-center justify-center gap-4 transition">
          <NIcon class="mr-2" size="96" :component="fileIcon" />
          <span class="text-center text-xl tracking-wide">{{
            file?.name
            }}</span>
          <div class="flex gap-2">
            <Spinner class="flex gap-2 items-center" size="sm" :show="loadingPublicPermission">
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
            </Spinner>
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
    </DrawerContent>
  </Drawer>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useFetch, useStorage } from "@vueuse/core";
import { toast } from "vue-sonner";

import File from "~icons/mdi/file";
import FilePdf from "~icons/mdi/file-pdf";
import FileWord from "~icons/mdi/file-word";

import { formatStaticTime } from "@/Utils/IntlTime";
import CopyToClipboardButton from "@/Components/Buttons/CopyToClipboardButton.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import { Spinner } from "@/Components/ui/spinner";
import { Drawer, DrawerContent } from "@/Components/ui/drawer";

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
      // Don't fetch permissions for folders
      if (props.file?.folder) {
        publicWebUrl.value = null;
        loadingPublicPermission.value = false;
        return;
      }

      loadingPublicPermission.value = true;

      let { data, isFinished } = await useFetch(
        // eslint-disable-next-line no-secrets/no-secrets
        route("sharepoint.getDriveItemPublicLink", props.file?.id)
      ).json();

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
  // Validate item type
  if (props.file?.folder) {
    toast.error('Cannot create public link for folders. Please select a file.');
    return;
  }

  if (!props.file?.id) {
    toast.error('No file selected');
    return;
  }

  loadingPublicPermission.value = true;

  let { data, error } = await useFetch(
    route("sharepoint.createPublicPermission", props.file.id),
    {
    headers: {
      "X-CSRF-TOKEN": usePage().props.csrf_token,
      "Content-Type": "application/json",
    }
    }
  ).post().json();

  loadingPublicPermission.value = false;

  if (error.value || !data.value?.success) {
    toast.error(data.value?.error || 'Failed to create public link');
    return;
  }

  publicWebUrl.value = data.value.url;
  toast.success('Public link created successfully');
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

<template>
  <NUpload
    accept="image/jpg,image/jpeg,image/png"
    :action="route('files.uploadImage')"
    :data="{ path: props.folder }"
    :file-list="fileList"
    :headers="csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}"
    list-type="image-card"
    :max="1"
    @before-upload="beforeUpload"
    @change="fileList = $event.fileList"
    @preview="handlePreview"
    @finish="handleFinish"
    @remove="fileList = $event.fileList"
  />
  <CardModal
    v-if="url"
    class="max-w-5xl"
    :show="showModal"
    title="Redaguoti paveikslėlį"
    @close="showModal = false"
  >
    <div id="cropper-buttons" class="flex items-center gap-2">
      <NButton @click="showCropper = !showCropper"
        ><template #icon> <NIcon :component="Crop20Filled" /> </template>
        <span v-if="!showCropper"> Įjungti apkirpimą </span>
        <span v-else> Išjungti apkirpimą </span>
      </NButton>
    </div>
    <NDivider />
    <img v-if="!showCropper" :src="url" />
    <VCropper
      v-else
      v-model:src="url"
      :style="{
        height: '800px',
      }"
      :path="folder"
      @finish="handleCropFinish"
    />
  </CardModal>
</template>

<script setup lang="ts">
import { Crop20Filled } from "@vicons/fluent";
import {
  NButton,
  NDivider,
  NIcon,
  NUpload,
  type UploadFileInfo,
  useMessage,
} from "naive-ui";
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";
import VCropper from "../VCropper.vue";

const props = defineProps<{
  folder: string;
}>();

const url = defineModel<string | null>("url");
const showModal = ref(false);
const showCropper = ref(false);
const message = useMessage();

// TODO: Fix filelist generation

const fileList = ref<UploadFileInfo[]>([
  {
    id: "1",
    name: "image.jpg",
    status: "finished",
    url: url.value,
  },
]);

const csrfToken = document.querySelector<HTMLMetaElement>(
  'meta[name="csrf-token"]'
)?.content;

const beforeUpload = async (data: {
  file: UploadFileInfo;
  fileList: UploadFileInfo[];
}) => {
  // if data.file.file.type is undefined, return false
  if (!data.file.file?.type) {
    message.error("Įvyko nenumatyta klaida.");
    return false;
  }

  if (!["image/png", "image/jpeg"].includes(data.file.file.type)) {
    message.error("Prašome kelti tik JPG arba PNG formato failus.");
    return false;
  }
  return true;
};

const handlePreview = (file: UploadFileInfo) => {
  showModal.value = true;
};

const handleFinish = ({
  file,
  event,
}: {
  file: UploadFileInfo;
  event: ProgressEvent<XMLHttpRequest>;
}) => {
  let response = event?.target?.response;

  if (response) {
    response = JSON.parse(response);
    url.value = response.url;
    file.url = response.url;
  }
};

const handleCropFinish = (url: string) => {
  showCropper.value = false;
  fileList.value[0].url = url;
  console.log(fileList.value, url);
};
</script>

<template>
  <NUpload accept="image/jpg,image/jpeg,image/png" :action="route('files.uploadImage')" :data="{ path: props.folder }"
    :file-list="fileList" :headers="{ 'X-CSRF-TOKEN': $page.props.csrf_token }" list-type="image-card" :max="1"
    @before-upload="beforeUpload" @change="fileList = $event.fileList" @preview="handlePreview" @finish="handleFinish"
    @remove="handleRemove" />
  <CardModal v-if="url" class="max-w-5xl" :show="showModal" title="Redaguoti paveikslėlį" @close="showModal = false">
    <div id="cropper-buttons" class="flex items-center gap-2">
      <Button variant="outline" @click="showCropper = !showCropper">
        <IFluentCrop24Regular />
        <span v-if="!showCropper"> Įjungti apkirpimą </span>
        <span v-else> Išjungti apkirpimą </span>
      </Button>
    </div>
    <Separator />
    <img v-if="!showCropper" :src="url">
    <VCropper v-else v-model:src="url" :style="{
      height: '800px',
    }" :path="folder" @finish="handleCropFinish" />
  </CardModal>
</template>

<script setup lang="ts">
import {
  type UploadFileInfo,
} from "naive-ui";
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";
import VCropper from "../VCropper.vue";
import { Separator } from "../ui/separator";
import { Button } from "@/Components/ui/button";
import { useToasts } from '@/Composables/useToasts';

const props = defineProps<{
  folder: string;
}>();

const url = defineModel<string | null>("url");
const showModal = ref(false);
const showCropper = ref(false);
const toasts = useToasts();

const fileList = ref<UploadFileInfo[]>([
]);

if (url.value) {
  fileList.value.push({
    id: "1",
    name: "image.jpg",
    status: "finished",
    url: url.value,
  });
}

const beforeUpload = async (data: {
  file: UploadFileInfo;
  fileList: UploadFileInfo[];
}) => {
  // if data.file.file.type is undefined, return false
  if (!data.file.file?.type) {
    toasts.error("Įvyko nenumatyta klaida.");
    return false;
  }

  if (!["image/png", "image/jpeg"].includes(data.file.file.type)) {
    toasts.error("Prašome kelti tik JPG arba PNG formato failus.");
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
};

function handleRemove({ fileList }: { fileList: UploadFileInfo[] }) {
  url.value = null;
  fileList.splice(0, fileList.length);
}
</script>

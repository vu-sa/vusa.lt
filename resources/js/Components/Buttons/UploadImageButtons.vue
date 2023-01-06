<template>
  <div>
    <img v-if="modelValue" class="w-72" :src="modelValue" />
    <div class="mt-4">
      <NUpload
        accept="image/jpg,image/jpeg,image/png"
        @change="uploadFile"
        @before-upload="beforeUpload"
      >
        <NButton size="small">Įkelti paveiksliuką</NButton>
      </NUpload>
      <NButton v-if="modelValue" size="small" type="error" @click="removeLink"
        >Pašalinti</NButton
      >
    </div>
  </div>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NUpload,
  type UploadFileInfo,
  createDiscreteApi,
} from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";

const props = defineProps<{
  modelValue: string | null;
  path: string;
}>();

const modelValue = ref(props.modelValue);
const { message } = createDiscreteApi(["message"]);

const emit = defineEmits(["update:modelValue"]);

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

const uploadFile = (options: {
  file: UploadFileInfo;
  fileList: Array<UploadFileInfo>;
  event?: Event;
}) => {
  const file = options.file;

  Inertia.post(
    route("files.uploadImage"),
    { file, path: props.path },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        modelValue.value = usePage<InertiaProps>().props.value.misc;
        emit("update:modelValue", modelValue);
        message.success("Failas įkeltas");
      },
    }
  );
};

const removeLink = () => {
  modelValue.value = null;
  emit("update:modelValue", modelValue.value);
};
</script>

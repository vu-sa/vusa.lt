<template>
  <div>
    <div class="mb-4">
      <NUpload @change="uploadFile" @before-upload="beforeUpload">
        <NButton>Įkelti paveiksliuką</NButton>
      </NUpload>
      <NButton v-if="modelValue" type="error" @click="removeLink"
        >Pašalinti</NButton
      >
    </div>

    <img v-if="modelValue" :src="modelValue" />
  </div>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NButton, NUpload, UploadFileInfo, createDiscreteApi } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const props = defineProps<{
  modelValue: string | null;
  path: string;
}>();

const inertiaProps = usePage<InertiaProps>().props.value;

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
        // console.log(usePage().props.value.misc, usePage().props.value);
        // console.log(imageSource.value, usePage().props.value.misc, imageSource);
        modelValue.value = inertiaProps.misc;
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

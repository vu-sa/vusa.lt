<template>
  <div>
    <div class="mb-4">
      <NUpload @change="uploadFile" @before-upload="beforeUpload">
        <NButton>Įkelti paveiksliuką</NButton>
      </NUpload>
      <NButton v-if="modelValue" type="error" @click="removeLink">Pašalinti</NButton>
    </div>

    <img v-if="modelValue" :src="modelValue" />
  </div>
</template>

<script setup>
import { NUpload, NButton, useMessage } from "naive-ui";
import { usePage } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { ref } from "vue";

const props = defineProps({
  modelValue: String,
  path: String,
});

const modelValue = ref(props.modelValue);
const message = useMessage();

const emit = defineEmits(["update:modelValue"]);

const beforeUpload = async (data) => {
  if (!["image/png", "image/jpeg"].includes(data.file.file?.type)) {
    message.error("Prašome kelti tik JPG arba PNG formato failus.");
    return false;
  }
  return true;
};

const uploadFile = (e) => {
  let file = e.file;

  Inertia.post(
    route("files.uploadImage"),
    { file, path: props.path },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        // console.log(usePage().props.value.misc, usePage().props.value);
        // console.log(imageSource.value, usePage().props.value.misc, imageSource);
        modelValue.value = usePage().props.value.misc;
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

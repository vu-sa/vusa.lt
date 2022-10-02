<template>
  <NButton round secondary size="small" @click="showModal = true"
    >Įkelti naują failą</NButton
  >
  <NModal
    v-model:show="showModal"
    class="prose-sm prose dark:prose-invert"
    style="max-width: 600px"
    :title="`Įkelti naują failą`"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
  >
    <p v-if="!contentTypeOptions">
      Negalite įkelti failo, nes nėra numatyta turinio tipų šiai formai.
      Susisiekite su administratoriumi.
    </p>
    <NForm ref="formRef" :disabled="!contentTypeOptions" :model="model">
      <NFormItem label="Tipas" path="typeValue"
        ><NSelect
          v-model:value="model.typeValue"
          :options="contentTypeOptions"
        ></NSelect
      ></NFormItem>
      <NFormItem label="Raktažodžiai" path="keywordsValue"
        ><NSelect
          v-model:value="model.keywordsValue"
          multiple
          filterable
          tag
        ></NSelect
      ></NFormItem>
      <NFormItem label="Dokumento data" path="datetimeValue"
        ><NDatePicker
          v-model:value="model.datetimeValue"
          type="date"
        ></NDatePicker
      ></NFormItem>
      <NFormItem label="Įkelti failą" path="uploadValue">
        <NUpload :max="1" :default-upload="false" @change="handleUploadChange">
          <NUploadDragger>
            <div style="margin-bottom: 12px">
              <NIcon :component="Archive24Regular" size="48" :depth="3" />
            </div>
            <NText style="font-size: 16px">
              Paspausk arba nutempk failą čia
            </NText>
            <NP depth="3" style="margin: 8px 0 0 0">
              Pateikite tik galutinį dokumentą, kuris bus patvirtintas
            </NP>
          </NUploadDragger>
        </NUpload>
      </NFormItem>

      <NButton
        :disabled="!contentTypeOptions"
        :loading="loading"
        @click="handleValidateClick"
        >Įkelti naują failą</NButton
      ></NForm
    ></NModal
  >
</template>

<script setup lang="ts">
import { Archive24Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NIcon,
  NModal,
  NP,
  NSelect,
  NSpace,
  NTag,
  NText,
  NUpload,
  NUploadDragger,
} from "naive-ui";
import { h, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const props = defineProps<{
  contentTypeOptions: Record<string, any>[];
  keywords: Record<string, any>[];
  contentModel: Record<string, any>[];
}>();

const showModal = ref(false);
const loading = ref(false);

const formRef = ref(null);
const model = useForm({
  typeValue: null,
  keywordsValue: [],
  datetimeValue: new Date().getTime(),
  uploadValue: null,
  contentModel: props.contentModel,
});

const handleUploadChange = (files) => {
  model.uploadValue = files.fileList;
};

const handleValidateClick = (e) => {
  e.preventDefault();
  loading.value = true;
  formRef.value?.validate((errors) => {
    if (!errors) {
      Inertia.post(route("sharepoint.addFile"), model, {
        onSuccess: () => {
          console.log("success");
          showModal.value = false;
          model.reset();
          loading.value = false;
        },
        onError: () => {
          console.log("error");
          loading.value = false;
        },
        forceFormData: true,
        preserveState: true,
      });
    }
  });
};

// generate name for this file...
</script>

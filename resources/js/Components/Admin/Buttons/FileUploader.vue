<template>
  <component :is="button" @click="showModal = true"></component>
  <NModal
    v-model:show="showModal"
    class="prose prose-sm dark:prose-invert"
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
    <NForm
      ref="formRef"
      :disabled="!contentTypeOptions"
      :model="model"
      :rules="rules"
    >
      <NFormItem v-if="showObjectName" label="Failo objektas">
        <NTreeSelect
          :default-value="fileObjectName"
          default-expand-all
          :options="objectOptions"
          placeholder="Pasirink, į ką įkelsi šį failą"
          @update:value="onObjectChange"
        ></NTreeSelect>
      </NFormItem>
      <NFormItem label="Tipas" path="typeValue"
        ><NSelect
          v-model:value="model.typeValue"
          :options="contentTypeOptions"
          @update:value="onTypeChange"
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
          @update:value="onDateChange"
        ></NDatePicker
      ></NFormItem>
      <NFormItem label="Įkelti failą" path="uploadValue">
        <NUpload
          :max="1"
          :default-upload="false"
          @before-upload="beforeUpload"
          @change="handleUploadChange"
        >
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
      <NFormItem label="Sugeneruotas failo pavadinimas"
        ><NInput
          v-model:value="model.nameValue"
          placeholder=""
          :disabled="isNameEditDisabled"
        ></NInput
      ></NFormItem>

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
import { DocumentAdd24Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NModal,
  NP,
  NSelect,
  NTag,
  NText,
  NTreeSelect,
  NUpload,
  NUploadDragger,
  useMessage,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const props = defineProps<{
  button: any;
  contentTypeOptions: Record<string, any>[];
  // keywords: Record<string, any>[];
  contentModel?: Record<string, any>;
  institution?: Record<string, any>;
  objectOptions?: Record<string, any>[];
  type?: string;
  showObjectName?: boolean;
}>();

const showModal = ref(false);
const loading = ref(false);
const message = useMessage();

const originalFileName = ref("");

const formRef = ref(null);
const model = useForm({
  typeValue: props.type ?? null,
  keywordsValue: [],
  datetimeValue: new Date().getTime(),
  uploadValue: null,
  contentModel: props.contentModel ?? null,
  nameValue: null,
});

const fileObjectName = computed(() => {
  return `${props.contentModel?.title}`;
});

const rules = {
  typeValue: [
    {
      required: true,
      message: "Pasirinkite tipą",
    },
  ],
  // keywordsValue: [
  //   {
  //     required: true,
  //     message: "Pasirinkite bent vieną raktažodį",
  //   },
  // ],
  datetimeValue: [
    {
      required: true,
      message: "Pasirinkite dokumento datą",
    },
  ],
  uploadValue: [
    {
      required: true,
      message: "Įkelkite failą",
    },
  ],
};

const handleUploadChange = (files) => {
  model.uploadValue = files.fileList;
  originalFileName.value = files.fileList[0].name;
  generateName();
};

const handleValidateClick = (e) => {
  e.preventDefault();
  formRef.value?.validate((errors) => {
    if (!errors) {
      loading.value = true;

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

const isNameEditDisabled = computed(() => {
  // check if model.nameValue is empty
  if (model.nameValue === null) {
    return true;
  }

  // check if model.typeValue is protokolas
  if (model.typeValue === "Protokolai") {
    return true;
  }

  return false;
});

// generate name for this file...
const generateName = () => {
  if (originalFileName.value === "" || model.typeValue === null) {
    return;
  }

  // get file extension from original file name
  const fileExtension = originalFileName.value.split(".").pop();

  const datetimeValue = model.datetimeValue;
  // add +1 day to datetimeValue
  const datetimeValuePlusOneDay = new Date(datetimeValue);
  datetimeValuePlusOneDay.setDate(datetimeValuePlusOneDay.getDate() + 1);

  // make date format like 2021-01-01
  const dateFormatted = new Date(datetimeValuePlusOneDay)
    .toISOString()
    .split("T")[0];

  // if posėdis
  // if (props.contentModel.contentTypes.some((x) => x.title === "Posėdis")) {
  //   model.nameValue = `${dateFormatted} ${props.institution.name} posėdžio ${model.typeValue}.${fileExtension}`;
  // }

  // if other, keep same name
  model.nameValue = originalFileName.value;
};

const onTypeChange = (value) => {
  if (value === "Protokolai") {
    generateName();
  } else model.nameValue = originalFileName.value;
};

const onDateChange = () => {
  generateName();
};

const beforeUpload = async (data) => {
  // check if file is pdf or docx
  if (
    data.file.type !== "application/pdf" &&
    data.file.type !==
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
  ) {
    message.error("Failas turi būti PDF arba DOCX formatu.");
    return false;
  }
  return true;
};

const onObjectChange = (value) => {
  model.contentModel.id = value;
};
</script>

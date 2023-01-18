<template>
  <NForm ref="formRef" :model="model" :rules="rules">
    <NFormItem label="Tipas" path="typeValue"
      ><NSelect
        v-model:value="model.typeValue"
        :disabled="model.uploadValue ?? false"
        placeholder="Pasirink failo tipą..."
        :options="sharepointFileTypeOptions"
      ></NSelect
    ></NFormItem>
    <!-- <NFormItem label="Raktažodžiai" path="keywordsValue"
        ><NSelect
          v-model:value="model.keywordsValue"
          multiple
          filterable
          tag
        ></NSelect
      ></NFormItem> -->
    <NFormItem label="Dokumento data" path="datetimeValue"
      ><NDatePicker
        v-model:value="model.datetimeValue"
        placeholder="2022-12-01"
        type="date"
      ></NDatePicker
    ></NFormItem>
    <NFormItem label="Aprašymas" path="descriptionValue"
      ><NInput
        v-model:value="model.description0Value"
        type="textarea"
        placeholder="Šis dokumentas yra skirtas..."
      ></NInput
    ></NFormItem>
    <NFormItem v-if="model.typeValue" label="Įkelti failą" path="uploadValue">
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
    <NFormItem v-if="model.typeValue" label="Sugeneruotas failo pavadinimas">
      <NInputGroup>
        <NInput
          v-model:value="model.nameValue"
          :style="{ width: '85%' }"
          placeholder=""
          :disabled="fileNameEditDisabled"
        ></NInput>
        <NInput
          placeholder=""
          disabled
          :value="fileExtension"
          :style="{ width: '15%' }"
        ></NInput>
      </NInputGroup>
    </NFormItem>

    <NButton
      type="primary"
      :disabled="!model.uploadValue"
      :loading="loading"
      @click="handleValidateClick"
      ><template #icon
        ><NIcon :component="DocumentAdd24Regular"></NIcon></template
      >Įkelti failą</NButton
    ></NForm
  >
</template>

<script setup lang="tsx">
import { Archive24Regular, DocumentAdd24Regular } from "@vicons/fluent";
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputGroup,
  NP,
  NSelect,
  NText,
  NUpload,
  NUploadDragger,
  useMessage,
} from "naive-ui";
import { generateNameForFile } from "./generateNameForFile";
import { modelTypes } from "@/Types/formOptions";
import { ref, watch } from "vue";
import { splitFileNameAndExtension } from "@/Utils/String";
import { useForm } from "@inertiajs/vue3";
import type { FormInst, FormRules, UploadFileInfo } from "naive-ui";

defineEmits<{
  (e: "submit", form: any): void;
  (e: "close"): void;
}>();

const props = defineProps<{
  // fileable is used for name generation
  fileable?: Record<string, any>;
}>();

const loading = ref(false);
const fileNameEditDisabled = ref(true);
const message = useMessage();

const originalFileName = ref("");
const fileExtension = ref<string | undefined>("");

const formRef = ref<FormInst | null>(null);
const model = useForm<{
  datetimeValue: number | null;
  description0Value: string;
  nameValue: string | null;
  //   keywordsValue: string[];
  typeValue: string | null;
  uploadValue: UploadFileInfo | null;
}>({
  datetimeValue: null,
  description0Value: "",
  nameValue: null,
  //   keywordsValue: [],
  typeValue: null,
  uploadValue: null,
});

const sharepointFileTypeOptions = modelTypes.sharepointFile.map((type) => ({
  label: type,
  value: type,
}));

const rules: FormRules = {
  typeValue: [
    {
      required: true,
      message: "Pasirinkite tipą",
      trigger: ["blur", "change"],
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
      trigger: ["blur", "change"],
      type: "number",
    },
  ],
  uploadValue: [
    {
      required: true,
      message: "Įkelkite failą",
      trigger: ["blur", "change"],
      // type is file object
      type: "object",
    },
  ],
};

const beforeUpload = async (data: {
  file: UploadFileInfo;
  fileList: UploadFileInfo[];
}) => {
  if (!data.file.type) return;

  // check if pristatymai is pptx or pdf
  if (model.typeValue === "Pristatymai") {
    if (
      ![
        "application/pdf",
        "application/vnd.openxmlformats-officedocument.presentationml.presentation",
      ].includes(data.file.type)
    ) {
      message.error("Pristatymas turi būti PDF arba PPTX formatu.");
      return false;
    }
    return true;
  }

  // others, check if file is pdf or docx
  if (
    ![
      "application/pdf",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    ].includes(data.file.type)
  ) {
    message.error("Failas turi būti PDF arba DOCX formatu.");
    return false;
  }
  return true;
};

const handleUploadChange = ({
  fileList,
}: {
  fileList: Array<UploadFileInfo>;
}) => {
  // 1. check if file removed
  if (fileList.length === 0) {
    model.uploadValue = null;
    originalFileName.value = "";
    model.nameValue = null;
    return;
  }

  model.uploadValue = fileList[0];
  console.log(fileList[0]);

  let { name, extension } = splitFileNameAndExtension(fileList[0].name);

  fileExtension.value = extension;
  originalFileName.value = name;
};

const unwatch = watch(model, () => {
  model.nameValue = setUploadFileName();
});

// generate name for this file...
const setUploadFileName = () => {
  fileNameEditDisabled.value = true;
  if (originalFileName.value === "" || model.typeValue === null) {
    return null;
  }

  const { fileName, isFileNameEditable } = generateNameForFile(
    {
      dateValue: model.datetimeValue,
      nameValue: model.nameValue,
      typeValue: model.typeValue,
    },
    props.fileable
  );

  fileNameEditDisabled.value = isFileNameEditable;
  return fileName;
};

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();

  formRef.value?.validate((errors) => {
    if (!errors) {
      loading.value = true;
      // remove watcher, to don't change the seen file name
      unwatch();
      if (model.nameValue === null || !fileExtension.value) {
        return;
      }
      model.transform((data) => ({
        ...data,
        nameValue: model.nameValue + fileExtension.value,
      }));
    }
  });
};
</script>

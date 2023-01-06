<template>
  <component :is="button" @click="showModal = true"></component>
  <CardModal
    v-model:show="showModal"
    :title="`Įkelti naują failą`"
    @close="showModal = false"
  >
    <p v-if="!contentTypeOptions">
      Negalite įkelti failo, nes nėra numatyta turinio tipų šiai formai.
      Susisiekite su administratoriumi.
    </p>
    <NForm
      ref="formRef"
      :disabled="!contentTypeOptions || loading"
      :model="model"
      :rules="rules"
    >
      <NFormItem v-if="contentModelOptions" required label="Failo objektas">
        <NTreeSelect
          :default-value="fileObjectName"
          filterable
          default-expand-all
          :options="contentModelOptions"
          placeholder="Pasirink, į ką įkelsi šį failą..."
          @update:value="onObjectChange"
        ></NTreeSelect>
      </NFormItem>
      <NFormItem v-if="!prespecifiedType" label="Tipas" path="typeValue"
        ><NSelect
          v-model:value="model.typeValue"
          :disabled="model.uploadValue"
          placeholder="Pasirink failo tipą..."
          :options="contentTypeOptions"
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
          v-model:value="model.descriptionValue"
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
            v-model:value="nameValue"
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
        :disabled="!contentTypeOptions"
        :loading="loading"
        @click="handleValidateClick"
        >Įkelti naują failą</NButton
      ></NForm
    ></CardModal
  >
</template>

<script setup lang="ts">
import type { FormInst, FormRules, UploadFileInfo } from "naive-ui";

import { Archive24Regular } from "@vicons/fluent";
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
  // NTag,
  NText,
  NTreeSelect,
  NUpload,
  NUploadDragger,
  useMessage,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import CardModal from "@/Components/Modals/CardModal.vue";

const props = defineProps<{
  button: any; // yes
  contentModel?: Record<string, any>; // yes
  contentModelOptions?: Record<string, any>[];
  contentTypeOptions: Record<string, any>[]; // yes
  relatedObjectName?: string | null;
  // keywords: Record<string, any>[]; // maybe

  // If some options are prespecified, they are automatically included in the request model
  prespecifiedType?: string;
}>();

const showModal = ref(false);
const loading = ref(false);
const fileNameEditDisabled = ref(true);
const message = useMessage();

const originalFileName = ref("");
const fileExtension = ref("");
const nameValue = ref<string | null>(null);

const formRef = ref<FormInst | null>(null);
const model = useForm({
  datetimeValue: null,
  descriptionValue: "",
  contentModel: props.contentModel ?? null,
  keywordsValue: [],
  typeValue: props.prespecifiedType ?? null,
  uploadValue: null,
});

const fileObjectName = computed(() => {
  return `${props.contentModel?.title}`;
});

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
      type: "array",
    },
  ],
};

const beforeUpload = async (data) => {
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
  // check if file removed
  if (fileList.length === 0) {
    model.uploadValue = null;
    originalFileName.value = "";
    nameValue.value = null;
    return;
  }

  model.uploadValue = fileList;

  let fileNameParts = fileList[0].name.split(".");

  // Remove the last element of the array, which is the file extension
  fileExtension.value = `.${fileNameParts.pop()}`;

  // Use the join() method to join the remaining parts of the file name together
  let fileFullName = fileNameParts.join(".");

  originalFileName.value = fileFullName;
};

const handleValidateClick = (e) => {
  e.preventDefault();
  console.log(model.uploadValue);
  formRef.value?.validate((errors) => {
    if (!errors) {
      loading.value = true;
      // remove watcher, to don't change the seen file name
      unwatch();
      model
        .transform((data) => ({
          ...data,
          nameValue: nameValue.value + fileExtension.value,
        }))
        .post(route("sharepoint.addFile"), {
          onSuccess: () => {
            console.log("success");
            showModal.value = false;
            model.reset();
            originalFileName.value = "";
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

const unwatch = watch(model, () => {
  nameValue.value = generateNameForFile();
});

// generate name for this file...
const generateNameForFile = () => {
  fileNameEditDisabled.value = true;

  if (originalFileName.value === "" || model.typeValue === null) {
    return null;
  }

  let date =
    model?.datetimeValue === null ? new Date() : new Date(model.datetimeValue);

  let dateFormatter = new Intl.DateTimeFormat("lt-LT", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });

  // if posėdis
  if (
    props.contentModel?.modelTypes?.some((x) => x.title === "Posėdis") &&
    model.typeValue === "Protokolai"
  ) {
    let relatedObjectName =
      props.relatedObjectName === undefined
        ? ""
        : genitivize(props.relatedObjectName) + " ";

    return `${dateFormatter.format(
      date
    )} ${relatedObjectName}posėdžio protokolas`;
  }

  // if other, keep same name
  fileNameEditDisabled.value = false;
  return originalFileName.value;
};

const onObjectChange = (value) => {
  if (model.contentModel === null) {
    return;
  }

  model.contentModel.id = value;
};

const genitivize = (name: string | null) => {
  if (name === null) {
    return "";
  }

  return name
    .replace(/a$/, "os")
    .replace(/as$/, "o")
    .replace(/iai$/, "ių")
    .replace(/ė$/, "ės");
};
</script>

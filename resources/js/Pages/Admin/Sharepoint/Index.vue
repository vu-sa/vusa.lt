<template>
  <PageContent title="Sharepoint">
    <Transition name="fade" appear>
      <div class="main-card">
        <h3>Sharepoint failai</h3>
        <NDataTable :data="sharepointFiles" :columns="columns"></NDataTable>

        <div class="mt-4">
          <NButton @click="showModal = true">Įkelti naują failą</NButton>
        </div>
      </div>
    </Transition>
  </PageContent>
  <!-- create NModal with form for file upload -->
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
    <NForm ref="formRef" :model="model">
      <NFormItem label="Tipas" path="typeValue"
        ><NSelect
          v-model:value="model.typeValue"
          :options="termStorePagrindinis"
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
        <NUpload :default-upload="false" @change="handleUploadChange">
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

      <NButton :loading="loading" @click="handleValidateClick"
        >Įkelti naują failą</NButton
      ></NForm
    ></NModal
  >
</template>

<script lang="ts">
import { Archive24Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NDataTable,
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

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

defineProps<{
  sharepointFiles: Record<string, any>[];
  termStorePagrindinis: Record<string, any>[];
}>();

const showModal = ref(false);
const loading = ref(false);

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    render(row) {
      return h("a", { href: row.webUrl, target: "_blank" }, row.name);
    },
  },
  {
    title: "Tipas",
    key: "type",
  },
  {
    title: "Raktažodžiai",
    key: "keywords",
    render(row) {
      return h(
        NSpace,
        {},
        {
          default: () =>
            row.keywords?.map((keyword) =>
              h(NTag, {}, { default: () => keyword })
            ),
        }
      );
    },
  },
  {
    title: "Data",
    key: "date",
  },
];

// date now to timestamp
const formRef = ref(null);
const model = useForm("dutyInstitution", {
  typeValue: null,
  keywordsValue: [],
  datetimeValue: new Date().getTime(),
  uploadValue: null,
});

const handleUploadChange = (files) => {
  model.uploadValue = files.fileList;
};

const handleValidateClick = (e) => {
  e.preventDefault();
  loading.value = true;
  formRef.value?.validate((errors) => {
    if (!errors) {
      Inertia.post(route("sharepoint.upload"), model, {
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
</script>

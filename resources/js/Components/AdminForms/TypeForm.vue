<template>
  <NForm :model="form" label-placement="top">
    <div class="grid gap-x-12 lg:grid-cols-6">
      <FormElement>
        <template #title>Pagrindinė informacija</template>
        <template #description
          >Pagrindinė informacija apie turinio tipą.</template
        >
        <NFormItem required label="Pavadinimas">
          <NInput
            v-model:value="form.title"
            type="text"
            placeholder="Turinio tipas"
          />
        </NFormItem>

        <NFormItem label="Aprašymas" :span="6">
          <NInput
            v-model:value="form.description"
            type="textarea"
            placeholder="Ilgas aprašymas..."
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Tipo parametrai</template>
        <template #description>Informacija apie tipą</template>
        <NFormItem required label="Modelio tipas" :span="2">
          <NSelect
            v-model:value="form.model_type"
            :options="modelDefaults"
            placeholder="Institucija"
            @update:value="form.parent_id = null"
          />
        </NFormItem>
        <NFormItem label="Tėvinis tipas" :span="2">
          <NSelect
            v-model:value="form.parent_id"
            label-field="title"
            value-field="id"
            :clearable="true"
            :options="parentTypeOptions"
            placeholder="Studentų atstovybė"
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Failai</template>
        <template #description
          >Failai, susiję su šiuo tipu. Šie failai rodomi atitinkamose vietose
          prie modelių, kurie priklauso šiam tipui.</template
        >
        <FileManager
          :starting-path="sharepointPath"
          :fileable="fileable"
        ></FileManager>
      </FormElement>
      <FormElement no-divider>
        <template #title>Kiti nustatymai</template>
        <NFormItem label="Techninė žymė">
          <NInput
            v-model:value="form.slug"
            :disabled="modelRoute === 'types.update'"
            type="text"
            placeholder="pvz.: turinio-tipas"
          />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NDivider,
  NForm,
  NFormItem,
  NFormItemGi,
  NGi,
  NInput,
  NSelect,
} from "naive-ui";
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";

import { modelTypes } from "@/Types/formOptions";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import FormElement from "./FormElement.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  type: App.Entities.Type;
  fileable: any;
  contentTypes: Record<string, any>[];
  modelRoute: string;
  deleteModelRoute?: string;
  sharepointPath: string;
}>();

const form = useForm("type", props.type);

const modelDefaults = modelTypes.type.map((type) => {
  return {
    value: "App\\Models\\" + type,
    label: type,
  };
});

const parentTypeOptions = computed(() => {
  return props.contentTypes.filter(
    (type) => form.model_type === type.model_type && form.id !== type.id
  );
});
</script>

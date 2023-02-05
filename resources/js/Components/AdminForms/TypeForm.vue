<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Pagrindinė informacija</template>
        <template #description
          >Pagrindinė informacija apie turinio tipą.</template
        >
        <NFormItem required>
          <template #label>
            <span class="inline-flex items-center gap-1">
              <NIcon :component="Icons.TITLE" />
              Pavadinimas
            </span>
          </template>
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
        <template #description>Parametrai</template>
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
          :fileable="{ id: form.id, type: 'Type' }"
        ></FileManager>
      </FormElement>
      <FormElement no-divider>
        <template #title>Kiti nustatymai</template>
        <NFormItem label="Techninė žymė">
          <template #label
            ><span class="inline-flex items-center gap-1"
              >Techninė žymė
              <InfoPopover
                >Keičiama tik išskirtiniais atvejais.</InfoPopover
              ></span
            ></template
          >
          <NInput
            v-model:value="form.slug"
            type="text"
            placeholder="pvz.: turinio-tipas"
          />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <NButton @click="handleSubmit">Naujinti</NButton>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { NButton, NForm, NFormItem, NIcon, NInput, NSelect } from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { modelTypes } from "@/Types/formOptions";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/icons/filled";
import InfoPopover from "../Buttons/InfoPopover.vue";

const emit = defineEmits<{
  (event: "submit:form", form: any): void;
}>();

const props = defineProps<{
  type: App.Entities.Type;
  contentTypes: Record<string, any>[];
  sharepointPath: string;
}>();

const loading = ref(false);

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

const handleSubmit = () => {
  loading.value = true;
  emit("submit:form", form);
};
</script>

<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        Pagrindinė informacija apie turinio tipą.
      </template>
      <NFormItem required>
        <template #label>
          <span class="inline-flex items-center gap-1">
            <NIcon :component="Icons.TITLE" />
            Pavadinimas
          </span>
        </template>
        <MultiLocaleInput v-model:input="form.title"
          :placeholder="{ lt: 'Studentų atstovų organas', en: 'Student representative body' }" />
      </NFormItem>

      <NFormItem>
        <template #label>
          <div class="inline-flex items-center gap-2">
            Aprašymas
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
        </template>
        <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <TipTap v-else v-model="form.description.en" html />
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Tipo parametrai
      </template>
      <template #description>
        Parametrai
      </template>
      <NFormItem required label="Modelio tipas" :span="2">
        <NSelect v-model:value="form.model_type" :options="modelDefaults" placeholder="Institucija"
          @update:value="form.parent_id = null" />
      </NFormItem>
      <NFormItem label="Tėvinis tipas" :span="2">
        <NSelect v-model:value="form.parent_id" label-field="title" value-field="id" :clearable="true"
          :options="parentTypeOptions" placeholder="Studentų atstovybė" />
      </NFormItem>
    </FormElement>
    <FormElement v-if="sharepointPath">
      <template #title>
        Failai
      </template>
      <template #description>
        Failai, susiję su šiuo tipu. Šie failai rodomi atitinkamose vietose
        prie modelių, kurie priklauso šiam tipui.
      </template>
      <FileManager :starting-path="sharepointPath" :fileable="{ id: form.id, type: 'Type' }" />
    </FormElement>
    <FormElement>
      <template #title>
        Tipą turintys modeliai
      </template>
      <template #description>
        Modeliai, kurie priklauso šiam tipui.
      </template>
      <NFormItem label="Modeliai" :span="6">
        <NTransfer v-model:value="form[props.modelType]" source-filterable :render-source-label="renderSourceLabel"
          virtual-scroll :options="modelOptions" />
      </NFormItem>
    </FormElement>
    <FormElement v-if="form.model_type === 'App\\Models\\Duty'">
      <template #title>
        Rolės, kurios priskiriamos pareigybėms
      </template>
      <template #description>
        Šios rolės automatiškai priskiriamos pareigybėms su šiuo
        tipu.
      </template>
      <NFormItem label="Rolės" :span="6">
        <NTransfer v-model:value="form.roles" :options="roles?.map((role) => ({
          value: role.id,
          label: role.name,
          role: role,
        }))
          " />
      </NFormItem>
    </FormElement>
    <FormElement no-divider>
      <template #title>
        Kiti nustatymai
      </template>
      <NFormItem label="Techninė žymė">
        <template #label>
          <span class="inline-flex items-center gap-1">Techninė žymė
            <InfoPopover>Keičiama tik išskirtiniais atvejais.</InfoPopover>
          </span>
        </template>
        <NInput v-model:value="form.slug" type="text" placeholder="pvz.: turinio-tipas" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import Edit16Filled from "~icons/fluent/edit16-filled"

import { modelTypes } from "@/Types/formOptions";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/filled";
import InfoPopover from "../Buttons/InfoPopover.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "../TipTap/OriginalTipTap.vue";
import AdminForm from "./AdminForm.vue";

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const props = defineProps<{
  type: App.Entities.Type;
  modelType?: string;
  contentTypes: Record<string, any>[];
  sharepointPath?: string;
  allModelsFromModelType?: Record<string, any>[];
  roles?: App.Entities.Role[];
  rememberKey?: "CreateType";
}>();

const locale = ref("lt");

const form = props.rememberKey
  ? useForm(props.rememberKey, props.type)
  : useForm(props.type);

// map e.g. form.institutions to id only, so it's used in transfer values

if (props.modelType) {
  form[props.modelType] = props.type[props.modelType]?.map((model) => model.id);
}

const modelDefaults = modelTypes.type.map((type) => {
  return {
    value: "App\\Models\\" + type,
    label: type,
  };
});

const modelOptions = computed(() => {
  return props.allModelsFromModelType?.map((model) => {
    return {
      value: model.id,
      label: model.title ?? model.name,
      model: model,
    };
  });
});

const parentTypeOptions = computed(() => {
  return props.contentTypes.filter(
    (type) => form.model_type === type.model_type && form.id !== type.id,
  );
});

const renderSourceLabel = ({ option }) => {
  return (
    <>
      <span>
        {`${option.label} (${option.model?.tenants?.[0]?.shortname ??
          option.model?.tenants?.shortname
          })`}
      </span>
      <a target="_blank" href={route(`${props.modelType}.edit`, option.value)}>
        <NButton onClick={(e) => e.stopPropagation()} text size="tiny">
          {{
            icon: <NIcon class="ml-2 align-middle" component={Edit16Filled} />,
          }}
        </NButton>
      </a>
    </>
  );
};
</script>

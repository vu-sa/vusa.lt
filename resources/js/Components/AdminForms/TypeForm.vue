<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>{{ $t("forms.context.main_info") }} </template>
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
          <OriginalTipTap html
            v-model="form.description"
            placeholder="Ilgas aprašymas..."
            :search-files="$page.props.search.other"
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
      <FormElement v-if="sharepointPath">
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
      <FormElement>
        <template #title>Tipą turintys modeliai</template>
        <template #description>Modeliai, kurie priklauso šiam tipui.</template>
        <NFormItem label="Modeliai" :span="6">
          <NTransfer
            v-model:value="form[props.modelType]"
            source-filterable
            :render-source-label="renderSourceLabel"
            virtual-scroll
            :options="modelOptions"
          ></NTransfer>
        </NFormItem>
      </FormElement>
      <FormElement v-if="form.model_type === 'App\\Models\\Duty'">
        <template #title>Rolės, kurios priskiriamos pareigybėms</template>
        <template #description
          >Šios rolės automatiškai priskiriamos pareigybėms su šiuo
          tipu.</template
        >
        <NFormItem label="Rolės" :span="6">
          <NTransfer
            v-model:value="form.roles"
            :options="
              roles?.map((role) => ({
                value: role.id,
                label: role.name,
                role: role,
              }))
            "
          ></NTransfer>
        </NFormItem>
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

<script setup lang="tsx">
import {
  NButton,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NSelect,
  NTransfer,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { Edit16Filled } from "@vicons/fluent";
import { modelTypes } from "@/Types/formOptions";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/filled";
import InfoPopover from "../Buttons/InfoPopover.vue";
import OriginalTipTap from "../TipTap/OriginalTipTap.vue";

const emit = defineEmits<{
  (event: "submit:form", form: any): void;
}>();

const props = defineProps<{
  type: App.Entities.Type;
  modelType?: string;
  contentTypes: Record<string, any>[];
  sharepointPath?: string;
  allModelsFromModelType?: Record<string, any>[];
  roles?: App.Entities.Role[];
}>();

const loading = ref(false);

const form = useForm("type", props.type);

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
        {`${option.label} (${
          option.model?.padaliniai?.[0]?.shortname ??
          option.model?.padaliniai?.shortname
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

const handleSubmit = () => {
  loading.value = true;
  emit("submit:form", form);
};
</script>
